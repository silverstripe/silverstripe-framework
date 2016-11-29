<?php

namespace SilverStripe\Security;

use Exception;

/**
 * Throw this exception to register that a user doesn't have permission to do the given action
 * and potentially redirect them to the log-in page.  The exception message may be presented to the
 * user, so it shouldn't be in nerd-speak.
 */
class PermissionFailureException extends Exception
{

}
