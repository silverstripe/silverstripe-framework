<?php

namespace SilverStripe\ORM;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;

/**
 * Exception thrown by {@link DataObject}::write if validation fails. By throwing an
 * exception rather than a user error, the exception can be caught in unit tests and as such
 * can be used as a successful test.
 */
class ValidationException extends Exception
{
    use Injectable;

    /**
     * The contained ValidationResult related to this error
     *
     * @var ValidationResult
     */
    protected $result;

    /**
     * Construct a new ValidationException with an optional ValidationResult object
     *
     * @param ValidationResult|string $result The ValidationResult containing the
     * failed result, or error message to build error from
     * @param integer $code The error code number
     */
    public function __construct($result = null, $code = 0)
    {
        // Catch legacy behaviour where second argument was not code
        if ($code && !is_numeric($code)) {
            throw new InvalidArgumentException("Code must be numeric");
        }

        // Set default message and result
        $exceptionMessage = _t("SilverStripe\\ORM\\ValidationException.DEFAULT_ERROR", "Validation error");
        if (!$result) {
            $result = $exceptionMessage;
        }

        // Check result type
        if ($result instanceof ValidationResult) {
            $this->result = $result;
            // Pick first message
            foreach ($result->getMessages() as $message) {
                $exceptionMessage = $message['message'];
                break;
            }
        } elseif (is_string($result)) {
            $this->result = ValidationResult::create()->addError($result);
            $exceptionMessage = $result;
        } else {
            throw new InvalidArgumentException(
                "ValidationExceptions must be passed a ValdiationResult, a string, or nothing at all"
            );
        }

        parent::__construct($exceptionMessage, $code);
    }

    /**
     * Retrieves the ValidationResult related to this error
     *
     * @return ValidationResult
     */
    public function getResult()
    {
        return $this->result;
    }
}
