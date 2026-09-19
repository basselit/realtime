<?php

namespace Codatsoft\Realtime\Voice\Http\Responses;

use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Codatsoft\Codatbase\Base\PBase;

/**
 * Everything the mobile client needs to join a LiveKit room: where to connect, as whom,
 * and until when the token is accepted.
 */
class HTResVoiceToken implements PBase
{
    /** WebSocket URL of the LiveKit server. */
    public string $url;

    /** Signed join token; the client passes it to the LiveKit SDK as-is. */
    public string $token;

    public string $roomName;
    public TVoiceRoomKind $kind;

    /** The participant identity the token was signed for (`user-<id>`). */
    public string $identity;

    /** Unix timestamp after which the token can no longer be used to join. */
    public int $expiresAt;

    public static function from(string $url, string $token, string $roomName, TVoiceRoomKind $kind, string $identity, int $expiresAt): HTResVoiceToken
    {
        $value = new self();
        $value->url = $url;
        $value->token = $token;
        $value->roomName = $roomName;
        $value->kind = $kind;
        $value->identity = $identity;
        $value->expiresAt = $expiresAt;

        return $value;
    }

}
