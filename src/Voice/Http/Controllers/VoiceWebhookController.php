<?php

namespace Codatsoft\Realtime\Voice\Http\Controllers;

use Illuminate\Routing\Controller;
use Codatsoft\Realtime\Voice\Business\VoiceWebhookActions;
use Codatsoft\Codatbase\Http\HTResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Called by the LiveKit server, not by the app. Unauthenticated: the signature in the
 * Authorization header is the proof of origin, verified before anything else runs.
 */
class VoiceWebhookController extends Controller
{
    public function __construct(protected VoiceWebhookActions $actions)
    {

    }

    /**
     * LiveKit webhook
     *
     * Receives room and participant lifecycle events. Answers 401 when the signature does
     * not match this project's API secret. Not for use by the mobile client.
     */
    public function handle(Request $request): HTResponse|Response
    {
        $event = $this->actions->receive($request->getContent(), $request->header('Authorization'));

        if (is_null($event))
        {
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        $this->actions->handle($event);

        return new HTResponse();
    }

}
