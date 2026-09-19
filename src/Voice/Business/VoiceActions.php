<?php

namespace Codatsoft\Realtime\Voice\Business;

use Agence104\LiveKit\RoomAgentDispatch;
use Agence104\LiveKit\RoomConfiguration;
use Agence104\LiveKit\VideoGrant;
use Codatsoft\Realtime\Contracts\BuildsDispatchMetadata;
use Codatsoft\Realtime\Contracts\ResolvesVoiceUser;
use Codatsoft\Realtime\Voice\Http\Responses\HTResVoiceToken;
use Codatsoft\Realtime\Voice\Models\VoiceUser;
use Codatsoft\Realtime\Voice\Services\AgentApiTokens;
use Codatsoft\Realtime\Voice\Services\LiveKitService;
use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Illuminate\Support\Str;

/**
 * Join tokens. The agent room is private to the user and dispatches the product's
 * agent; call rooms are named and managed by VoiceCallActions and only signed here.
 */
class VoiceActions
{
    /*
     * The signed-in user is resolved per call, not in the constructor: Laravel keeps one
     * controller instance per route for the life of the process, so anything captured at
     * construction would stick to whoever made the first request.
     */
    public function __construct(
        protected ResolvesVoiceUser $users,
        protected LiveKitService $liveKit,
        protected AgentApiTokens $agentTokens,
        protected BuildsDispatchMetadata $dispatchMetadata,
    ) {}

    /**
     * A private room for the signed-in user and the AI agent. Each request opens a fresh
     * room, so a stale session can never be rejoined by accident.
     */
    public function agentToken(): HTResVoiceToken
    {
        $user = $this->users->current();
        $roomName = TVoiceRoomKind::AGENT->value . '-' . $user->userId . '-' . Str::lower(Str::random(8));

        $roomConfig = (new RoomConfiguration())
            ->setEmptyTimeout((int) config('realtime.livekit.rooms.agent_empty_timeout'))
            ->setMaxParticipants((int) config('realtime.livekit.rooms.agent_max_participants'));

        $agentName = (string) config('realtime.livekit.agent_name');

        if ($agentName !== '')
        {
            $dispatch = (new RoomAgentDispatch())
                ->setAgentName($agentName)
                ->setMetadata($this->encodeDispatchMetadata($user, $roomName));

            $roomConfig->setAgents([$dispatch]);
        }

        return $this->issue($user, TVoiceRoomKind::AGENT, $roomName, $roomConfig);
    }

    /**
     * A join token for one participant in a user-to-user call room. The call itself (who,
     * state, ringing) lives in VoiceCallActions; this only signs entry to the room with the
     * call room's limits.
     */
    public function callRoomToken(string $roomName): HTResVoiceToken
    {
        $roomConfig = (new RoomConfiguration())
            ->setEmptyTimeout((int) config('realtime.livekit.rooms.call_empty_timeout'))
            ->setMaxParticipants((int) config('realtime.livekit.rooms.call_max_participants'));

        return $this->issue($this->users->current(), TVoiceRoomKind::CALL, $roomName, $roomConfig);
    }

    /**
     * What only the agent worker gets to see: the product's dispatch metadata plus a
     * Sanctum token that lets the worker call the API as the user. Dispatch metadata
     * travels to the worker, never to other participants, and the token is revoked when
     * the room finishes (see VoiceWebhookActions).
     */
    protected function encodeDispatchMetadata(VoiceUser $user, string $roomName): string
    {
        $metadata = $this->dispatchMetadata->build($user, $roomName);
        $metadata['apiToken'] = $this->agentTokens->issueForCurrentUser($roomName);

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Every token this module hands out is a plain participant: publish own audio,
     * subscribe to the rest, no room admin rights. Anything administrative goes through
     * the server API, never through a client token. Updating its own metadata is allowed
     * because the phone reports its position to the agent as participant attributes.
     */
    protected function issue(VoiceUser $user, TVoiceRoomKind $kind, string $roomName, RoomConfiguration $roomConfig): HTResVoiceToken
    {
        $grant = (new VideoGrant())
            ->setCanPublish()
            ->setCanSubscribe()
            ->setCanPublishData()
            ->setCanUpdateOwnMetadata(true);

        $identity = $this->identity($user);
        $token = $this->liveKit->joinToken($identity, $user->userName, $roomName, $grant, $this->participantMetadata($user), $roomConfig);

        return HTResVoiceToken::from($this->liveKit->url(), $token, $roomName, $kind, $identity, time() + $this->liveKit->tokenTtl());
    }

    protected function identity(VoiceUser $user): string
    {
        return 'user-' . $user->userId;
    }

    /**
     * What every participant in the room can read about this one. Deliberately fixed and
     * small: it is visible to the other side of a call, unlike the dispatch metadata.
     */
    protected function participantMetadata(VoiceUser $user): array
    {
        return [
            'userId' => $user->userId,
            'locale' => $user->locale,
        ];
    }

}
