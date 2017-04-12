<?php

namespace SilverStripe\Forms;

use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Form field used for editing date time string
 *
 * # Configuration
 *
 * - "timezone": Set a different timezone for viewing. {@link dataValue()} will still save
 * the time in PHP's default timezone (date_default_timezone_get()), its only a view setting.
 * - "datetimeorder": An sprintf() template to determine in which order the date and time values will
 * be combined. This is necessary as those separate formats are set in their invididual fields.
 */
class DatetimeField extends TextField
{

    /**
     * @var bool
     */
    protected $html5 = true;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Min date time
     *
     * @var string ISO 8601 date time for min date time
     */
    protected $minDatetime = null;

    /**
     * Max date time
     *
     * @var string ISO 860 date time for max date time
     */
    protected $maxDatetime = null;

    /**
     * Override date format. If empty will default to that used by the current locale.
     *
     * @var null
     */
    protected $datetimeFormat = null;

    /**
     * Length of this date (full, short, etc).
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     * @var int
     */
    protected $dateLength = null;

    /**
     * Length of this time (full, short, etc).
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

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATETIME;

    /**
     * Date time order
     *
     * @var string
     */
    protected $dateTimeOrder = '{date} {time}';

    public function __construct($name, $title = null, $value = "")
    {
        parent::__construct($name, $title, $value);
    }

    public function setForm($form)
    {
        parent::setForm($form);
        return $this;
    }

    public function setName($name)
    {
        parent::setName($name);
        return $this;
    }

    public function Type()
    {
        return 'text datetime';
    }

    public function getHTML5()
    {
        return $this->html5;
    }

    public function setHTML5($bool)
    {
        $this->html5 = $bool;
        return $this;
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

        // Null case
        if (!$value) {
            $this->value = null;
            return $this;
        }

        // Parse from submitted value
        $this->value = $this->localisedToISO8601($value);
        return $this;
    }

    /**
     * Convert date localised in the current locale to ISO 8601 date
     *
     * @param string $date
     * @return string The formatted date, or null if not a valid date
     */
    public function localisedToISO8601($datetime)
    {
        if (!$datetime) {
            return null;
        }
        $fromFormatter = $this->getFormatter();
        $toFormatter = $this->getISO8601Formatter();
        $timestamp = $fromFormatter->parse($datetime);
        if ($timestamp === false) {
            return null;
        }
        return $toFormatter->format($timestamp) ?: null;
    }

    /**
     * Get date formatter with the standard locale / date format
     *
     * @throws \LogicException
     * @return IntlDateFormatter
     */
    protected function getFormatter()
    {
        if ($this->getHTML5() && $this->datetimeFormat && $this->datetimeFormat !== DBDatetime::ISO_DATE) {
            throw new \LogicException(
                'Please opt-out of HTML5 processing of ISO 8601 dates via setHTML5(false) if using setDateFormat()'
            );
        }

        if ($this->getHTML5() && $this->dateLength) {
            throw new \LogicException(
                'Please opt-out of HTML5 processing of ISO 8601 dates via setHTML5(false) if using setDateLength()'
            );
        }

        if ($this->getHTML5() && $this->locale) {
            throw new \LogicException(
                'Please opt-out of HTML5 processing of ISO 8601 dates via setHTML5(false) if using setLocale()'
            );
        }

        $formatter = IntlDateFormatter::create(
            $this->getLocale(),
            $this->getDateLength(),
            $this->getTimeLength(),
            $this->getTimezone()
        );

        if ($this->getHTML5()) {
            // Browsers expect ISO 8601 dates, localisation is handled on the client
            $formatter->setPattern(DBDatetime::ISO_DATETIME);
        } elseif ($this->datetimeFormat) {
            // Don't invoke getDateFormat() directly to avoid infinite loop
            $ok = $formatter->setPattern($this->datetimeFormat);
            if (!$ok) {
                throw new InvalidArgumentException("Invalid date format {$this->datetimeFormat}");
            }
        }
        return $formatter;
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
        return $this;
    }

    public function Value()
    {
        return $this->iso8601ToLocalised($this->value);
    }

