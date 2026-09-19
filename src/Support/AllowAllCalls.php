<?php

namespace Codatsoft\Realtime\Support;

use Codatsoft\Realtime\Contracts\AuthorizesCalls;
use Codatsoft\Realtime\Voice\Models\VoiceUser;

/**
 * Default rule: any signed-in user may ring any existing user.
 */
class AllowAllCalls implements AuthorizesCalls
{
    public function mayCall(VoiceUser $caller, int $calleeId): bool
    {
        return true;
    }

}
