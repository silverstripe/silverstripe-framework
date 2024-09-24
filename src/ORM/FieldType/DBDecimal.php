<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBFieldTrait;
use SilverStripe\Model\ModelFields\ModelField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Represents a Decimal field.
 */
class DBDecimal extends ModelField implements DBField
{
    use DBFieldTrait;
    /**
     * Whole number size
     */
    protected int $wholeSize = 9;

    /**
     * Decimal scale
     */
    protected int $decimalSize = 2;

    /**
     * Default value
     */
    protected float|int|string $defaultValue = 0;

    /**
     * Create a new Decimal field.
     */
    public function __construct(?string $name = null, ?int $wholeSize = 9, ?int $decimalSize = 2, float|int $defaultValue = 0)
    {
        $this->wholeSize = is_int($wholeSize) ? $wholeSize : 9;
        $this->decimalSize = is_int($decimalSize) ? $decimalSize : 2;

        $this->defaultValue = number_format((float) $defaultValue, $this->decimalSize);

        parent::__construct($name);
    }

    public function Nice(): string
    {
        return number_format($this->value ?? 0.0, $this->decimalSize ?? 0);
    }

    public function Int(): int
    {
        return floor($this->value ?? 0.0);
    }

    public function requireField(): void
    {
        $parts = [
            'datatype' => 'decimal',
            'precision' => "$this->wholeSize,$this->decimalSize",
            'default' => $this->defaultValue,
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'decimal',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }

    public function saveInto(ModelData $model): void
    {
        $fieldName = $this->name;

        if ($fieldName) {
            if ($this->value instanceof DBField) {
                $this->value->saveInto($model);
            } else {
                $value = (float) preg_replace('/[^0-9.\-\+]/', '', $this->value ?? '');
                $model->__set($fieldName, $value);
            }
        } else {
            throw new \UnexpectedValueException(
                "DBField::saveInto() Called on a nameless '" . static::class . "' object"
            );
        }
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return NumericField::create($this->name, $title)
            ->setScale($this->decimalSize);
    }

    public function nullValue(): ?int
    {
        return 0;
    }

    public function prepValueForDB(mixed $value): array|float|int|null
    {
        if ($value === true) {
            return 1;
        }

        if (empty($value) || !is_numeric($value)) {
            return 0;
        }

        if (abs((float) $value - (int) $value) < PHP_FLOAT_EPSILON) {
            return (int)$value;
        }

        return (float)$value;
    }
}
