<?php

namespace Codatsoft\Realtime\Voice\Database;

use Codatsoft\Realtime\Voice\DBModels\VoiceCall;
use Codatsoft\Realtime\Voice\Types\TVoiceCallStatus;
use Codatsoft\Codatbase\Database\TDBRead;
use Illuminate\Support\Collection;

class VoiRead extends TDBRead
{
    public function __construct(protected VoiConvert $converter, string $mapper)
    {
        parent::__construct($mapper);
    }

    public function callById(int $id): ?VoiceCall
    {
        return VoiceCall::query()->find($id);
    }

    public function callByRoom(string $roomName): ?VoiceCall
    {
        return VoiceCall::query()->where('room_name', $roomName)->first();
    }

    /**
     * The one ringing or active call the user is part of, in either role. Null when free.
     */
    public function openCallForUser(int $userId): ?VoiceCall
    {
        return VoiceCall::query()
            ->whereIn('status', TVoiceCallStatus::openValues())
            ->where(fn ($q) => $q->where('caller_id', $userId)->orWhere('callee_id', $userId))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Most recent calls first, both roles, open ones included.
     */
    public function callsForUser(int $userId, int $limit): Collection
    {
        return VoiceCall::query()
            ->where(fn ($q) => $q->where('caller_id', $userId)->orWhere('callee_id', $userId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

}
