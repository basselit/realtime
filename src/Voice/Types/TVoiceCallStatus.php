<?php

namespace Codatsoft\Realtime\Voice\Types;

/**
 * Lifecycle of a user-to-user call. `ringing` and `active` are the open states: a user
 * has at most one open call at a time, in either role.
 *
 *   ringing --accept--> active --hangup / room_finished--> ended
 *      |
 *      +-- decline (callee) ----------------> declined
 *      +-- hangup (caller) -----------------> cancelled
 *      +-- ring timeout / room_finished ----> missed
 */
enum TVoiceCallStatus: string
{
    case RINGING = 'ringing';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case DECLINED = 'declined';
    case MISSED = 'missed';
    case CANCELLED = 'cancelled';

    public function isOpen(): bool
    {
        return $this === self::RINGING || $this === self::ACTIVE;
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::RINGING->value, self::ACTIVE->value];
    }

}
