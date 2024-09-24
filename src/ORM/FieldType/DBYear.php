<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBFieldTrait;
use SilverStripe\Model\ModelFields\ModelField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Represents a single year field.
 */
class DBYear extends ModelField implements DBField
{
    use DBFieldTrait;

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

    /**
     * Returns a list of default options that can
     * be used to populate a select box, or compare against
     * input values. Starts by default at the current year,
     * and counts back to 1900.
     *
     * @param int|null $start starting date to count down from
     * @param int|null $end end date to count down to
     */
    private function getDefaultOptions(?int $start = null, ?int $end = null): array
    {
        if (!$start) {
            $start = (int)date('Y');
        }
        if (!$end) {
            $end = 1900;
        }
        $years = [];
        for ($i = $start; $i >= $end; $i--) {
            $years[$i] = $i;
        }
        return $years;
    }
}
