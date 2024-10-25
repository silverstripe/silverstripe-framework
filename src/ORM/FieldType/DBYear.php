<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DB;
use SilverStripe\Model\ModelData;
use SilverStripe\Core\Validation\FieldValidation\YearFieldValidator;

/**
 * Represents a single year field
 * This field is only intended to be used with a MySQL database and the year datatype
 */
class DBYear extends DBField
{
    // MySQL year datatype supports years between 1901 and 2155
    // https://dev.mysql.com/doc/refman/8.0/en/year.html
    public const MIN_YEAR = 1901;
    public const MAX_YEAR = 2155;

    private static $field_validators = [
        YearFieldValidator::class => ['getMinYear', 'getMaxYear'],
    ];

    public function requireField(): void
    {
        $parts = ['datatype' => 'year', 'precision' => 4, 'arrayValue' => $this->arrayValue];
        $values = ['type' => 'year', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        $selectBox = DropdownField::create($this->name, $title);
        $selectBox->setSource($this->getDefaultOptions());
        return $selectBox;
    }

    public function setValue(mixed $value, null|array|ModelData $record = null, bool $markChanged = true): static
    {
        parent::setValue($value, $record, $markChanged);
        // 0 is used to represent a null value, which will be stored as 0000 in MySQL
        if ($this->value === '0000') {
            $this->value = 0;
        }
        // shorthand for 2000 in MySQL
        if ($this->value === '00') {
            $this->value = 2000;
        }
        // convert string int to int
        // string int and int are both valid in MySQL, though only use int internally
        if (is_string($this->value) && preg_match('#^\d+$#', (string) $this->value)) {
            $this->value = (int) $this->value;
        }
        if (!is_int($this->value)) {
            return $this;
        }
        // shorthand for 2001-2069 in MySQL
        if ($this->value >= 1 && $this->value <= 69) {
            $this->value = 2000 + $this->value;
        }
        // shorthand for 1970-1999 in MySQL
        if ($this->value >= 70 && $this->value <= 99) {
            $this->value = 1900 + $this->value;
        }
        return $this;
    }

    public function getMinYear(): int
    {
        return DBYear::MIN_YEAR;
    }

    public function getMaxYear(): int
    {
        return DBYear::MAX_YEAR;
    }

    /**
     * Returns a list of default options that can
     * be used to populate a select box, or compare against
     * input values. Starts by default at the current year,
     * and counts back to 1901.
     *
     * @param int|null $start starting date to count down from
     * @param int|null $end end date to count down to
     */
    private function getDefaultOptions(?int $start = null, ?int $end = null): array
    {
        if (!$start) {
            $start = (int) date('Y');
        }
        if (!$end) {
            $end = DBYear::MIN_YEAR;
        }
        $years = [];
        for ($i = $start; $i >= $end; $i--) {
            $years[$i] = $i;
        }
        return $years;
    }
}
