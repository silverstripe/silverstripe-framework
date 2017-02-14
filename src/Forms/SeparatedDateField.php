<?php

namespace SilverStripe\Forms;

use SilverStripe\i18n\i18n;

/**
 * Date field with separate inputs for d/m/y
 */
class SeparatedDateField extends DateField
{
    public function Field($properties = array())
    {
        // Three separate fields for day, month and year
        $valArr = $this->iso8601ToArray($this->dataValue());
        $fieldDay = NumericField::create($this->name . '[day]', false, $valArr ? $valArr['day'] : null)
            ->addExtraClass('day')
            ->setHTML5(true)
            ->setMaxLength(2);
        $fieldMonth = NumericField::create($this->name . '[month]', false, $valArr ? $valArr['month'] : null)
            ->addExtraClass('month')
            ->setHTML5(true)
            ->setMaxLength(2);
        $fieldYear = NumericField::create($this->name . '[year]', false, $valArr ? $valArr['year'] : null)
            ->addExtraClass('year')
            ->setHTML5(true)
            ->setMaxLength(4);

        // Set placeholders
        if ($this->getPlaceholders()) {
            $fieldDay->setAttribute('placeholder', _t(__CLASS__ . '.DAY', 'Day'));
            $fieldMonth->setAttribute('placeholder', _t(__CLASS__ . '.MONTH', 'Month'));
            $fieldYear->setAttribute('placeholder', _t(__CLASS__ . '.YEAR', 'Year'));
        }

        // Join all fields
        // @todo custom ordering based on locale
        $sep = '&nbsp;<span class="separator">/</span>&nbsp;';
        return $fieldDay->Field() . $sep
            . $fieldMonth->Field() . $sep
            . $fieldYear->Field();
    }

    /**
     * Convert array to timestamp
     *
     * @param array $value
     * @return string
     */
    public function arrayToISO8601($value)
    {
        if ($this->isEmptyArray($value)) {
            return null;
        }

        // ensure all keys are specified
        if (!isset($value['month']) || !isset($value['day']) || !isset($value['year'])) {
            return null;
        }

        // Ensure valid range
        if (!checkdate($value['month'], $value['day'], $value['year'])) {
            return null;
        }

        // Note: Set formatter to strict for array input
        $formatter = $this->getISO8601Formatter();
        $timestamp = mktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
        if ($timestamp === false) {
            return null;
        }
        return $formatter->format($timestamp);
    }

    /**
     * Convert iso 8601 date to array (day / month / year)
     *
     * @param string $date
     * @return array|null Array form, or null if not valid
     */
    public function iso8601ToArray($date)
    {
        if (!$date) {
            return null;
        }
        $formatter = $this->getISO8601Formatter();
        $timestamp = $formatter->parse($date);
        if ($timestamp === false) {
            return null;
        }

        // Format time manually into an array
        return [
            'day' => date('j', $timestamp),
            'month' => date('n', $timestamp),
            'year' => date('Y', $timestamp),
        ];
    }

    /**
     * Assign value posted from form submission
     *
     * @param mixed $value
     * @param mixed $data
     * @return $this
     */
    public function setSubmittedValue($value, $data = null)
    {
        // Filter out empty arrays
        if ($this->isEmptyArray($value)) {
            $value = null;
        }
        $this->rawValue = $value;

        // Null case
        if (!$value || !is_array($value)) {
            $this->value = null;
            return $this;
        }

        // Parse
        $this->value = $this->arrayToISO8601($value);
        return $this;
    }

    /**
     * Check if this array is empty
     *
     * @param $value
     * @return bool
     */
    public function isEmptyArray($value)
    {
        return is_array($value) && !array_filter($value);
    }
}
