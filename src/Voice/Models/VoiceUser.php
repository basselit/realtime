<?php

namespace Codatsoft\Realtime\Voice\Models;

/**
 * The signed-in user as the voice module sees them: three fields, nothing else. Built
 * by the product's ResolvesVoiceUser implementation. The integer id is what room names
 * and participant identities are built from.
 */
final readonly class VoiceUser
{
    public function __construct(
        public int $userId,
        public string $userName,
        public string $locale,
    ) {}

}
