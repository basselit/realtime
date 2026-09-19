<?php

namespace Codatsoft\Realtime\Voice\Business;

use Codatsoft\Realtime\Contracts\AuthorizesCalls;
use Codatsoft\Realtime\Contracts\ResolvesVoiceUser;
use Codatsoft\Realtime\Voice\Database\VoiDatabase;
use Codatsoft\Realtime\Voice\DBModels\VoiceCall;
use Codatsoft\Realtime\Voice\Exceptions\VoiceCallRefused;
use Codatsoft\Realtime\Voice\Http\Responses\HTResVoiceCall;
use Codatsoft\Realtime\Voice\Http\Responses\HTResVoiceToken;
use Codatsoft\Realtime\Voice\Models\DBMapped\TVoiceCall;
use Codatsoft\Realtime\Voice\Models\DBMapped\TVoiceCalls;
use Codatsoft\Realtime\Voice\Services\CallRooms;
use Codatsoft\Realtime\Voice\Types\TVoiceCallStatus;
use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Illuminate\Support\Str;

/**
 * User-to-user calls. The `voice_calls` row is the call; LiveKit only carries the audio.
 *
 * Signalling is polling: the callee's app asks `current()` every few seconds and shows the
 * incoming-call screen when a ringing call names it as callee. Nothing here pushes to a
 * device, so swapping polling for push later touches only the client and a notifier.
 *
 * A user has at most one open call (ringing or active) at a time, in either role.
 */
class VoiceCallActions
{
    public function __construct(
        protected VoiDatabase $db,
        protected ResolvesVoiceUser $users,
        protected AuthorizesCalls $policy,
        protected VoiceActions $tokens,
        protected CallRooms $rooms,
    ) {}

    /**
     * Caller side: opens a ringing call to the peer and returns the caller's join token, so
     * the caller is already in the room when the callee picks up.
     */
    public function invite(int $peerUserId): HTResVoiceCall
    {
        $user = $this->users->current();

        if ($peerUserId === $user->userId)
        {
            throw new VoiceCallRefused('You cannot call yourself');
        }

        if (!$this->policy->mayCall($user, $peerUserId))
        {
            throw new VoiceCallRefused('You cannot call this user');
        }

        $this->expireStaleRings();

        if (!is_null($this->db->reader->openCallForUser($user->userId)))
        {
            throw new VoiceCallRefused('You already have a call open');
        }

        if (!is_null($this->db->reader->openCallForUser($peerUserId)))
        {
            throw new VoiceCallRefused('The other user is busy');
        }

        $roomName = TVoiceRoomKind::CALL->value . '-' . min($user->userId, $peerUserId) . '-' . max($user->userId, $peerUserId) . '-' . Str::lower(Str::random(8));

        $call = $this->db->saver->createCall($roomName, $user->userId, $peerUserId);

        return $this->respond($call, $user->userId, $this->tokens->callRoomToken($roomName));
    }

    /**
     * What the app polls: the user's open call, if any, with no token. A ringing call where
     * the user is the callee is an incoming call; the app then calls accept() or decline().
     */
    public function current(): ?HTResVoiceCall
    {
        $user = $this->users->current();

        $this->expireStaleRings();

        $call = $this->db->reader->openCallForUser($user->userId);

        return is_null($call) ? null : $this->respond($call, $user->userId, null);
    }

    /**
     * Callee side: answers a ringing call and gets the join token for it.
     */
    public function accept(int $callId): HTResVoiceCall
    {
        $user = $this->users->current();
        $call = $this->callForParty($callId, $user->userId);

        if ((int) $call->callee_id !== $user->userId)
        {
            throw new VoiceCallRefused('Only the person being called can accept');
        }

        $this->mustBe($call, TVoiceCallStatus::RINGING, 'This call is no longer ringing');

        $call = $this->db->saver->setCallStatus($call, TVoiceCallStatus::ACTIVE);

        return $this->respond($call, $user->userId, $this->tokens->callRoomToken($call->room_name));
    }

