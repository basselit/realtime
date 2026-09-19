<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per user-to-user voice call. The row is the source of truth for the call's
 * state; LiveKit only carries the audio. The callee learns about a ringing call by
 * polling `GET /api/voice/calls/current`, so nothing here depends on push or sockets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_calls', function (Blueprint $table) {
            $table->id();
            $table->string('room_name', 64);
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('callee_id');
            // ringing | active | ended | declined | missed | cancelled (TVoiceCallStatus)
            $table->string('status', 20);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->unique('room_name', 'uq_voice_calls_room');
            $table->index(['caller_id', 'status'], 'idx_voice_calls_caller_status');
            $table->index(['callee_id', 'status'], 'idx_voice_calls_callee_status');

            $table->foreign('caller_id', 'fk_voice_calls_caller')->references('id')->on(config('realtime.users_table', 'users'))->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('callee_id', 'fk_voice_calls_callee')->references('id')->on(config('realtime.users_table', 'users'))->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_calls');
    }
};
