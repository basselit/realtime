# codatsoft/realtime

Voice rooms with a LiveKit agent, user-to-user calls, and the realtime channel
conventions shared by Codatsoft Laravel products. One implementation, installed into
each product; each product keeps its own LiveKit project, Ably app and agent worker.

## Install

The package is a path repository, like codatbase. In the product's `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "../../Packages/Realtime/realtime", "options": { "symlink": false } }
],
"require": { "codatsoft/realtime": "^1.0" }
```

Then `composer update codatsoft/realtime`. The service provider is auto-discovered.

`.env` needs the LiveKit project keys and the agent name, plus Ably for broadcasting:

```dotenv
LIVEKIT_URL=wss://<project>.livekit.cloud
LIVEKIT_API_KEY=
LIVEKIT_API_SECRET=
LIVEKIT_AGENT_NAME=          # must equal the worker's agent name
BROADCAST_CONNECTION=ably
ABLY_KEY=
```

Run `php artisan migrate` for the `voice_calls` table. After changing config on a
server, run `config:cache` and `route:cache` again.

## What it mounts

| Route | Purpose |
|---|---|
| `POST <prefix>/voice/token` | Agent-room join token (`kind: agent`) or start a call (`kind: call`) |
| `POST <prefix>/voice/calls` | Ring another user; returns the caller's join token |
| `GET <prefix>/voice/calls/current` | The signed-in user's open call, for polling |
| `POST <prefix>/voice/calls/{id}/accept|decline|hangup|token` | Call lifecycle |
| `GET <prefix>/voice/calls` | Call history |
| `POST <prefix>/voice/webhook` | LiveKit room webhook, signature-verified, no session |

`<prefix>` and the middleware come from `config/realtime.php` (`api` and
`['api', 'auth:sanctum']` by default). Publish the config with
`php artisan vendor:publish --tag=realtime-config` to change them.

It also registers the private broadcast channel `user.{userId}`, authorized when the
parameter equals the user's `web_id` (both configurable). Events should build their
channel with `Codatsoft\Realtime\Broadcasting\UserChannel::for($webId)` so the name and
the authorization never drift apart.

`php artisan voice:token <user id or email> [--json] [--meet]` issues an agent-room
token without going through HTTP, for testing the worker.

## Extension points

Bind any of these in the product's service provider to change behaviour; the defaults
match a product with a Sanctum guard and a user model carrying `name` and `locale`.

| Contract | Default | Override when |
|---|---|---|
| `Contracts\ResolvesVoiceUser` | `Support\GuardVoiceUserResolver` | the user model differs, or identity comes from elsewhere |
| `Contracts\AuthorizesCalls` | `Support\AllowAllCalls` | only contacts, roles or non-blocked users may be rung |
| `Contracts\BuildsDispatchMetadata` | `Support\DefaultDispatchMetadata` | the agent needs more than `userId` and `locale` |

## Testing in a product

`Codatsoft\Realtime\Testing` ships `FakeAgentApiTokens` and `FakeCallRooms`. Bind them
in the test base so the suite never mints real API tokens or calls the LiveKit room API:

```php
$this->app->instance(AgentApiTokens::class, new FakeAgentApiTokens());
$this->app->instance(CallRooms::class, new FakeCallRooms());
```

Sign the LiveKit config with fake keys (`realtime.livekit.*`) and decode the tokens with
`firebase/php-jwt` to assert their claims. The apiCodatsoft suite under
`tests/Feature/Voice` is the reference.

Ready-to-run HTTP requests for every endpoint are in `docs/http/`.
