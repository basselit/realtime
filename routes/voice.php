<?php

use Codatsoft\Realtime\Voice\Http\Controllers\VoiceCallController;
use Codatsoft\Realtime\Voice\Http\Controllers\VoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Voice: LiveKit tokens and user-to-user calls for the signed-in user
|--------------------------------------------------------------------------
| Mounted by RealtimeServiceProvider under config('realtime.routes.prefix')
| with config('realtime.routes.middleware').
*/

Route::prefix('voice')
    ->group(function (): void {
        Route::post('token', [VoiceController::class, 'token']);

        Route::prefix('calls')
            ->group(function (): void {
                Route::post('', [VoiceCallController::class, 'invite']);
                Route::get('', [VoiceCallController::class, 'history']);
                Route::get('current', [VoiceCallController::class, 'current']);
                Route::post('{id}/accept', [VoiceCallController::class, 'accept'])->whereNumber('id');
                Route::post('{id}/decline', [VoiceCallController::class, 'decline'])->whereNumber('id');
                Route::post('{id}/hangup', [VoiceCallController::class, 'hangup'])->whereNumber('id');
                Route::post('{id}/token', [VoiceCallController::class, 'token'])->whereNumber('id');
            });
    });
