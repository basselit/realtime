<?php

namespace Codatsoft\Realtime\Voice\Models\DBMapped;

use Codatsoft\Realtime\Voice\DBModels\VoiceCall;
use Codatsoft\Realtime\Voice\Types\TVoiceCallStatus;
use Codatsoft\Codatbase\Base\PBase;

/**
 * A call as the mobile client sees it. Dates are ISO 8601 strings in UTC.
 */
class TVoiceCall implements PBase
{
    public int $id;
    public string $roomName;
    public int $callerId;
    public int $calleeId;
    public TVoiceCallStatus $status;
    public ?string $startedAt = null;
    public ?string $endedAt = null;
    public string $createdAt;

    public static function fromModel(VoiceCall $model): TVoiceCall
    {
        $one = new self();
        $one->id = (int) $model->id;
        $one->roomName = $model->room_name;
        $one->callerId = (int) $model->caller_id;
        $one->calleeId = (int) $model->callee_id;
        $one->status = TVoiceCallStatus::from($model->status);
        $one->startedAt = $model->started_at?->toISOString();
        $one->endedAt = $model->ended_at?->toISOString();
        $one->createdAt = $model->created_at->toISOString();

        return $one;
    }

    public function isParty(int $userId): bool
    {
        return $userId === $this->callerId || $userId === $this->calleeId;
    }

    public function peerOf(int $userId): int
    {
        return $userId === $this->callerId ? $this->calleeId : $this->callerId;
    }

}
