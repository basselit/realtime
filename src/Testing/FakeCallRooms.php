<?php

namespace Codatsoft\Realtime\Testing;

use Codatsoft\Realtime\Voice\Services\CallRooms;

/**
 * Stands in for the LiveKit room API so ending a call in the suite never makes a network
 * request. Records which rooms were asked to close, in order.
 */
class FakeCallRooms extends CallRooms
{
    /** @var list<string> */
    public array $closed = [];

    public function __construct()
    {

    }

    public function close(string $roomName): void
    {
        $this->closed[] = $roomName;
    }

}
