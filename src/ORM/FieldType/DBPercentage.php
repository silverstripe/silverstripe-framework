<?php

namespace SilverStripe\ORM\FieldType;

/**
 * Represents a decimal field from 0-1 containing a percentage value.
 *
 * Example instantiation in {@link DataObject::$db}:
 * <code>
 * static $db = [
 *  "SuccessRatio" => "Percentage",
 *  "ReallyAccurate" => "Percentage(6)",
 * ];
 * </code>
 */
class DBPercentage extends DBDecimal
{

    /**
     * Create a new Decimal field.
     *
     * @param string $name
     * @param int $precision
     */
    public function __construct($name = null, $precision = 4)
    {
        if (!$precision) {
            $precision = 4;
        }

        parent::__construct($name, $precision + 1, $precision);
    }

    /**
     * Returns the number, expressed as a percentage. For example, “36.30%”
     */
    public function Nice()
    {
        return number_format($this->value * 100, $this->decimalSize - 2) . '%';
    }

    public function saveInto($dataObject)
    {
        parent::saveInto($dataObject);

        $fieldName = $this->name;
        if ($fieldName && $dataObject->$fieldName > 1.0) {
            $dataObject->__set($fieldName, 1.0);
        }
    }
}
