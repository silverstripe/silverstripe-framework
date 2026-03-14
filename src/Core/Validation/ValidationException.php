<?php

namespace SilverStripe\Core\Validation;

use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Control\Director;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;

/**
 * Exception thrown by {@link DataObject}::write if validation fails. By throwing an
 * exception rather than a user error, the exception can be caught in unit tests and as such
 * can be used as a successful test.
 */
class ValidationException extends Exception
{
    use Injectable;
    use Configurable;

    /**
     * List of controllers to show additional info when not in CLI
     * Subclasses of these controllers will also show additional info
     */
    private static array $show_additional_info_non_cli_controllers = [
        DevelopmentAdmin::class
    ];

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
                if ($this->doShowAdditionalInfo()) {
                    if ($message['fieldName']) {
                        $exceptionMessage .= ' - fieldName: ' . $message['fieldName'];
                    }
                    $dataClass = $result->getModelClass();
                    if (is_subclass_of($dataClass, DataObject::class, true)) {
                        $exceptionMessage .= ', recordID: ' . $result->getRecordID();
                        $exceptionMessage .= ', dataClass: ' . $dataClass;
                    }
                }
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

    /**
     * Whether to show additional information in the error message depending on the context
     *
     * We do not want this to show this in a context where it's easy to know the record and value that
     * triggered the error e.g. form submission, API calls, etc
     */
    private function doShowAdditionalInfo(): bool
    {
        if (Director::is_cli()) {
            return true;
        }
        $currentController = Controller::curr();
        foreach (static::config()->get('show_additional_info_non_cli_controllers') as $controller) {
            if (is_a($currentController, $controller)) {
                return true;
            }
        }
        return false;
    }
}
