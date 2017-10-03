<?php
/**
 * Created by IntelliJ IDEA.
 * User: Werner
 * Date: 03.10.2017
 * Time: 23:07
 */

namespace SilverStripe\Dev\Constraint;


use PHPUnit_Util_InvalidArgumentHelper;

class ViewableDataContains extends \PHPUnit_Framework_Constraint
{

    private $match = [];

    public function __construct($match)
    {
        parent::__construct();

        if (!is_array($match)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
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
     * @param mixed $other Value or object to evaluate.
     * @param string $description Additional information about the test
     * @param bool $returnResult Whether to return a result or throw an exception
     *
     * @return mixed
     *
     * @throws \PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = true;

        foreach ($this->match as $fieldName => $value) {
            if (!$other->hasField($fieldName)) {
                $success = false;
                break;
            }
            if ($other->getField($fieldName) != $value) {
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
    }


    /**
     * Returns a string representation of the object.
     *
     * @todo: add representation for more than one match
     *
     * @return string
     */
    public function toString()
    {
        return 'contains only Objects where "' . key($this->match) . '" is "' . current($this->match) . '"';
    }
}