    /**
     * Callee side: turns a ringing call down. The room is closed so the waiting caller is
     * disconnected right away.
     */
    public function decline(int $callId): HTResVoiceCall
    {
        $user = $this->users->current();
        $call = $this->callForParty($callId, $user->userId);

        if ((int) $call->callee_id !== $user->userId)
        {
            throw new VoiceCallRefused('Only the person being called can decline');
        }

        $this->mustBe($call, TVoiceCallStatus::RINGING, 'This call is no longer ringing');

        return $this->close($call, TVoiceCallStatus::DECLINED, $user->userId);
    }

    /**
     * Either side ends the call. While it is still ringing, the caller hanging up cancels
     * it and the callee hanging up counts as a decline; once active it is simply ended.
     */
    public function hangup(int $callId): HTResVoiceCall
    {
        $user = $this->users->current();
        $call = $this->callForParty($callId, $user->userId);
        $status = TVoiceCallStatus::from($call->status);

        if (!$status->isOpen())
        {
            throw new VoiceCallRefused('This call has already ended');
        }

        $next = match (true)
        {
            $status === TVoiceCallStatus::ACTIVE => TVoiceCallStatus::ENDED,
            (int) $call->caller_id === $user->userId => TVoiceCallStatus::CANCELLED,
            default => TVoiceCallStatus::DECLINED,
        };

        return $this->close($call, $next, $user->userId);
    }

    /**
     * A fresh join token for an open call the user is part of: for a caller that lost its
     * token, or a client reconnecting after a network drop. A callee must accept first.
     */
    public function joinToken(int $callId): HTResVoiceCall
    {
        $user = $this->users->current();
        $call = $this->callForParty($callId, $user->userId);
        $status = TVoiceCallStatus::from($call->status);

        if (!$status->isOpen())
        {
            throw new VoiceCallRefused('This call has already ended');
        }

        if ($status === TVoiceCallStatus::RINGING && (int) $call->callee_id === $user->userId)
        {
            throw new VoiceCallRefused('Accept the call first');
        }

        return $this->respond($call, $user->userId, $this->tokens->callRoomToken($call->room_name));
    }

    public function history(int $limit = 50): TVoiceCalls
    {
        $user = $this->users->current();

        return TVoiceCalls::fromModels($this->db->reader->callsForUser($user->userId, max(1, min($limit, 200))));
    }

    /**
     * Rings nobody answered within the window are missed. Run before every read or write
     * so a stale ring never blocks a new call or shows up as incoming.
     */
    protected function expireStaleRings(): void
    {
        // A missing key (stale config cache) must never mean "expire at once".
        $timeout = (int) config('realtime.livekit.rooms.call_ring_timeout', 45);

        $this->db->saver->expireRingingCalls($timeout > 0 ? $timeout : 45);
    }

    /**
     * A call the user is caller or callee of. A call that exists but belongs to other
     * people answers the same as one that does not exist.
     */
    protected function callForParty(int $callId, int $userId): VoiceCall
    {
        $this->expireStaleRings();

        $call = $this->db->reader->callById($callId);

        if (is_null($call) || ((int) $call->caller_id !== $userId && (int) $call->callee_id !== $userId))
        {
            throw new VoiceCallRefused('No such call');
        }

        return $call;
    }

    protected function mustBe(VoiceCall $call, TVoiceCallStatus $status, string $otherwise): void
    {
        if (TVoiceCallStatus::from($call->status) !== $status)
        {
            throw new VoiceCallRefused($otherwise);
        }
    }

    protected function close(VoiceCall $call, TVoiceCallStatus $status, int $userId): HTResVoiceCall
    {
        $call = $this->db->saver->setCallStatus($call, $status);
        $this->rooms->close($call->room_name);

        return $this->respond($call, $userId, null);
    }

    protected function respond(VoiceCall $call, int $userId, ?HTResVoiceToken $token): HTResVoiceCall
    {
        return HTResVoiceCall::from(TVoiceCall::fromModel($call), $userId, $token);
    }

}
