<?php

namespace SilverStripe\ORM\FieldType;

use Exception;
use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Represents a date-time field.
 * The field currently supports New Zealand date format (DD/MM/YYYY),
 * or an ISO 8601 formatted date and time (Y-m-d H:i:s).
 * Alternatively you can set a timestamp that is evaluated through
 * PHP's built-in date() and strtotime() function according to your system locale.
 *
 * For all computations involving the current date and time,
 * please use {@link DBDatetime::now()} instead of PHP's built-in date() and time()
 * methods. This ensures that all time-based computations are testable with mock dates
 * through {@link DBDatetime::set_mock_now()}.
 *
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 *  "Expires" => "DBDatetime",
 * );
 * </code>
 *
 */
class DBDatetime extends DBDate implements TemplateGlobalProvider
{
    /**
     * Standard ISO format string for date and time in CLDR standard format,
     * with a whitespace separating date and time (common database representation, e.g. in MySQL).
     */
    const ISO_DATETIME = 'y-MM-dd HH:mm:ss';

    /**
     * Standard ISO format string for date and time in CLDR standard format,
     * with a "T" separator between date and time (W3C standard, e.g. for HTML5 datetime-local fields).
     */
    const ISO_DATETIME_NORMALISED = 'y-MM-dd\'T\'HH:mm:ss';

    /**
     * Flag idicating if this field is considered immutable
     * when this is enabled setting the value of this field will return a new field instance
     * instead updatin the old one
     *
     * @var bool
     */
    protected $immutable = false;

    /**
     * @param bool $immutable
     * @return $this
     */
    public function setImmutable(bool $immutable): DBDatetime
    {
        $this->immutable = $immutable;

        return $this;
    }

    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($this->immutable) {
            // This field is set as immutable so we have to create a new field instance
            // instead of just updating the value
            $field = clone $this;

            return $field
                // This field will inherit the immutable status but we have to disable it before setting the value
                // to avoid recursion
                ->setImmutable(false)
                // Update the value so the new field contains the desired value
                ->setValue($value, $record, $markChanged)
                // Return the immutable flag to the correct state
                ->setImmutable(true);
        }

