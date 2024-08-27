<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\View\ViewableData;

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
     */
    public function __construct(?string $name = null, int $precision = 4)
    {
        if (!$precision) {
            $precision = 4;
        }

        parent::__construct($name, $precision + 1, $precision);
    }

    /**
     * Returns the number, expressed as a percentage. For example, “36.30%”
     */
    public function Nice(): string
    {
        return number_format($this->value * 100, $this->decimalSize - 2) . '%';
    }

    public function saveInto(ViewableData $model): void
    {
        parent::saveInto($model);

        $fieldName = $this->name;
        if ($fieldName && $model->$fieldName > 1.0) {
            $model->__set($fieldName, 1.0);
        }
    }
}
