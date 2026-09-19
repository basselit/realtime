<?php

namespace Codatsoft\Realtime\Support;

use Codatsoft\Realtime\Contracts\BuildsDispatchMetadata;
use Codatsoft\Realtime\Voice\Models\VoiceUser;

/**
 * Default dispatch metadata: the user's id and locale, which every agent needs to
 * greet the right person in the right language.
 */
class DefaultDispatchMetadata implements BuildsDispatchMetadata
{
    public function build(VoiceUser $user, string $roomName): array
    {
        return [
            'userId' => $user->userId,
            'locale' => $user->locale,
        ];
    }

}
