<?php

namespace SilverStripe\ORM\FieldType;

use IntlDateFormatter;
use InvalidArgumentException;
use SilverStripe\Forms\TimeField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Represents a column in the database with the type 'Time'.
 *
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 *  "StartTime" => "Time",
 * );
 * </code>
 */
class DBTime extends DBField
{
    /**
     * Standard ISO format string for time in CLDR standard format
     */
    const ISO_TIME = 'HH:mm:ss';

    public function setValue($value, $record = null, $markChanged = true)
    {
        $value = $this->parseTime($value);
        if ($value === false) {
            throw new InvalidArgumentException(
                'Invalid date passed. Use ' . $this->getISOFormat() . ' to prevent this error.'
            );
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Parse timestamp or iso8601-ish date into standard iso8601 format
     *
     * @param mixed $value
     * @return string|null|false Formatted time, null if empty but valid, or false if invalid
     */
    protected function parseTime($value)
    {
        // Skip empty values
        if (empty($value) && !is_numeric($value)) {
            return null;
        }

        // Determine value to parse
        if (is_array($value)) {
            $source = $value; // parse array
        } elseif (is_numeric($value)) {
            $source = $value; // parse timestamp
        } else {
            // Convert using strtotime
            $source = strtotime($value ?? '');
        }
        if ($value === false) {
            return false;
        }

        // Format as iso8601
        $formatter = $this->getFormatter();
        $formatter->setPattern($this->getISOFormat());
        return $formatter->format($source);
    }

    /**
     * Get date / time formatter for the current locale
     *
     * @param int $timeLength
     * @return IntlDateFormatter
     */
    public function getFormatter($timeLength = IntlDateFormatter::MEDIUM)
    {
        return IntlDateFormatter::create(i18n::get_locale(), IntlDateFormatter::NONE, $timeLength);
    }

    /**
     * Returns the date in the localised short format
     *
     * @return string
     */
    public function Short()
    {
        if (!$this->value) {
            return null;
        }
        $formatter = $this->getFormatter(IntlDateFormatter::SHORT);
        return $formatter->format($this->getTimestamp());
    }

    /**
     * Returns the standard localised medium time
     * e.g. "3:15pm"
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->value) {
            return null;
        }
        $formatter = $this->getFormatter();
        return $formatter->format($this->getTimestamp());
    }

    /**
     * Return the time using a particular formatting string.
     *
     * @param string $format Format code string. See https://unicode-org.github.io/icu/userguide/format_parse/datetime
     * @return string The time in the requested format
     */
    public function Format($format)
    {
        if (!$this->value) {
            return null;
        }
        $formatter = $this->getFormatter();
        $formatter->setPattern($format);
        return $formatter->format($this->getTimestamp());
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'time',
            'arrayValue' => $this->arrayValue
        ];
        $values = [
            'type' => 'time',
            'parts' => $parts
        ];
        DB::require_field($this->tableName, $this->name, $values);
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return TimeField::create($this->name, $title);
    }

    /**
     * Return a time formatted as per a CMS user's settings.
     *
     * @param Member $member
     * @return string A time formatted as per user-defined settings.
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

        // Get user format
        $format = $member->getTimeFormat();
        return $this->Format($format);
    }

    /**
     * Get standard ISO time format string
     *
     * @return string
     */
    public function getISOFormat()
    {
        return DBTime::ISO_TIME;
    }

    /**
     * Get unix timestamp for this time
     *
     * @return int
     */
    public function getTimestamp()
    {
        if ($this->value) {
            return strtotime($this->value ?? '');
        }
        return 0;
    }
}
