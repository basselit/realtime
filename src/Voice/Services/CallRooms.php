<?php

namespace Codatsoft\Realtime\Voice\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Closes a call's LiveKit room when the call ends through the API, so the other side
 * is disconnected at once instead of sitting alone until the empty timeout. Best effort:
 * the call record is already closed when this runs, and a room that no longer exists
 * (nobody ever joined, or LiveKit already timed it out) is not an error.
 *
 * Its own class so the feature tests can swap it out: there is no LiveKit server in the
 * suite, and the room API call would hang on a fake host.
 */
class CallRooms
{
    public function __construct(protected LiveKitService $liveKit)
    {

    }

    public function close(string $roomName): void
    {
        try
        {
            $this->liveKit->rooms()->deleteRoom($roomName);
        }
        catch (Throwable $e)
        {
            Log::info('LiveKit call room not closed', ['room' => $roomName, 'reason' => $e->getMessage()]);
        }
    }

}
