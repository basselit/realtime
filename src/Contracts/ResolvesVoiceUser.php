<?php

namespace Codatsoft\Realtime\Contracts;

use Codatsoft\Realtime\Voice\Models\VoiceUser;
use Illuminate\Auth\AuthenticationException;

/**
 * Who the voice module is acting for. The default reads the configured guard and the
 * `name` and `locale` attributes of the user model; a product with a different user
 * model binds its own implementation.
 */
interface ResolvesVoiceUser
{
    /**
     * @throws AuthenticationException when there is no signed-in user
     */
    public function current(): VoiceUser;

}
