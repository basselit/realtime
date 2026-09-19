<?php

namespace Codatsoft\Realtime\Voice\Http\Responses;

use Codatsoft\Realtime\Voice\Models\DBMapped\TVoiceCall;
use Codatsoft\Codatbase\Base\PBase;
use JsonSerializable;

/**
 * A call from the point of view of the user who asked: which side they are on, who the
 * other person is, and, when the action implies joining the room, a join token.
 */
class HTResVoiceCall implements PBase, JsonSerializable
{
    public const string ROLE_CALLER = 'caller';
    public const string ROLE_CALLEE = 'callee';

    public TVoiceCall $call;

    /** `caller` or `callee`. */
    public string $role;

    public int $peerUserId;

    /** Present after invite, accept and token; absent on a poll, decline or hangup. */
    public ?HTResVoiceToken $token = null;

    public static function from(TVoiceCall $call, int $userId, ?HTResVoiceToken $token): HTResVoiceCall
    {
        $value = new self();
        $value->call = $call;
        $value->role = $userId === $call->callerId ? self::ROLE_CALLER : self::ROLE_CALLEE;
        $value->peerUserId = $call->peerOf($userId);
        $value->token = $token;

        return $value;
    }

    /**
     * The token key is left out rather than sent as null, so the app can test for its
     * presence instead of its value.
     */
    public function jsonSerialize(): array
    {
        $out = [
            'call'       => $this->call,
            'role'       => $this->role,
            'peerUserId' => $this->peerUserId,
        ];

        if (!is_null($this->token))
        {
            $out['token'] = $this->token;
        }

        return $out;
    }

}
