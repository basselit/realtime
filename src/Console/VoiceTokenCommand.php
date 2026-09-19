<?php

namespace Codatsoft\Realtime\Console;

use Codatsoft\Realtime\Voice\Business\VoiceActions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Issues an agent-room LiveKit join token for a user, the same way `POST /voice/token`
 * does, without going through HTTP. Meant for testing the voice agent from a test page
 * or LiveKit Meet without the mobile app.
 *
 * Like the HTTP route, each call opens a new room and mints one API token for the agent
 * worker (`voice-agent:<room>`), which the LiveKit webhook revokes when the room ends.
 */
class VoiceTokenCommand extends Command
{
    protected $signature = 'voice:token
                            {user=1 : The user id or email address to issue the token for}
                            {--json : Print the response as JSON only, for piping to a file}
                            {--meet : Also print a LiveKit Meet URL that joins the room}';

    protected $description = 'Issue an agent-room LiveKit join token for a user, as POST /voice/token would';

    public function handle(VoiceActions $voice): int
    {
        $lookup = (string) $this->argument('user');
        $model = (string) config('auth.providers.users.model');

        $user = is_numeric($lookup)
            ? $model::find((int) $lookup)
            : $model::where('email', $lookup)->first();

        if (is_null($user))
        {
            $this->error("No user with id or email '{$lookup}'.");

            return self::FAILURE;
        }

        // The actions read the signed-in user from the configured guard, exactly as the route does.
        Auth::guard((string) config('realtime.guard', 'sanctum'))->setUser($user);

        try
        {
            $res = $voice->agentToken();
        }
        catch (Throwable $e)
        {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $data = [
            'url'       => $res->url,
            'token'     => $res->token,
            'roomName'  => $res->roomName,
            'kind'      => $res->kind->value,
            'identity'  => $res->identity,
            'expiresAt' => $res->expiresAt,
        ];

        if ($this->option('json'))
        {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['User', $user->getAuthIdentifier() . ' ' . ($user->name ?? $user->email)],
            ['Room', $res->roomName],
            ['Identity', $res->identity],
            ['Expires', date('c', $res->expiresAt)],
            ['URL', $res->url],
        ]);

        $this->newLine();
        $this->info('Token:');
        $this->line($res->token);

        if ($this->option('meet'))
        {
            $this->newLine();
            $this->info('LiveKit Meet:');
            $this->line('https://meet.livekit.io/custom?liveKitUrl=' . rawurlencode($res->url) . '&token=' . rawurlencode($res->token));
        }

        $this->newLine();
        $this->comment('Use a new token for every attempt: the agent is dispatched only when the room is first created.');

        return self::SUCCESS;
    }
}
