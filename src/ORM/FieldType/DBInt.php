<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Validation\FieldValidation\IntFieldValidator;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelData;

/**
 * Represents a signed 32 bit integer field, which has a range between -2147483648 and 2147483647.
 */
class DBInt extends DBField
{
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
        // Cast int like strings as ints
        if (is_string($this->value) && preg_match('/^-?\d+$/', $this->value)) {
            $this->value = (int) $value;
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
        $parts = [
            'datatype' => 'int',
            'precision' => 11,
            'null' => 'not null',
            'default' => $this->getDefaultValue(),
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'int', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function Times(): SS_List
    {
        $output = new ArrayList();
        for ($i = 0; $i < $this->value; $i++) {
            $output->push(ArrayData::create(['Number' => $i + 1]));
        }

        return $output;
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
}
