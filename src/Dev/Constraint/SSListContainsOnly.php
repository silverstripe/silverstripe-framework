<?php
/**
 * Created by IntelliJ IDEA.
 * User: Werner
 * Date: 03.10.2017
 * Time: 23:07
 */

namespace SilverStripe\Dev\Constraint;


class SSListContainsOnly extends \PHPUnit_Framework_Constraint
{

    private $constraint;

    public function __construct($match)
    {
        parent::__construct();

        $this->constraint = new ViewableDataContains($match);
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
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = true;

        foreach ($other as $item) {
            if (!$this->constraint->evaluate($item, '', true)) {
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
     * @return string
     */
    public function toString()
    {
        return 'contains only Objects where "' . key($this->match) . '" is "' . current($this->match) . '"';

    }
}
