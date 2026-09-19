<?php

namespace Codatsoft\Realtime\Contracts;

use Codatsoft\Realtime\Voice\Models\VoiceUser;

/**
 * What the agent worker learns about the user it is about to talk to. Travels only to
 * the worker through the room's agent dispatch; the module appends the worker's API
 * token itself. The default sends `userId` and `locale`.
 *
 * @return array<string, mixed> JSON-encodable
 */
interface BuildsDispatchMetadata
{
    public function build(VoiceUser $user, string $roomName): array;

}
