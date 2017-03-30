<?php

namespace SilverStripe\Forms;

use IntlDateFormatter;
use SilverStripe\i18n\i18n;
use InvalidArgumentException;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Form used for editing a date stirng
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 *
 * # Localization
 *
 * Date formatting can be controlled in the below order of priority:
 *  - Format set via setDateFormat()
 *  - Format generated from current locale set by setLocale() and setDateLength()
 *  - Format generated from current locale in i18n
 *
 * You can also specify a setClientLocale() to set the javascript to a specific locale
 * on the frontend. However, this will not override the date format string.
 *
 * See http://doc.silverstripe.org/framework/en/topics/i18n for more information about localizing form fields.
 *
 * # Usage
 *
 * ## Example: Field localised with german date format
 *
 *   $f = new DateField('MyDate');
 *   $f->setLocale('de_DE');
 *
 * # Validation
 *
 * Caution: JavaScript validation is only supported for the 'en_NZ' locale at the moment,
 * it will be disabled automatically for all other locales.
 *
 * # Formats
 *
 * All format strings should follow the CLDR standard as per
 * http://userguide.icu-project.org/formatparse/datetime. These will be converted
 * automatically to jquery UI format.
 *
 * The value of this field in PHP will be ISO 8601 standard (e.g. 2004-02-12), and
 * stores this as a timestamp internally.
 *
 * Note: Do NOT use php date format strings. Date format strings follow the date
 * field symbol table as below.
 *
 * @see http://userguide.icu-project.org/formatparse/datetime
 * @see http://api.jqueryui.com/datepicker/#utility-formatDate
 */
