<?php

namespace Codatsoft\Realtime\Voice\Business;

use Codatsoft\Realtime\Voice\Database\VoiDatabase;
use Codatsoft\Realtime\Voice\Services\AgentApiTokens;
use Codatsoft\Realtime\Voice\Services\LiveKitService;
use Codatsoft\Realtime\Voice\Types\TVoiceCallStatus;
use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Illuminate\Support\Facades\Log;
use Livekit\WebhookEvent;
use Throwable;

/**
 * Guest-safe: LiveKit calls the webhook with no user session, so this class must not
 * depend on MUserBase or on anything that resolves the authenticated user.
 */
class VoiceWebhookActions
{
    public function __construct(protected VoiDatabase $db, protected LiveKitService $liveKit, protected AgentApiTokens $agentTokens)
    {

    }

    /**
     * Verifies the signature and returns the decoded event, or null when the body was not
     * signed by our LiveKit project.
     */
    public function receive(string $body, ?string $authorizationHeader): ?WebhookEvent
    {
        try
        {
            return $this->liveKit->receiveWebhook($body, $authorizationHeader);
        }
        catch (Throwable $e)
        {
            Log::warning('LiveKit webhook rejected', ['reason' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Room lifecycle hook. The match is the place to hang billing or presence once those
     * exist.
     */
    public function handle(WebhookEvent $event): void
    {
        $room = $event->getRoom()?->getName();
        $participant = $event->getParticipant()?->getIdentity();

        Log::info('LiveKit webhook', [
            'event'       => $event->getEvent(),
            'room'        => $room,
            'participant' => $participant,
        ]);

        match ($event->getEvent())
        {
            'room_finished' => $this->roomFinished($room),
            'participant_joined' => $this->participantJoined($room, $participant),
            'room_started',
            'participant_left' => null,
            default => null,
        };
    }

    /**
     * An agent room closing retires the Sanctum token the worker was given for it, so a
     * token only ever lives as long as the conversation it served. A call room closing
     * settles the call: an unanswered one was missed, an answered one has ended.
     */
    protected function roomFinished(?string $room): void
    {
        if (is_null($room))
        {
            return;
        }

        if ($this->isKind($room, TVoiceRoomKind::AGENT))
        {
            $revoked = $this->agentTokens->revokeForRoom($room);

            if ($revoked > 0)
            {
                Log::info('LiveKit agent token revoked', ['room' => $room, 'count' => $revoked]);
            }

            return;
        }

        if ($this->isKind($room, TVoiceRoomKind::CALL))
        {
            $call = $this->db->reader->callByRoom($room);

            if (is_null($call))
            {
                return;
            }

            $status = TVoiceCallStatus::from($call->status);

            if ($status === TVoiceCallStatus::RINGING)
            {
                $this->db->saver->setCallStatus($call, TVoiceCallStatus::MISSED);
            }
            elseif ($status === TVoiceCallStatus::ACTIVE)
            {
                $this->db->saver->setCallStatus($call, TVoiceCallStatus::ENDED);
            }
        }
    }

    /**
     * The callee entering a call room means the call was answered, even if the accept
     * request was lost on the way back. The caller entering changes nothing.
     */
    protected function participantJoined(?string $room, ?string $identity): void
    {
        if (is_null($room) || is_null($identity) || !$this->isKind($room, TVoiceRoomKind::CALL))
        {
            return;
        }

        $call = $this->db->reader->callByRoom($room);

        if (is_null($call) || TVoiceCallStatus::from($call->status) !== TVoiceCallStatus::RINGING)
        {
            return;
        }

        if ($identity === 'user-' . $call->callee_id)
        {
            $this->db->saver->setCallStatus($call, TVoiceCallStatus::ACTIVE);
        }
    }

    protected function isKind(string $room, TVoiceRoomKind $kind): bool
    {
        return str_starts_with($room, $kind->value . '-');
    }

}
