<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;

/**
 * Represents a floating point field.
 */
class DBFloat extends DBField
{
    public function __construct(?string $name = null, float|int $defaultVal = 0)
    {
        $this->setDefaultValue((float) $defaultVal);
        parent::__construct($name);
    }

    public function requireField(): void
    {
        $parts = [
            'datatype' => 'float',
            'null' => 'not null',
            'default' => $this->getDefaultValue(),
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'float', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * Returns the number, with commas and decimal places as appropriate, eg “1,000.00”.
     *
     * @uses number_format()
     */
    public function Nice(): string
    {
        return number_format($this->value ?? 0.0, 2);
    }

    public function Round($precision = 3): float
    {
        return round($this->value ?? 0.0, $precision ?? 0);
    }

    public function NiceRound($precision = 3): string
    {
        return number_format(round($this->value ?? 0.0, $precision ?? 0), $precision ?? 0);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        $field = NumericField::create($this->name, $title);
        $field->setScale(null); // remove no-decimal restriction
        return $field;
    }

    public function nullValue(): ?int
    {
        return 0.0;
    }

    public function prepValueForDB(mixed $value): array|float|int|null
    {
        if ($value === true) {
            return 1;
        }

        if (empty($value) || !is_numeric($value)) {
            return 0;
        }

        return $value;
    }
}
