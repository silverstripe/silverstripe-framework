<?php
/**
 * Throw this exception to register that a user doesn't have permission to do the given action
 * and potentially redirect them to the log-in page.  The exception message may be presented to the
 * user, so it shouldn't be in nerd-speak.
 *
 * @package framework
 * @subpackage security
 */
class PermissionFailureException extends Exception {

}