class DateField extends TextField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATE;

    /**
     * Override locale. If empty will default to current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Override date format. If empty will default to that used by the current locale.
     *
     * @var null
     */
    protected $dateFormat = null;

    /**
     * Set if js calendar should popup
     *
     * @var bool
     */
    protected $showCalendar = false;

    /**
     * Length of this date (full, short, etc).
     *
     * @see http://php.net/manual/en/class.intldateformatter.php#intl.intldateformatter-constants
     * @var int
     */
    protected $dateLength = null;

    /**
     * Override locale for client side.
     *
     * @var string
     */
    protected $clientLocale = null;

    /**
     * Min date
     *
     * @var string ISO 8601 date for min date
     */
    protected $minDate = null;

    /**
     * Max date
     *
     * @var string ISO 860 date for max date
     */
    protected $maxDate = null;

    /**
     * Unparsed value, used exclusively for comparing with internal value
     * to detect invalid values.
     *
     * @var mixed
     */
    protected $rawValue = null;

    /**
     * Check if calendar should be shown on the frontend
     *
     * @return bool
     */
    public function getShowCalendar()
    {
        return $this->showCalendar;
    }

    /**
     * Set if calendar should be shown on the frontend.
     * @internal WARNING: Experimental and volatile API.
     *
     * @param bool $show
     * @return $this
     */
    public function setShowCalendar($show)
    {
        $this->showCalendar = $show;
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
    public function getDateLength()
    {
        if ($this->dateLength) {
            return $this->dateLength;
        }
        return IntlDateFormatter::MEDIUM;
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
     * Get date format in CLDR standard format
     *
     * This can be set explicitly. If not, this will be generated from the current locale
     * with the current date length.
     *
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     */
    public function getDateFormat()
    {
        if ($this->dateFormat) {
            return $this->dateFormat;
        }

        // Get from locale
        return $this->getFormatter()->getPattern();
    }

    /**
     * Set date format in CLDR standard format.
     *
     * @see http://userguide.icu-project.org/formatparse/datetime#TOC-Date-Field-Symbol-Table
     * @param string $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * Get date formatter with the standard locale / date format
     *
     * @return IntlDateFormatter
     */
    protected function getFormatter()
    {
        $formatter = IntlDateFormatter::create(
            $this->getLocale(),
            $this->getDateLength(),
            IntlDateFormatter::NONE
        );

        // Don't invoke getDateFormat() directly to avoid infinite loop
        if ($this->dateFormat) {
            $ok = $formatter->setPattern($this->dateFormat);
            if (!$ok) {
                throw new InvalidArgumentException("Invalid date format {$this->dateFormat}");
            }
        }
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
            IntlDateFormatter::NONE
        );
        $formatter->setLenient(false);
        // CLDR iso8601 date.
        $formatter->setPattern('y-MM-dd');
        return $formatter;
    }

    public function FieldHolder($properties = array())
    {
        return $this->renderWithClientView(function () use ($properties) {
            return parent::FieldHolder($properties);
        });
    }

    public function SmallFieldHolder($properties = array())
    {
        return $this->renderWithClientView(function () use ($properties) {
            return parent::SmallFieldHolder($properties);
        });
    }

    /**
     * Generate field with client view enabled
     *
     * @param callable $callback
     * @return string
     */
    protected function renderWithClientView($callback)
    {
        $clientView = null;
        if ($this->getShowCalendar()) {
            $clientView = $this->getClientView();
            $clientView->onBeforeRender();
        }
        $html = $callback();
        if ($clientView) {
            $html = $clientView->onAfterRender($html);
        }
        return $html;
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        // Merge with client config
        $config = $this->getClientConfig();
        foreach ($config as $key => $value) {
            $attributes["data-{$key}"] = $value;
        }

        return $attributes;
    }

    public function Type()
    {
        return 'date text';
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

    public function setValue($value, $data = null)
    {
        // Save raw value for later validation
        $this->rawValue = $value;

        // Null case
        if (!$value) {
            $this->value = null;
            return $this;
        }

        if (is_array($value)) {
            throw new InvalidArgumentException("Use setSubmittedValue to assign by array");
        }

        // Re-run through formatter to tidy up (e.g. remove time component)
        $this->value = $this->tidyISO8601($value);
        return $this;
    }

    public function Value()
    {
        return $this->iso8601ToLocalised($this->value);
    }

    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy(DateField_Disabled::class);
        $field->setValue($this->dataValue());
        $field->setReadonly(true);
        return $field;
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
        $min = $this->getMinDate();
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
        $max = $this->getMaxDate();
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

    /**
     * Get locale to use for this field
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Caution: Will not update the 'dateformat' config value.
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
     * Get locale code for client-side. Will default to getLocale() if omitted.
     *
     * @return string
     */
    public function getClientLocale()
    {
        if ($this->clientLocale) {
            return $this->clientLocale;
        }
        return $this->getLocale();
    }

    /**
     * @param string $clientLocale
     * @return DateField
     */
    public function setClientLocale($clientLocale)
    {
        $this->clientLocale = $clientLocale;
        return $this;
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['date'] = true;
        return $rules;
    }

    /**
     * @return string
     */
    public function getMinDate()
    {
        return $this->minDate;
    }

    /**
     * @param string $minDate
     * @return $this
     */
    public function setMinDate($minDate)
    {
        $this->minDate = $this->tidyISO8601($minDate);
        return $this;
    }

    /**
     * @return string
     */
    public function getMaxDate()
    {
        return $this->maxDate;
    }

    /**
     * @param string $maxDate
     * @return $this
     */
    public function setMaxDate($maxDate)
    {
        $this->maxDate = $this->tidyISO8601($maxDate);
        return $this;
    }

    /**
     * Get client data properties for this field
     *
     * @return array
     */
    public function getClientConfig()
    {
        $view = $this->getClientView();
        $config = [
            'showcalendar' => $this->getShowCalendar() ? 'true' : null,
            'date-format' => $view->getDateFormat(), // https://api.jqueryui.com/datepicker/#option-dateFormat
            'locale' => $view->getLocale(),
        ];

        // Format min/maxDate in format expected by jquery datepicker
        $min = $this->getMinDate();
        if ($min) {
            // https://api.jqueryui.com/datepicker/#option-minDate
            $config['min-date'] = $this->iso8601ToLocalised($min);
        }
        $max = $this->getMaxDate();
        if ($max) {
            // https://api.jqueryui.com/datepicker/#option-maxDate
            $config['max-date'] = $this->iso8601ToLocalised($max);
        }

        return $config;
    }

    /**
     * Convert date localised in the current locale to ISO 8601 date
     *
     * @param string $date
     * @return string The formatted date, or null if not a valid date
     */
    public function localisedToISO8601($date)
    {
        if (!$date) {
            return null;
        }
        $fromFormatter = $this->getFormatter();
        $toFormatter = $this->getISO8601Formatter();
        $timestamp = $fromFormatter->parse($date);
        if ($timestamp === false) {
            return null;
        }
        return $toFormatter->format($timestamp) ?: null;
    }

    /**
     * Convert an ISO 8601 localised date into the format specified by the
     * current date format.
     *
     * @param string $date
     * @return string The formatted date, or null if not a valid date
     */
    public function iso8601ToLocalised($date)
    {
        $date = $this->tidyISO8601($date);
        if (!$date) {
            return null;
        }
        $fromFormatter = $this->getISO8601Formatter();
        $toFormatter = $this->getFormatter();
        $timestamp = $fromFormatter->parse($date);
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
    public function tidyISO8601($date)
    {
        if (!$date) {
            return null;
        }
        // Re-run through formatter to tidy up (e.g. remove time component)
        $formatter = $this->getISO8601Formatter();
        $timestamp = $formatter->parse($date);
        if ($timestamp === false) {
            // Fallback to strtotime
            $timestamp = strtotime($date, DBDatetime::now()->getTimestamp());
            if ($timestamp === false) {
                return null;
            }
        }
        return $formatter->format($timestamp);
    }

    /**
     * @return DateField_View_JQuery
     */
    protected function getClientView()
    {
        return DateField_View_JQuery::create($this);
    }
}
