<?php

use Codatsoft\Realtime\Voice\Http\Controllers\VoiceWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Server-to-server webhook
|--------------------------------------------------------------------------
| No user middleware: LiveKit signs each request with the project's API
| secret, and the controller verifies that signature before anything runs.
| Point the LiveKit project's webhook at <prefix>/voice/webhook.
*/

Route::post('voice/webhook', [VoiceWebhookController::class, 'handle']);
