<?php

namespace Codatsoft\Realtime\Voice\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\RoomConfiguration;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\WebhookReceiver;
use Livekit\WebhookEvent;
use RuntimeException;

/**
 * The only class in the app that imports the LiveKit SDK. Business code goes through
 * this wrapper, so replacing the community package is a one-file change.
 */
class LiveKitService
{
    protected string $url;
    protected string $apiKey;
    protected string $apiSecret;
    protected int $tokenTtl;

    public function __construct()
    {
        $this->url = (string) config('realtime.livekit.url');
        $this->apiKey = (string) config('realtime.livekit.api_key');
        $this->apiSecret = (string) config('realtime.livekit.api_secret');
        $this->tokenTtl = (int) config('realtime.livekit.token_ttl');

        if ($this->url === '' || $this->apiKey === '' || $this->apiSecret === '')
        {
            throw new RuntimeException('LiveKit is not configured: set LIVEKIT_URL, LIVEKIT_API_KEY and LIVEKIT_API_SECRET.');
        }
    }

    /**
     * The WebSocket URL the mobile client connects to.
     */
    public function url(): string
    {
        return $this->url;
    }

    public function tokenTtl(): int
    {
        return $this->tokenTtl;
    }

    /**
     * Signs a join token for one participant in one room.
     *
     * @param array<string, mixed> $metadata JSON-encoded onto the participant; the agent
     *                                       worker and other participants can read it.
     */
    public function joinToken(string $identity, string $displayName, string $roomName, VideoGrant $grant, array $metadata = [], ?RoomConfiguration $roomConfig = null): string
    {
        $options = (new AccessTokenOptions())
            ->setIdentity($identity)
            ->setName($displayName)
            ->setTtl($this->tokenTtl);

        if ($metadata !== [])
        {
            $options->setMetadata(json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if (!is_null($roomConfig))
        {
            $options->setRoomConfig($roomConfig);
        }

        $grant->setRoomJoin();
        $grant->setRoomName($roomName);

        return (new AccessToken($this->apiKey, $this->apiSecret, $options))
            ->setGrant($grant)
            ->toJwt();
    }

    /**
     * Verifies the webhook signature and decodes the event. Throws on a bad or missing
     * signature, so a caller that gets an event back can trust it came from LiveKit.
     *
     * @throws \Exception
     */
    public function receiveWebhook(string $body, ?string $authorizationHeader): WebhookEvent
    {
        return (new WebhookReceiver($this->apiKey, $this->apiSecret))->receive($body, $authorizationHeader);
    }

    /**
     * Server-side room control (list, delete, kick, mute…). Lazily built: most requests
     * only sign a token and never touch the HTTP API.
     */
    public function rooms(): RoomServiceClient
    {
        return new RoomServiceClient($this->apiHost(), $this->apiKey, $this->apiSecret);
    }

    /**
     * The room API is plain HTTPS on the same host the clients reach over WebSocket.
     */
    protected function apiHost(): string
    {
        return preg_replace('/^ws(s?):\/\//', 'http$1://', $this->url);
    }

}
