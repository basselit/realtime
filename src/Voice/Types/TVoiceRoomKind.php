<?php

namespace Codatsoft\Realtime\Voice\Types;

/**
 * What a LiveKit room is for. The kind is the first segment of the room name
 * (`agent-…`, `call-…`) so the agent worker and the webhook handler can tell
 * them apart without a lookup.
 */
enum TVoiceRoomKind: string
{
    case AGENT = 'agent';
    case CALL = 'call';

}
