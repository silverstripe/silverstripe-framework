<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Validation\FieldValidation\BooleanFieldValidator;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DB;
use SilverStripe\Model\ModelData;

/**
 * Represents a boolean field
 * Values are stored in the database as tinyint i.e. 1 or 0
 */
class DBBoolean extends DBField
{
    private static array $field_validators = [
        BooleanFieldValidator::class,
    ];

    public function __construct(?string $name = null, bool|int $defaultVal = false)
    {
        $this->setDefaultValue($defaultVal);
        parent::__construct($name);
    }

    public function requireField(): void
    {
        $parts = [
            'datatype' => 'tinyint',
            'precision' => 1,
            'sign' => 'unsigned',
            'null' => 'not null',
            'default' => (int) $this->getDefaultValue(),
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'boolean', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function setDefaultValue(mixed $defaultValue): static
    {
        $value = (int) $this->convertBooleanLikeValue($defaultValue);
        return parent::setDefaultValue($value);
    }

    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        $value = $this->convertBooleanLikeValue($value);
        parent::setValue($value);
        return $this;
    }

    public function Nice(): string
    {
        return ($this->value) ? _t(__CLASS__ . '.YESANSWER', 'Yes') : _t(__CLASS__ . '.NOANSWER', 'No');
    }

    public function NiceAsBoolean(): string
    {
        return ($this->value) ? 'true' : 'false';
    }

    public function saveInto(ModelData $dataObject): void
    {
        $fieldName = $this->name;
        if ($fieldName) {
            if ($this->value instanceof DBField) {
                $this->value->saveInto($dataObject);
            } else {
                $dataObject->__set($fieldName, (bool) $this->value);
            }
        } else {
            $class = static::class;
            throw new \RuntimeException("DBField::saveInto() Called on a nameless '$class' object");
        }
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return CheckboxField::create($this->name, $title);
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        $anyText = _t(__CLASS__ . '.ANY', 'Any');
        $source = [
            '' => $anyText,
            '1' => _t(__CLASS__ . '.YESANSWER', 'Yes'),
            '0' => _t(__CLASS__ . '.NOANSWER', 'No')
        ];

        return DropdownField::create($this->name, $title, $source)
            ->setEmptyString($anyText);
    }

    public function nullValue(): ?int
    {
        return 0;
    }

    public function prepValueForDB(mixed $value): array|int|null
    {
        $bool = $this->convertBooleanLikeValue($value);
        // Ensure a tiny int is returned no matter what e.g. value is an
        return $bool ? 1 : 0;
    }

    /**
     * Convert boolean-like values to boolean
     * Does not convert non-boolean-like values e.g. array - will be handled by the FieldValidator
     */
    private function convertBooleanLikeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'false':
                case 'f':
                case '0':
                    return false;
                case 'true':
                case 't':
                case '1':
                    return true;
            }
        }
        if ($value === 0) {
            return false;
        }
        if ($value === 1) {
            return true;
        }
        return $value;
    }
}
