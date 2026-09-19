<?php

namespace Codatsoft\Realtime\Support;

use Codatsoft\Realtime\Contracts\ResolvesVoiceUser;
use Codatsoft\Realtime\Voice\Models\VoiceUser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Default resolver: the user on the configured guard, with `name` (falling back to
 * `email`) and `locale` (falling back to `ar`) read straight off the model. Resolved
 * per call, never captured, so a long-lived controller can serve many users.
 */
class GuardVoiceUserResolver implements ResolvesVoiceUser
{
    public function __construct(protected AuthFactory $auth)
    {

    }

    public function current(): VoiceUser
    {
        $user = $this->auth->guard((string) config('realtime.guard', 'sanctum'))->user();

        if (is_null($user))
        {
            throw new AuthenticationException();
        }

        return new VoiceUser(
            (int) $user->getAuthIdentifier(),
            (string) ($user->name ?? $user->email ?? ''),
            (string) ($user->locale ?: 'ar'),
        );
    }

}