        return parent::setValue($value, $record, $markChanged);
    }

    /**
     * Returns the standard localised date
     *
     * @return string Formatted date.
     */
    public function Date()
    {
        $formatter = $this->getFormatter(IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        return $formatter->format($this->getTimestamp());
    }

    /**
     * Returns the standard localised time
     *
     * @return string Formatted time.
     */
    public function Time()
    {
        $formatter = $this->getFormatter(IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM);
        return $formatter->format($this->getTimestamp());
    }

    /**
     * Returns the time in 12-hour format using the format string 'h:mm a' e.g. '1:32 pm'.
     *
     * @return string Formatted time.
     */
    public function Time12()
    {
        return $this->Format('h:mm a');
    }

    /**
     * Returns the time in 24-hour format using the format string 'H:mm' e.g. '13:32'.
     *
     * @return string Formatted time.
     */
    public function Time24()
    {
        return $this->Format('H:mm');
    }

    /**
     * Return a date and time formatted as per a CMS user's settings.
     *
     * @param Member $member
     * @return boolean|string A time and date pair formatted as per user-defined settings.
     */
    public function FormatFromSettings($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // Fall back to nice
        if (!$member) {
            return $this->Nice();
        }

        $dateFormat = $member->getDateFormat();
        $timeFormat = $member->getTimeFormat();

        // Get user format
        return $this->Format($dateFormat . ' ' . $timeFormat, $member->getLocale());
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'datetime',
            'arrayValue' => $this->arrayValue
        ];
        $values = [
            'type' => 'datetime',
            'parts' => $parts
        ];
        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * Returns the url encoded date and time in ISO 6801 format using format
     * string 'y-MM-dd%20HH:mm:ss' e.g. '2014-02-28%2013:32:22'.
     *
     * @return string Formatted date and time.
     */
    public function URLDatetime()
    {
        return rawurlencode($this->Format(DBDatetime::ISO_DATETIME, DBDatetime::ISO_LOCALE) ?? '');
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        $field = DatetimeField::create($this->name, $title);
        $dateTimeFormat = $field->getDatetimeFormat();
        $locale = $field->getLocale();

        // Set date formatting hints and example
        $date = static::now()->Format($dateTimeFormat, $locale);
        $field
            ->setDescription(_t(
                'SilverStripe\\Forms\\FormField.EXAMPLE',
                'e.g. {format}',
                'Example format',
                ['format' => $date]
            ))
            ->setAttribute('placeholder', $dateTimeFormat);

        return $field;
    }

    /**
     *
     */
    protected static $mock_now = null;

    /**
     * Returns either the current system date as determined
     * by date(), or a mocked date through {@link set_mock_now()}.
     *
     * @return static
     */
    public static function now()
    {
        $time = DBDatetime::$mock_now ? DBDatetime::$mock_now->Value : time();

        /** @var DBDatetime $now */
        $now = DBField::create_field('Datetime', $time);

        return $now;
    }

    /**
     * Mock the system date temporarily, which is useful for time-based unit testing.
     * Use {@link clear_mock_now()} to revert to the current system date.
     * Caution: This sets a fixed date that doesn't increment with time.
     *
     * @param DBDatetime|string $datetime Either in object format, or as a DBDatetime compatible string.
     * @throws Exception
     */
    public static function set_mock_now($datetime)
    {
        if (!$datetime instanceof DBDatetime) {
            $value = $datetime;
            $datetime = DBField::create_field('Datetime', $datetime);
            if ($datetime === false) {
                throw new InvalidArgumentException('DBDatetime::set_mock_now(): Wrong format: ' . $value);
            }
        }
        DBDatetime::$mock_now = $datetime;
    }

    /**
     * Clear any mocked date, which causes
     * {@link Now()} to return the current system date.
     */
    public static function clear_mock_now()
    {
        DBDatetime::$mock_now = null;
    }

    /**
     * Run a callback with specific time, original mock value is retained after callback
     *
     * @param DBDatetime|string $time
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public static function withFixedNow($time, $callback)
    {
        $original = DBDatetime::$mock_now;

        try {
            DBDatetime::set_mock_now($time);

            return $callback();
        } finally {
            DBDatetime::$mock_now = $original;
        }
    }

    public static function get_template_global_variables()
    {
        return [
            'Now' => ['method' => 'now', 'casting' => 'Datetime'],
        ];
    }

    /**
     * Get date / time formatter for the current locale
     *
     * @param int $dateLength
     * @param int $timeLength
     * @return IntlDateFormatter
     */
    public function getFormatter($dateLength = IntlDateFormatter::MEDIUM, $timeLength = IntlDateFormatter::SHORT)
    {
        return parent::getFormatter($dateLength, $timeLength);
    }


    /**
     * Return formatter in a given locale. Useful if localising in a format other than the current locale.
     *
     * @param string|null $locale The current locale, or null to use default
     * @param string|null $pattern Custom pattern to use for this, if required
     * @param int $dateLength
     * @param int $timeLength
     * @return IntlDateFormatter
     */
    public function getCustomFormatter(
        $locale = null,
        $pattern = null,
        $dateLength = IntlDateFormatter::MEDIUM,
        $timeLength = IntlDateFormatter::MEDIUM
    ) {
        return parent::getCustomFormatter($locale, $pattern, $dateLength, $timeLength);
    }

    /**
     * Formatter used internally
     *
     * @internal
     * @return IntlDateFormatter
     */
    protected function getInternalFormatter()
    {
        $formatter = $this->getCustomFormatter(DBDate::ISO_LOCALE, DBDatetime::ISO_DATETIME);
        $formatter->setLenient(false);
        return $formatter;
    }

    /**
     * Get standard ISO date format string
     *
     * @return string
     */
    public function getISOFormat()
    {
        return DBDatetime::ISO_DATETIME;
    }
}
