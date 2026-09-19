<?php

namespace Codatsoft\Realtime\Voice\Http\Controllers;

use Illuminate\Routing\Controller;
use Codatsoft\Realtime\Voice\Business\VoiceActions;
use Codatsoft\Realtime\Voice\Business\VoiceCallActions;
use Codatsoft\Realtime\Voice\Exceptions\VoiceCallRefused;
use Codatsoft\Realtime\Voice\Http\Requests\HTReqVoiceToken;
use Codatsoft\Realtime\Voice\Types\TVoiceRoomKind;
use Codatsoft\Codatbase\Http\HTResponse;

class VoiceController extends Controller
{
    public function __construct(protected VoiceActions $actions, protected VoiceCallActions $calls)
    {

    }

    /**
     * Get a LiveKit join token
     *
     * Signs a short-lived token for the signed-in user. `kind` = `agent` opens a private
     * room with the voice assistant. `kind` = `call` starts a call to `peerUserId` and
     * returns the caller's token; prefer `POST /api/voice/calls`, which also returns the
     * call itself. The client connects to `url` with `token` through the LiveKit SDK.
     */
    public function token(HTReqVoiceToken $request): HTResponse
    {
        $res = new HTResponse();

        try
        {
            $res->data = match ($request->kind)
            {
                TVoiceRoomKind::AGENT => $this->actions->agentToken(),
                TVoiceRoomKind::CALL => $this->calls->invite($request->peerUserId)->token,
            };
        }
        catch (VoiceCallRefused $e)
        {
            $res->success = false;
            $res->message = $e->getMessage();
        }

        return $res;
    }

}
