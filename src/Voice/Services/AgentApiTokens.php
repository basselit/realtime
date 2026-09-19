<?php

namespace Codatsoft\Realtime\Voice\Services;

use Codatsoft\Realtime\Voice\Database\VoiDatabase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * The Sanctum token the agent worker uses to call this API as the user. One token per
 * agent room, named after it, so the room finishing is enough to find and retire it.
 *
 * Kept behind its own class so the feature tests can swap it out: creating a real token
 * writes to `personal_access_tokens`, and the suite must never leave rows behind.
 */
class AgentApiTokens
{
    public const string NAME_PREFIX = 'voice-agent:';

    public function __construct(protected VoiDatabase $db, protected AuthFactory $auth)
    {

    }

    /**
     * Issues a token for the signed-in user, named after the room it is for.
     */
    public function issueForCurrentUser(string $roomName): string
    {
        $user = $this->auth->guard(config('realtime.guard', 'sanctum'))->user();

        // createToken() comes from the HasApiTokens trait on the User model.
        if (is_null($user) || !method_exists($user, 'createToken'))
        {
            throw new AuthenticationException();
        }

        return $user->createToken(self::NAME_PREFIX . $roomName)->plainTextToken;
    }

    /**
     * Retires every live token issued for the room. Returns how many were revoked.
     */
    public function revokeForRoom(string $roomName): int
    {
        return $this->db->saver->revokeApiTokensNamed(self::NAME_PREFIX . $roomName);
    }

}
