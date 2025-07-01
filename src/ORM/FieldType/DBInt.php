<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DB;
use SilverStripe\Model\ModelData;

/**
 * Represents a signed 32 bit integer field, which has a range between -2147483648 and 2147483647.
 */
class DBInt extends DBField
{
    /**
     * The minimum value for a signed 32-bit integer.
     * Defined as string instead of int because be cast to a float
     * on 32-bit systems if defined as an int
     */
    protected const MIN_INT = '-2147483648';

    /**
     * The maximum value for a signed 32-bit integer.
     */
    protected const MAX_INT = '2147483647';

    private static array $field_validators = [
        IntFieldValidator::class
    ];

    public function __construct(?string $name = null, int $defaultVal = 0)
    {
        $this->setDefaultValue($defaultVal);
        parent::__construct($name);
    }

    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        parent::setValue($value, $record, $markChanged);
        // Cast string ints if they're valid
        if (is_string($this->value) && preg_match('/^-?\d+$/', $this->value)) {
            // Ensure we can cast to int and back without loss of precision
            // if not, keep the original value which will fail validation later
            $stringIntValue = (string) (int) $value;
            if ($stringIntValue !== $value) {
                $this->value = $value;
            } else {
                // Cast valid string ints as ints
                $this->value = (int) $value;
            }
        }
        return $this;
    }

    /**
     * Returns the number, with commas added as appropriate, eg “1,000”.
     */
    public function Formatted(): string
    {
        return number_format($this->value ?? 0.0);
    }

    public function requireField(): void
    {
        DB::require_field($this->tableName, $this->name, $this->getFieldSpec());
    }

    /**
     * Get the specifications which will be used to generate this column in the database.
     */
    public function getFieldSpec(): string|array
    {
        $parts = [
            'datatype' => 'int',
            'precision' => 11,
            'null' => 'not null',
            'default' => $this->getDefaultValue(),
            'arrayValue' => $this->arrayValue
        ];
        return ['type' => 'int', 'parts' => $parts];
    }

    public function Nice(): string
    {
        return sprintf('%d', $this->value);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return NumericField::create($this->name, $title);
    }

    public function nullValue(): ?int
    {
        return 0;
    }

    public function prepValueForDB(mixed $value): array|int|null
    {
        if ($value === true) {
            return 1;
        }

        if (empty($value) || !is_numeric($value)) {
            return 0;
        }

        return (int)$value;
    }

    public static function getMinValue(): string|int
    {
        return static::MIN_INT;
    }

    public static function getMaxValue(): string|int
    {
        return static::MAX_INT;
    }
}
