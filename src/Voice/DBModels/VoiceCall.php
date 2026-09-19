<?php

namespace Codatsoft\Realtime\Voice\DBModels;

use Illuminate\Database\Eloquent\Model;

class VoiceCall extends Model
{
    protected $table = 'voice_calls';

    protected $fillable = ['room_name', 'caller_id', 'callee_id', 'status', 'started_at', 'ended_at'];

    protected $casts = [
        'caller_id'  => 'integer',
        'callee_id'  => 'integer',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
