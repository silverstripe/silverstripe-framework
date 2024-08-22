<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DB;
use SilverStripe\View\ViewableData;

/**
 * Represents a boolean field.
 */
class DBBoolean extends DBField
{
    public function __construct(?string $name = null, bool|int $defaultVal = 0)
    {
        $this->defaultVal = ($defaultVal) ? 1 : 0;

        parent::__construct($name);
    }

    public function requireField(): void
    {
        $parts = [
            'datatype' => 'tinyint',
            'precision' => 1,
            'sign' => 'unsigned',
            'null' => 'not null',
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];
        $values = ['type' => 'boolean', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function Nice(): string
    {
        return ($this->value) ? _t(__CLASS__ . '.YESANSWER', 'Yes') : _t(__CLASS__ . '.NOANSWER', 'No');
    }

    public function NiceAsBoolean(): string
    {
        return ($this->value) ? 'true' : 'false';
    }

    public function saveInto(ViewableData $dataObject): void
    {
        $fieldName = $this->name;
        if ($fieldName) {
            if ($this->value instanceof DBField) {
                $this->value->saveInto($dataObject);
            } else {
                $dataObject->__set($fieldName, $this->value ? 1 : 0);
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
            1 => _t(__CLASS__ . '.YESANSWER', 'Yes'),
            0 => _t(__CLASS__ . '.NOANSWER', 'No')
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
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (empty($value)) {
            return 0;
        }
        if (is_string($value)) {
            switch (strtolower($value ?? '')) {
                case 'false':
                case 'f':
                    return 0;
                case 'true':
                case 't':
                    return 1;
            }
        }
        return $value ? 1 : 0;
    }
}
