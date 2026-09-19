<?php

namespace Codatsoft\Realtime\Voice\Http\Controllers;

use Illuminate\Routing\Controller;
use Codatsoft\Realtime\Voice\Business\VoiceCallActions;
use Codatsoft\Realtime\Voice\Exceptions\VoiceCallRefused;
use Codatsoft\Realtime\Voice\Http\Requests\HTReqVoiceCallInvite;
use Closure;
use Codatsoft\Codatbase\Http\HTResponse;
use Illuminate\Http\Request;

/**
 * User-to-user calls. Every answer is an HTResponse: a refused action comes back with
 * `success: false` and a message the app can show, never as an HTTP error.
 */
class VoiceCallController extends Controller
{
    public function __construct(protected VoiceCallActions $actions)
    {

    }

    /**
     * Start a call
     *
     * Opens a ringing call to `peerUserId` and returns the caller's join token. Refused
     * when either side already has a call open.
     */
    public function invite(HTReqVoiceCallInvite $request): HTResponse
    {
        return $this->respond(fn () => $this->actions->invite($request->peerUserId));
    }

    /**
     * Current call
     *
     * The signed-in user's open call, if any, without a token. Poll this every few seconds:
     * a `ringing` call with `role` = `callee` is an incoming call. No `data` means no call.
     */
    public function current(): HTResponse
    {
        return $this->respond(fn () => $this->actions->current());
    }

    /**
     * Accept a call
     *
     * Callee only. Marks the call active and returns the callee's join token.
     */
    public function accept(int $id): HTResponse
    {
        return $this->respond(fn () => $this->actions->accept($id));
    }

    /**
     * Decline a call
     *
     * Callee only, while ringing. Closes the room so the caller is disconnected.
     */
    public function decline(int $id): HTResponse
    {
        return $this->respond(fn () => $this->actions->decline($id));
    }

    /**
     * Hang up
     *
     * Either side. Cancels a ringing call (caller) or ends an active one; closes the room.
     */
    public function hangup(int $id): HTResponse
    {
        return $this->respond(fn () => $this->actions->hangup($id));
    }

    /**
     * Rejoin token
     *
     * A fresh join token for an open call the user is part of, for reconnecting.
     */
    public function token(int $id): HTResponse
    {
        return $this->respond(fn () => $this->actions->joinToken($id));
    }

    /**
     * Call history
     *
     * The user's most recent calls in both roles, newest first. `limit` defaults to 50.
     */
    public function history(Request $request): HTResponse
    {
        $limit = (int) $request->query('limit', 50);

        return $this->respond(fn () => $this->actions->history($limit));
    }

    protected function respond(Closure $action): HTResponse
    {
        $res = new HTResponse();

        try
        {
            $res->data = $action();
        }
        catch (VoiceCallRefused $e)
        {
            $res->success = false;
            $res->message = $e->getMessage();
        }

        return $res;
    }

}
