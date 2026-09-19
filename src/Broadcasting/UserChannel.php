<?php

namespace Codatsoft\Realtime\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;

/**
 * The one private channel a product's events broadcast on for a given user. Use it in
 * `broadcastOn()` so events and the channel authorization registered by the package
 * always agree on the name.
 */
final class UserChannel
{
    public static function name(string $userId): string
    {
        $pattern = (string) config('realtime.broadcasting.user_channel', 'user.{userId}');

        return str_replace('{userId}', $userId, $pattern);
    }

    public static function for(string $userId): PrivateChannel
    {
        return new PrivateChannel(self::name($userId));
    }

}
