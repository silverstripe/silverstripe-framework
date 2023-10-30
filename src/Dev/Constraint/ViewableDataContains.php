<?php

namespace SilverStripe\Dev\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;
use SilverStripe\Dev\SapphireTest;

/**
 * Constraint for checking if a ViewableData (e.g. ArrayData or any DataObject) contains fields matching the given
 * key-value pairs.
 */
class ViewableDataContains extends Constraint implements TestOnly
{
    /**
     * @var array
     */
    private $match;

    /**
     * ViewableDataContains constructor.
     */
    public function __construct(array $match)
    {
        if (!is_array($match)) {
            throw SapphireTest::createInvalidArgumentException(
                1,
                'array'
            );
        }

        $this->match = $match;
    }

    /**
     * Evaluates the constraint for parameter $other
     *
     * If $returnResult is set to false (the default), an exception is thrown
     * in case of a failure. null is returned otherwise.
     *
     * If $returnResult is true, the result of the evaluation is returned as
     * a boolean value instead: true in case of success, false in case of a
     * failure.
     *
     * @param ViewableData $other Value or object to evaluate.
     * @param string $description Additional information about the test
     * @param bool $returnResult Whether to return a result or throw an exception
     *
     * @throws ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false): ?bool
    {
        $success = true;

        foreach ($this->match as $fieldName => $value) {
            if ($other->$fieldName != $value) {
                $success = false;
                break;
            }
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }

        return null;
    }

    /**
     * Returns a string representation of the object.
     */
    public function toString(): string
    {
        return 'contains only Objects where "' . key($this->match ?? []) . '" is "' . current($this->match ?? []) . '"';
    }
}
