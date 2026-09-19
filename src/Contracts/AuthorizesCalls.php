<?php

namespace Codatsoft\Realtime\Contracts;

use Codatsoft\Realtime\Voice\Models\VoiceUser;

/**
 * Whether one user may ring another. The default allows any signed-in user to call any
 * other; a product with contacts, roles or blocking binds its own rule. Self-calls and
 * busy lines are refused by the module before this is consulted.
 */
interface AuthorizesCalls
{
    public function mayCall(VoiceUser $caller, int $calleeId): bool;

}
