<?php

namespace SilverStripe\Forms;

use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Form field to display editable time values in an <input type="text"> field.
 *
 * # Localization
 *
 * See {@link DateField}
 *
 * @todo Timezone support
 */
class TimeField extends TextField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TIME;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Override time format. If empty will default to that used by the current locale.
     *
     * @var string
     */
    protected $timeFormat = null;

    /**
     * Length of this date (full, short, etc).
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     * @var int
     */
    protected $timeLength = null;

    /**
     * Unparsed value, used exclusively for comparing with internal value
     * to detect invalid values.
     *
     * @var mixed
     */
    protected $rawValue = null;

    /**
     * Set custom timezone
     *
     * @var string
     */
    protected $timezone = null;

    /**
     * Get time format in CLDR standard format
     *
     * This can be set explicitly. If not, this will be generated from the current locale
     * with the current time length.
     *
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     */
    public function getTimeFormat()
    {
        if ($this->timeFormat) {
            return $this->timeFormat;
        }

        // Get from locale
        return $this->getFormatter()->getPattern();
    }

    /**
     * Set time format in CLDR standard format.
     *
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     * @param string $format
     * @return $this
     */
    public function setTimeFormat($format)
    {
        $this->timeFormat = $format;
        return $this;
    }

    /**
     * Get length of the time format to use. One of:
     *
     *  - IntlDateFormatter::SHORT E.g. '6:31 PM'
     *  - IntlDateFormatter::MEDIUM E.g. '6:30:48 PM'
     *  - IntlDateFormatter::LONG E.g. '6:32:09 PM NZDT'
     *  - IntlDateFormatter::FULL E.g. '6:32:24 PM New Zealand Daylight Time'
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     * @return int
     */
    public function getTimeLength()
    {
        if ($this->timeLength) {
            return $this->timeLength;
        }
        return IntlDateFormatter::MEDIUM;
    }

    /**
     * Get length of the time format to use. One of:
     *
     *  - IntlDateFormatter::SHORT E.g. '6:31 PM'
     *  - IntlDateFormatter::MEDIUM E.g. '6:30:48 PM'
     *  - IntlDateFormatter::LONG E.g. '6:32:09 PM NZDT'
     *  - IntlDateFormatter::FULL E.g. '6:32:24 PM New Zealand Daylight Time'
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     *
     * @param int $length
     * @return $this
     */
    public function setTimeLength($length)
    {
        $this->timeLength = $length;
        return $this;
    }

    /**
     * Get time formatter with the standard locale / date format
     *
     * @return IntlDateFormatter
     */
    protected function getFormatter()
    {
        $formatter =  IntlDateFormatter::create(
            $this->getLocale(),
            IntlDateFormatter::NONE,
            $this->getTimeLength(),
            $this->getTimezone()
        );

        // Don't invoke getDateFormat() directly to avoid infinite loop
        if ($this->timeFormat) {
            $ok = $formatter->setPattern($this->timeFormat);
            if (!$ok) {
                throw new InvalidArgumentException("Invalid time format {$this->timeFormat}");
            }
        }
        return $formatter;
    }

    /**
     * Get a time formatter for the ISO 8601 format
     *
     * @return IntlDateFormatter
     */
    protected function getISO8601Formatter()
    {
        $formatter = IntlDateFormatter::create(
            i18n::config()->get('default_locale'),
            IntlDateFormatter::NONE,
            IntlDateFormatter::MEDIUM,
            date_default_timezone_get() // Default to server timezone
        );
        $formatter->setLenient(false);
        // CLDR iso8601 time
        // Note we omit timezone from this format, and we assume server TZ always.
        $formatter->setPattern('HH:mm:ss');
        return $formatter;
    }

    public function getAttribute($name)
    {
        $attributes = parent::getAttributes();

        // Merge with client config
        $config = $this->getClientConfig();
        foreach ($config as $key => $value) {
            $attributes["data-{$key}"] = $value;
        }
        return $attributes;
    }

    /**
     * Get client config options for this field
     *
     * @return array
     */
    public function getClientConfig()
    {
        return [
            // @todo - Support javascript time picker
            'timeformat' => $this->getTimeFormat(),
        ];
    }

    public function Type()
    {
        return 'time text';
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
        // Save raw value for later validation
        $this->rawValue = $value;

        // Parse from submitted value
        $this->value = $this->localisedToISO8601($value);
        return $this;
    }

    /**
     * Set time assigned from database value
     *
     * @param mixed $value
     * @param mixed $data
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        // Save raw value for later validation
        $this->rawValue = $value;

        // Null case
        if (!$value) {
            $this->value = null;
            return $this;
        }

        // Re-run through formatter to tidy up (e.g. remove date component)
        $this->value = $this->tidyISO8601($value);
        return $this;
    }

    public function Value()
    {
        $localised = $this->iso8601ToLocalised($this->value);
        if ($localised) {
            return $localised;
        }

        // Show midnight in localised format
        return $this->getMidnight();
    }

    /**
     * Show midnight in current format (adjusts for timezone)
     *
     * @return string
     */
    public function getMidnight()
    {
        $formatter = $this->getFormatter();
        $timestamp = $this->withTimezone($this->getTimezone(), function () {
            return strtotime('midnight');
        });
        return $formatter->format($timestamp);
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Don't validate empty fields
        if (empty($this->rawValue)) {
            return true;
        }

        // We submitted a value, but it couldn't be parsed
        if (empty($this->value)) {
            $validator->validationError(
                $this->name,
                _t(
                    'TimeField.VALIDATEFORMAT',
                    "Please enter a valid time format ({format})",
                    ['format' => $this->getTimeFormat()]
                )
            );
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Creates a new readonly field specified below
     *
     * @return TimeField_Readonly
     */
    public function performReadonlyTransformation()
    {
        /** @var TimeField_Readonly $result */
        $result = $this->castedCopy(TimeField_Readonly::class);
        return $result;
    }

    /**
     * Convert time localised in the current locale to ISO 8601 time
     *
     * @param string $time
     * @return string The formatted time, or null if not a valid time
     */
    public function localisedToISO8601($time)
    {
        if (!$time) {
            return null;
        }
        $fromFormatter = $this->getFormatter();
        $toFormatter = $this->getISO8601Formatter();
        $timestamp = $fromFormatter->parse($time);
        if ($timestamp === false) {
            return null;
        }
        return $toFormatter->format($timestamp);
    }

    /**
     * Format iso time to localised form
     *
     * @param string $time
     * @return string
     */
    public function iso8601ToLocalised($time)
    {
        $time = $this->tidyISO8601($time);
        if (!$time) {
            return null;
        }
        $fromFormatter = $this->getISO8601Formatter();
        $toFormatter = $this->getFormatter();
        $timestamp = $fromFormatter->parse($time);
        if ($timestamp === false) {
            return null;
        }
        return $toFormatter->format($timestamp);
    }



    /**
     * Tidy up iso8601-ish time, or approximation
     *
     * @param string $time Time in iso8601 or approximate form
     * @return string iso8601 time, or null if not valid
     */
    public function tidyISO8601($time)
    {
        if (!$time) {
            return null;
        }
        // Re-run through formatter to tidy up (e.g. remove date component)
        $formatter = $this->getISO8601Formatter();
        $timestamp = $formatter->parse($time);
        if ($timestamp === false) {
            // Fallback to strtotime
            $timestamp = strtotime($time, DBDatetime::now()->getTimestamp());
            if ($timestamp === false) {
                return null;
            }
        }
        return $formatter->format($timestamp);
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        if ($this->value && $timezone !== $this->timezone) {
            throw new \BadMethodCallException("Can't change timezone after setting a value");
        }
        $this->timezone = $timezone;
        return $this;
    }


    /**
     * Run a callback within a specific timezone
     *
     * @param string $timezone
     * @param callable $callback
     */
    protected function withTimezone($timezone, $callback)
    {
        $currentTimezone = date_default_timezone_get();
        try {
            if ($timezone) {
                date_default_timezone_set($timezone);
            }
            return $callback();
        } finally {
            // Restore timezone
            if ($timezone) {
                date_default_timezone_set($currentTimezone);
            }
        }
    }
}
