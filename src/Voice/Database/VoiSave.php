<?php

namespace Codatsoft\Realtime\Voice\Database;

use Codatsoft\Realtime\Voice\DBModels\VoiceCall;
use Codatsoft\Realtime\Voice\Types\TVoiceCallStatus;
use Codatsoft\Codatbase\Database\TDBSave;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

class VoiSave extends TDBSave
{
    public function __construct(protected VoiConvert $converter, string $mapper)
    {
        parent::__construct($mapper);
    }

    /**
     * Deletes every Sanctum token carrying this name. Sanctum has no "revoked" flag:
     * a deleted row is a dead token. Used to retire the agent worker's token once its
     * room has finished. Returns how many were removed.
     */
    public function revokeApiTokensNamed(string $name): int
    {
        $tokenModel = Sanctum::personalAccessTokenModel();

        return $tokenModel::query()
            ->where('name', $name)
            ->delete();
    }

    public function createCall(string $roomName, int $callerId, int $calleeId): VoiceCall
    {
        return VoiceCall::query()->create([
            'room_name' => $roomName,
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'status'    => TVoiceCallStatus::RINGING->value,
        ]);
    }

    /**
     * Moves a call to its next state. `active` stamps started_at; every closed state
     * stamps ended_at. Returns the refreshed row.
     */
    public function setCallStatus(VoiceCall $call, TVoiceCallStatus $status): VoiceCall
    {
        $now = Carbon::now();
        $values = ['status' => $status->value];

        if ($status === TVoiceCallStatus::ACTIVE)
        {
            $values['started_at'] = $now;
        }
        elseif (!$status->isOpen())
        {
            $values['ended_at'] = $now;
        }

        $call->fill($values)->save();

        return $call->refresh();
    }

    /**
     * Rings that nobody answered within the window become missed. Returns how many.
     */
    public function expireRingingCalls(int $ringTimeoutSeconds): int
    {
        $now = Carbon::now();

        return VoiceCall::query()
            ->where('status', TVoiceCallStatus::RINGING->value)
            ->where('created_at', '<', $now->copy()->subSeconds($ringTimeoutSeconds))
            ->update(['status' => TVoiceCallStatus::MISSED->value, 'ended_at' => $now, 'updated_at' => $now]);
    }

}
