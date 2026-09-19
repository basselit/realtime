<?php

namespace Codatsoft\Realtime\Testing;

use Codatsoft\Realtime\Voice\Services\AgentApiTokens;

/**
 * Stands in for the Sanctum-backed service so the suite never writes a real row to
 * `personal_access_tokens`.
 */
class FakeAgentApiTokens extends AgentApiTokens
{
    /** @var array<string, int> room name => user id the token was issued for */
    public array $issued = [];

    /** @var list<string> room names whose tokens were revoked, in order */
    public array $revoked = [];

    public function __construct()
    {

    }

    public function issueForCurrentUser(string $roomName): string
    {
        $userId = (int) auth('sanctum')->id();
        $this->issued[$roomName] = $userId;

        return 'fake-sanctum-token-for-user-' . $userId . '-room-' . $roomName;
    }

    public function revokeForRoom(string $roomName): int
    {
        $this->revoked[] = $roomName;

        return isset($this->issued[$roomName]) ? 1 : 0;
    }

}
