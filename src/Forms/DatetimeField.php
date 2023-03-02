<?php

namespace SilverStripe\Forms;

use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;

/**
 * Form field used for editing date time strings.
 * In the default HTML5 mode, the field expects form submissions
 * in normalised ISO 8601 format, for example 2017-04-26T23:59:59 (with a "T" separator).
 * Data is passed on via {@link dataValue()} with whitespace separators.
 * The {@link $value} property is always in ISO 8601 format, in the server timezone.
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

    protected $inputType = 'datetime-local';

    /**
     * Min date time
     *
     * @var string ISO 8601 date time in server timezone
     */
    protected $minDatetime = null;

    /**
     * Max date time
     *
     * @var string ISO 860 date time in server timezone
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

    /**
     * @inheritDoc
     */
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATETIME;

    /**
     * Custom timezone
     *
     * @var string
     */
    protected $timezone = null;

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $attributes['lang'] = i18n::convert_rfc1766($this->getLocale());

        if ($this->getHTML5()) {
            $attributes['min'] = $this->internalToFrontend($this->getMinDatetime());
            $attributes['max'] = $this->internalToFrontend($this->getMaxDatetime());
        } else {
            $attributes['type'] = 'text';
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        return array_merge($defaults, [
            'lang' => i18n::convert_rfc1766($this->getLocale()),
            'data' => array_merge($defaults['data'], [
                'html5' => $this->getHTML5(),
                'min' => $this->internalToFrontend($this->getMinDatetime()),
                'max' => $this->internalToFrontend($this->getMaxDatetime())
            ])
        ]);
    }

    /**
     * @inheritDoc
     */
    public function Type()
    {
        return 'text datetime';
    }

    /**
     * @return bool
     */
    public function getHTML5()
    {
        return $this->html5;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setHTML5($bool)
    {
        $this->html5 = $bool;
        return $this;
    }

    /**
     * Assign value posted from form submission, based on {@link $datetimeFormat}.
     * When $html5=true, this needs to be normalised ISO format (with "T" separator).
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
        $this->value = $this->frontendToInternal($value);

        return $this;
    }

    /**
     * Convert frontend date to the internal representation (ISO 8601).
     * The frontend date is also in ISO 8601 when $html5=true.
     * Assumes the value is in the defined {@link $timezone} (if one is set),
     * and adjusts for server timezone.
     *
     * @param string $datetime
     * @return string The formatted date, or null if not a valid date
     */
    public function frontendToInternal($datetime)
    {
        if (!$datetime) {
            return null;
        }
        $fromFormatter = $this->getFrontendFormatter();
        $toFormatter = $this->getInternalFormatter();

        // Try to parse time with seconds
        $timestamp = $fromFormatter->parse($datetime);

        // Try to parse time without seconds, since that's a valid HTML5 submission format
        // See https://html.spec.whatwg.org/multipage/infrastructure.html#times
        if ($timestamp === false && $this->getHTML5()) {
            $fromFormatter->setPattern(str_replace(':ss', '', $fromFormatter->getPattern() ?? ''));
            $timestamp = $fromFormatter->parse($datetime);
        }

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
    protected function getFrontendFormatter()
    {
        if ($this->getHTML5() && $this->datetimeFormat && $this->datetimeFormat !== DBDatetime::ISO_DATETIME_NORMALISED) {
            throw new \LogicException(
                'Please opt-out of HTML5 processing of ISO 8601 dates via setHTML5(false) if using setDatetimeFormat()'
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
            // Browsers expect ISO 8601 dates, localisation is handled on the client.
            // Add 'T' date and time separator to create W3C compliant format
            $formatter->setPattern(DBDatetime::ISO_DATETIME_NORMALISED);
        } elseif ($this->datetimeFormat) {
            // Don't invoke getDatetimeFormat() directly to avoid infinite loop
            $ok = $formatter->setPattern($this->datetimeFormat);
            if (!$ok) {
                throw new InvalidArgumentException("Invalid date format {$this->datetimeFormat}");
            }
        }
        return $formatter;
    }

    /**
     * Get date format in CLDR standard format
     *
     * This can be set explicitly. If not, this will be generated from the current locale
     * with the current date length.
     *
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     */
    public function getDatetimeFormat()
    {
        if ($this->datetimeFormat) {
            return $this->datetimeFormat;
        }

        // Get from locale
        return $this->getFrontendFormatter()->getPattern();
    }

    /**
     * Set date format in CLDR standard format.
     * Only applicable with {@link setHTML5(false)}.
     *
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     * @param string $format
     * @return $this
     */
    public function setDatetimeFormat($format)
    {
        $this->datetimeFormat = $format;
        return $this;
    }

    /**
     * Get a date formatter for the ISO 8601 format
     *
     * @param string $timezone Optional timezone identifier (defaults to server timezone)
     * @return IntlDateFormatter
     */
    protected function getInternalFormatter($timezone = null)
    {
        if (!$timezone) {
            $timezone = date_default_timezone_get(); // Default to server timezone
        }

        $formatter = IntlDateFormatter::create(
            DBDate::ISO_LOCALE,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::MEDIUM,
            $timezone
        );
        $formatter->setLenient(false);

        // Note we omit timezone from this format, and we always assume server TZ
        $formatter->setPattern(DBDatetime::ISO_DATETIME);

        return $formatter;
    }

    /**
     * Assign value based on {@link $datetimeFormat}, which might be localised.
     * The value needs to be in the server timezone.
     *
     * When $html5=true, assign value from ISO 8601 normalised string (with a "T" separator).
     * Falls back to an ISO 8601 string (with a whitespace separator).
     *
     * @param mixed $value
     * @param mixed $data
     * @return $this
     */
    public function setValue($value, $data = null)
    {
        // Save raw value for later validation
        $this->rawValue = $value;

        // Empty value
        if (empty($value)) {
            $this->value = null;
            return $this;
        }

        // Validate iso 8601 date
        // If invalid, assign for later validation failure
        $internalFormatter = $this->getInternalFormatter();
        $timestamp = $internalFormatter->parse($value);

        // Retry with "T" separator
        if (!$timestamp) {
            $fallbackFormatter = $this->getInternalFormatter();
            $fallbackFormatter->setPattern(DBDatetime::ISO_DATETIME_NORMALISED);
            $timestamp = $fallbackFormatter->parse($value);
        }

        if ($timestamp === false) {
            return $this;
        }

        // Cleanup date
        $value = $internalFormatter->format($timestamp);

        // Save value
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the frontend representation of the field value,
     * according to the defined {@link dateFormat}.
     * With $html5=true, this will be in ISO 8601 format.
     *
     * @return string
     */
    public function Value()
    {
        return $this->internalToFrontend($this->value);
    }

    /**
     * Convert the internal date representation (ISO 8601) to a format used by the frontend,
     * as defined by {@link $dateFormat}. With $html5=true, the frontend date will also be
     * in ISO 8601.
     *
     * @param string $datetime
     * @return string The formatted date and time, or null if not a valid date and time
     */
    public function internalToFrontend($datetime)
    {
        $datetime = $this->tidyInternal($datetime);
        if (!$datetime) {
            return null;
        }
        $fromFormatter = $this->getInternalFormatter();
        $toFormatter = $this->getFrontendFormatter();
        $timestamp = $fromFormatter->parse($datetime);
        if ($timestamp === false) {
            return null;
        }

        return $toFormatter->format($timestamp) ?: null;
    }

    /**
     * Tidy up the internal date representation (ISO 8601),
     * and fall back to strtotime() if there's parsing errors.
     *
     * @param string $datetime Date in ISO 8601 or approximate form
     * @return string ISO 8601 date, or null if not valid
     */
    public function tidyInternal($datetime)
    {
        if (!$datetime) {
            return null;
        }
        // Re-run through formatter to tidy up (e.g. remove time component)
        $formatter = $this->getInternalFormatter();
        $timestamp = $formatter->parse($datetime);
        if ($timestamp === false) {
            // Fallback to strtotime
            $timestamp = strtotime($datetime ?? '', DBDatetime::now()->getTimestamp());
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
     * @return string Date in ISO 8601 format, in server timezone.
     */
    public function getMinDatetime()
    {
        return $this->minDatetime;
    }

    /**
     * @param string $minDatetime A string in ISO 8601 format, in server timezone.
     * @return $this
     */
    public function setMinDatetime($minDatetime)
    {
        $this->minDatetime = $this->tidyInternal($minDatetime);
        return $this;
    }

    /**
     * @return string Date in ISO 8601 format, in server timezone.
     */
    public function getMaxDatetime()
    {
        return $this->maxDatetime;
    }

    /**
     * @param string $maxDatetime A string in ISO 8601 format, in server timezone.
     * @return $this
     */
    public function setMaxDatetime($maxDatetime)
    {
        $this->maxDatetime = $this->tidyInternal($maxDatetime);
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
            return $this->extendValidationResult(true, $validator);
        }

        // We submitted a value, but it couldn't be parsed
        if (empty($this->value)) {
            $validator->validationError(
                $this->name,
                _t(
                    __CLASS__ . '.VALIDDATETIMEFORMAT',
                    "Please enter a valid date and time format ({format})",
                    ['format' => $this->getDatetimeFormat()]
                )
            );
            return $this->extendValidationResult(false, $validator);
        }

        // Check min date (in server timezone)
        $min = $this->getMinDatetime();
        if ($min) {
            $oops = strtotime($this->value ?? '') < strtotime($min ?? '');
            if ($oops) {
                $validator->validationError(
                    $this->name,
                    _t(
                        __CLASS__ . '.VALIDDATETIMEMINDATE',
                        "Your date has to be newer or matching the minimum allowed date and time ({datetime})",
                        [
                            'datetime' => sprintf(
                                '<time datetime="%s">%s</time>',
                                $min,
                                $this->internalToFrontend($min)
                            )
                        ]
                    ),
                    ValidationResult::TYPE_ERROR,
                    ValidationResult::CAST_HTML
                );
                return $this->extendValidationResult(false, $validator);
            }
        }

        // Check max date (in server timezone)
        $max = $this->getMaxDatetime();
        if ($max) {
            $oops = strtotime($this->value ?? '') > strtotime($max ?? '');
            if ($oops) {
                $validator->validationError(
                    $this->name,
                    _t(
                        __CLASS__ . '.VALIDDATEMAXDATETIME',
                        "Your date has to be older or matching the maximum allowed date and time ({datetime})",
                        [
                            'datetime' => sprintf(
                                '<time datetime="%s">%s</time>',
                                $max,
                                $this->internalToFrontend($max)
                            )
                        ]
                    ),
                    ValidationResult::TYPE_ERROR,
                    ValidationResult::CAST_HTML
                );
                return $this->extendValidationResult(false, $validator);
            }
        }

        return $this->extendValidationResult(true, $validator);
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
}
