<?php

namespace Codatsoft\Realtime\Voice\Exceptions;

use RuntimeException;

/**
 * A call request the business rules turn down: calling yourself, calling while a call
 * is open, acting on a call you are not part of, or one that is no longer in the state
 * the action needs. The message is safe to show to the user.
 */
class VoiceCallRefused extends RuntimeException
{

}