    /**
     * Convert an ISO 8601 localised date into the format specified by the
     * current date format.
     *
     * @param string $date
     * @return string The formatted date, or null if not a valid date
     */
    public function iso8601ToLocalised($datetime)
    {
        $datetime = $this->tidyISO8601($datetime);
        if (!$datetime) {
            return null;
        }
        $fromFormatter = $this->getISO8601Formatter();
        $toFormatter = $this->getFormatter();
        $timestamp = $fromFormatter->parse($datetime);
        if ($timestamp === false) {
            return null;
        }
        return $toFormatter->format($timestamp) ?: null;
    }

    /**
     * Tidy up iso8601-ish date, or approximation
     *
     * @param string $date Date in iso8601 or approximate form
     * @return string iso8601 date, or null if not valid
     */
    public function tidyISO8601($datetime)
    {
        if (!$datetime) {
            return null;
        }
        // Re-run through formatter to tidy up (e.g. remove time component)
        $formatter = $this->getISO8601Formatter();
        $timestamp = $formatter->parse($datetime);
        if ($timestamp === false) {
            // Fallback to strtotime
            $timestamp = strtotime($datetime, DBDatetime::now()->getTimestamp());
            if ($timestamp === false) {
                return null;
            }
        }
        return $formatter->format($timestamp);
    }

    /**
     * Get length of the date format to use. One of:
     *
     *  - IntlDateFormatter::SHORT
     *  - IntlDateFormatter::MEDIUM
     *  - IntlDateFormatter::LONG
     *  - IntlDateFormatter::FULL
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     * @return int
     */
    public function getDateLength()
    {
        if ($this->dateLength) {
            return $this->dateLength;
        }
        return IntlDateFormatter::MEDIUM;
    }

    /**
     * Get length of the date format to use.
     * Only applicable with {@link setHTML5(false)}.
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     *
     * @param int $length
     * @return $this
     */
    public function setDateLength($length)
    {
        $this->dateLength = $length;
        return $this;
    }

    /**
     * Get length of the date format to use. One of:
     *
     *  - IntlDateFormatter::SHORT
     *  - IntlDateFormatter::MEDIUM
     *  - IntlDateFormatter::LONG
     *  - IntlDateFormatter::FULL
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
     * Get length of the date format to use.
     * Only applicable with {@link setHTML5(false)}.
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

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);
        return $this;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);
        return $this;
    }

    /**
     * Set default locale for this field. If omitted will default to the current locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get locale for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * @return string
     */
    public function getMinDatetime()
    {
        return $this->minDatetime;
    }

    /**
     * @param string $minDatetime
     * @return $this
     */
    public function setMinDatetime($minDatetime)
    {
        $this->minDatetime = $this->tidyISO8601($minDatetime);
        return $this;
    }

    /**
     * @return string
     */
    public function getMaxDatetime()
    {
        return $this->maxDatetime;
    }

    /**
     * @param string $maxDatetime
     * @return $this
     */
    public function setMaxDatetime($maxDatetime)
    {
        $this->maxDatetime = $this->tidyISO8601($maxDatetime);
        return $this;
    }

    /**
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
                    'DateField.VALIDDATEFORMAT2',
                    "Please enter a valid date format ({format})",
                    ['format' => $this->getDateFormat()]
                )
            );
            return false;
        }

        // Check min date
        $min = $this->getMinDatetime();
        if ($min) {
            $oops = strtotime($this->value) < strtotime($min);
            if ($oops) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'DateField.VALIDDATEMINDATE',
                        "Your date has to be newer or matching the minimum allowed date ({date})",
                        ['date' => $this->iso8601ToLocalised($min)]
                    )
                );
                return false;
            }
        }

        // Check max date
        $max = $this->getMaxDatetime();
        if ($max) {
            $oops = strtotime($this->value) > strtotime($max);
            if ($oops) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'DateField.VALIDDATEMAXDATE',
                        "Your date has to be older or matching the maximum allowed date ({date})",
                        ['date' => $this->iso8601ToLocalised($max)]
                    )
                );
                return false;
            }
        }

        return true;
    }

    public function performReadonlyTransformation()
    {
        $field = clone $this;
        $field->setReadonly(true);
        return $field;
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
