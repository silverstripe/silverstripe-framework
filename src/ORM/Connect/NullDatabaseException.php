<?php
namespace SilverStripe\ORM\Connect;

use LogicException;

/**
 * Exception thrown when a database operation is attempted on a `NullDatabase`.
 * @internal
 */
class NullDatabaseException extends LogicException
{
}
