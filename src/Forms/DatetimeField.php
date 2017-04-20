<?php

namespace SilverStripe\Forms;

use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\i18n\i18n;

/**
 * A composite field for date and time entry,
 * based on {@link DateField} and {@link TimeField}.
 * Usually saves into a single {@link DBDateTime} database column.
 * If you want to save into {@link Date} or {@link Time} columns,
 * please instanciate the fields separately.
 *
 * This field does not implement the <input type="datetime-local"> HTML5 field,
 * but can use date and time HTML5 inputs separately (through {@link DateField->setHTML5()}
 * and {@link TimeField->setHTML5()}.
 *
 * # Configuration
 *
 * Individual options are configured either on the DatetimeField, or on individual
 * sub-fields accessed via getDateField() or getTimeField()
 *
 * Example:
 * <code>
 * $field = new DatetimeField('Name', 'Label');
 * $field->getDateField()->setTitle('Select Date');
 * </code>
 *
 * - setLocale(): Sets a custom locale for date / time formatting.
 * - setTimezone(): Set a different timezone for viewing. {@link dataValue()} will still save
 * the time in PHP's default timezone (date_default_timezone_get()), its only a view setting.
 * Note that the sub-fields ({@link getDateField()} and {@link getTimeField()})
 * are not timezone aware, and will have their values set in local time, rather than server time.
 * - setDateTimeOrder(): An sprintf() template to determine in which order the date and time values will
 * be combined. This is necessary as those separate formats are set in their invididual fields.
 */
class DatetimeField extends FormField
{

    /**
     * @var DateField
     */
    protected $dateField = null;

    /**
     * @var TimeField
     */
    protected $timeField = null;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATETIME;

    /**
     * Date time order
     *
     * @var string
     */
    protected $dateTimeOrder = '{date} {time}';

    public function __construct($name, $title = null, $value = "")
    {
        $this->timeField = TimeField::create($name . '[time]', false);
        $this->dateField = DateField::create($name . '[date]', false);
        parent::__construct($name, $title, $value);
    }

    public function setForm($form)
    {
        parent::setForm($form);
        $this->dateField->setForm($form);
        $this->timeField->setForm($form);
        return $this;
    }

    public function setName($name)
    {
        parent::setName($name);
        $this->dateField->setName($name . '[date]');
        $this->timeField->setName($name . '[time]');
        return $this;
    }

    /**
     * Sets value from a submitted form array
     *
     * @param array $value Expected submission value is either an empty value,
     * or an array with the necessary components keyed against 'date' and 'time', each value
     * localised according to each's localisation setting.
     * @param mixed $data
     * @return $this
     */
    public function setSubmittedValue($value, $data = null)
    {
        // Empty value
        if (empty($value)) {
            $this->value = null;
            $this->dateField->setValue(null);
            $this->timeField->setValue(null);
            return $this;
        }

        // Validate value is submitted in array format
        if (!is_array($value)) {
            throw new InvalidArgumentException("Value is not submitted array");
        }

        // Save each field, and convert from array to iso8601 string
        $this->dateField->setSubmittedValue($value['date'], $value);
        $this->timeField->setSubmittedValue($value['time'], $value);

        // Combine date components back into iso8601 string for the root value
        $this->value = $this->dataValue();
        return $this;
    }

    /**
     * Get formatter for converting to the target timezone, if timezone is set
     * Can return null if no timezone set
     *
     * @return IntlDateFormatter|null
     */
    protected function getTimezoneFormatter()
    {
        $timezone = $this->getTimezone();
        if (!$timezone) {
            return null;
        }

        // Build new formatter with the altered timezone
        $formatter = clone $this->getISO8601Formatter();
        $formatter->setTimeZone($timezone);
        return $formatter;
    }

    /**
     * Get a date formatter for the ISO 8601 format
     *
     * @return IntlDateFormatter
     */
    protected function getISO8601Formatter()
    {
        $formatter = IntlDateFormatter::create(
            i18n::config()->uninherited('default_locale'),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::MEDIUM,
            date_default_timezone_get() // Default to server timezone
        );
        $formatter->setLenient(false);
        // CLDR iso8601 date.
        // Note we omit timezone from this format, and we assume server TZ always.
        $formatter->setPattern('y-MM-dd HH:mm:ss');
        return $formatter;
    }

