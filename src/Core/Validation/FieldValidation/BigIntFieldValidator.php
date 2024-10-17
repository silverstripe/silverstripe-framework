<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;

class BigIntFieldValidator extends IntFieldValidator
{
    /**
     * The minimum value for a signed 64-bit integer.
     * Defined as string instead of int otherwise will end up as a float
     * on 64-bit systems if defined as an int
     */
    private const MIN_64_BIT_INT = '-9223372036854775808';

    /**
     * The maximum value for a signed 64-bit integer.
     */
    private const MAX_64_BIT_INT = '9223372036854775807';

    public function __construct(
        string $name,
        mixed $value,
        ?int $minValue = null,
        ?int $maxValue = null
    ) {
        if (is_null($minValue)) {
            // Casting the string const to an int will properly return an int on 64-bit systems
            $minValue = (int) BigIntFieldValidator::MIN_64_BIT_INT;
        }
        if (is_null($maxValue)) {
            $maxValue = (int) BigIntFieldValidator::MAX_64_BIT_INT;
        }
        parent::__construct($name, $value, $minValue, $maxValue);
    }
}