    /**
     * Assign value from iso8601 string
     *
     * @param mixed $value
     * @param mixed $data
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        // Empty value
        if (empty($value)) {
            $this->value = null;
            $this->dateField->setValue(null);
            $this->timeField->setValue(null);
            return $this;
        }
        if (is_array($value)) {
            throw new InvalidArgumentException("Use setSubmittedValue to assign by array");
        };

        // Validate iso 8601 date
        // If invalid, assign for later validation failure
        $isoFormatter = $this->getISO8601Formatter();
        $timestamp = $isoFormatter->parse($value);
        if ($timestamp === false) {
            $this->dateField->setSubmittedValue($value);
            $this->timeField->setValue(null);
            return $this;
        }

        // Cleanup date
        $value = $isoFormatter->format($timestamp);

        // Save value
        $this->value = $value;

        // Shift iso date into timezone before assignment to subfields
        $timezoneFormatter = $this->getTimezoneFormatter();
        if ($timezoneFormatter) {
            $value = $timezoneFormatter->format($timestamp);
        }

        // Set date / time components, which are unaware of their timezone
        list($date, $time) = explode(' ', $value);
        $this->dateField->setValue($date, $data);
        $this->timeField->setValue($time, $data);
        return $this;
    }

    /**
     * localised time value
     *
     * @return string
     */
    public function Value()
    {
        $date = $this->dateField->Value();
        $time = $this->timeField->Value();
        return $this->joinDateTime($date, $time);
    }

    /**
     * @param string $date
     * @param string $time
     * @return string
     */
    protected function joinDateTime($date, $time)
    {
        $format = $this->getDateTimeOrder();
        return strtr($format, [
            '{date}' => $date,
            '{time}' => $time
        ]);
    }

    /**
     * Get ISO8601 formatted string in the local server timezone
     *
     * @return string|null
     */
    public function dataValue()
    {
        // No date means no value (even if time is specified)
        $dateDataValue = $this->getDateField()->dataValue();
        if (empty($dateDataValue)) {
            return null;
        }

        // Build iso8601 timestamp from combined date and time
        $timeDataValue = $this->getTimeField()->dataValue() ?: '00:00:00';
        $value = $dateDataValue . ' ' . $timeDataValue;

        // If necessary, convert timezone
        $timezoneFormatter = $this->getTimezoneFormatter();
        if ($timezoneFormatter) {
            $timestamp = $timezoneFormatter->parse($value);
            $isoFormatter = $this->getISO8601Formatter();
            $value = $isoFormatter->format($timestamp);
        }

        return $value;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);
        $this->dateField->setDisabled($bool);
        $this->timeField->setDisabled($bool);
        return $this;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);
        $this->dateField->setReadonly($bool);
        $this->timeField->setReadonly($bool);
        return $this;
    }

    /**
     * @return DateField
     */
    public function getDateField()
    {
        return $this->dateField;
    }

    /**
     * @param FormField $field
     */
    public function setDateField($field)
    {
        $expected = $this->getName() . '[date]';
        if ($field->getName() != $expected) {
            throw new InvalidArgumentException(sprintf(
                'Wrong name format for date field: "%s" (expected "%s")',
                $field->getName(),
                $expected
            ));
        }

        $field->setForm($this->getForm());
        $field->setValue($this->dateField->dataValue());
        $this->dateField = $field;
    }

    /**
     * @return TimeField
     */
    public function getTimeField()
    {
        return $this->timeField;
    }

    /**
     * @param FormField $field
     */
    public function setTimeField($field)
    {
        $expected = $this->getName() . '[time]';
        if ($field->getName() != $expected) {
            throw new InvalidArgumentException(sprintf(
                'Wrong name format for time field: "%s" (expected "%s")',
                $field->getName(),
                $expected
            ));
        }

        $field->setForm($this->getForm());
        $field->setValue($this->timeField->dataValue());
        $this->timeField = $field;
    }

    /**
     * Set default locale for this field. If omitted will default to the current locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->dateField->setLocale($locale);
        $this->timeField->setLocale($locale);
        return $this;
    }

    /**
     * Get locale for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->dateField->getLocale();
    }

    public function validate($validator)
    {
        $dateValid = $this->dateField->validate($validator);
        $timeValid = $this->timeField->validate($validator);

        // Validate if both subfields are valid
        return $dateValid && $timeValid;
    }

    public function performReadonlyTransformation()
    {
        $field = clone $this;
        $field->setReadonly(true);
        return $field;
    }

    public function __clone()
    {
        $this->dateField = clone $this->dateField;
        $this->timeField = clone $this->timeField;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Custom timezone
     *
     * @var string
     */
    protected $timezone = null;

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        if ($this->value && $timezone !== $this->timezone) {
            throw new \BadMethodCallException("Can't change timezone after setting a value");
        }
        // Note: DateField has no timezone option, and TimeField::setTimezone
        // should be ignored
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateTimeOrder()
    {
        return $this->dateTimeOrder;
    }

    /**
     * Set date time order format string. Use {date} and {time} as placeholders.
     *
     * @param string $dateTimeOrder
     * @return $this
     */
    public function setDateTimeOrder($dateTimeOrder)
    {
        $this->dateTimeOrder = $dateTimeOrder;
        return $this;
    }
}
