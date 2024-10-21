<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Model\ModelData;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;

/**
 * An abstract base class for the string field types (i.e. Varchar and Text)
 */
abstract class DBString extends DBField
{
    private static array $field_validators = [
        StringFieldValidator::class,
    ];

    private static array $casting = [
        'LimitCharacters' => 'Text',
        'LimitCharactersToClosestWord' => 'Text',
        'LimitWordCount' => 'Text',
        'LowerCase' => 'Text',
        'UpperCase' => 'Text',
        'Plain' => 'Text',
    ];

    /**
     * Set the default value for "nullify empty" and 'default'
     *
     * {@inheritDoc}
     */
    public function __construct($name = null, $options = [])
    {
        $this->options['nullifyEmpty'] = true;
        $this->options['default'] = '';
        parent::__construct($name, $options);
    }

    /**
     * Update the optional parameters for this field.
     *
     * The options allowed are:
     *   <ul><li>"nullifyEmpty"
     *       This is a boolean flag.
     *       True (the default) means that empty strings are automatically converted to nulls to be stored in
     *       the database. Set it to false to ensure that nulls and empty strings are kept intact in the database.
     *   </li></ul>
     */
    public function setOptions(array $options = []): static
    {
        parent::setOptions($options);

        if (array_key_exists('nullifyEmpty', $options ?? [])) {
            $this->options['nullifyEmpty'] = (bool) $options['nullifyEmpty'];
        }
        if (array_key_exists('default', $options ?? [])) {
            $this->setDefaultValue($options['default']);
        }

        return $this;
    }

    /**
     * Set whether this field stores empty strings rather than converting
     * them to null.
     *
     * @param $value boolean True if empty strings are to be converted to null
     * @return $this
     */
    public function setNullifyEmpty(bool $value): static
    {
        $this->options['nullifyEmpty'] = $value;
        return $this;
    }

    /**
     * Get whether this field stores empty strings rather than converting
     * them to null
     *
     * @return boolean True if empty strings are to be converted to null
     */
    public function getNullifyEmpty(): bool
    {
        return !empty($this->options['nullifyEmpty']);
    }

    public function exists(): bool
    {
        $value = $this->RAW();
        // All truthy values and non-empty strings exist ('0' but not (int)0)
        return $value || (is_string($value) && strlen($value ?? ''));
    }

    public function prepValueForDB(mixed $value): array|string|null
    {
        // Cast non-empty value
        if (is_scalar($value) && strlen($value ?? '')) {
            return (string)$value;
        }

        // Return "empty" value
        if ($this->getNullifyEmpty() || $value === null) {
            return null;
        }
        return '';
    }

    public function forTemplate(): string
    {
        return nl2br(parent::forTemplate() ?? '');
    }

    /**
     * Limit this field's content by a number of characters.
     * This makes use of strip_tags() to avoid malforming the
     * HTML tags in the string of text.
     *
     * @param int $limit Number of characters to limit by
     * @param string|false $add Ellipsis to add to the end of truncated string
     */
    public function LimitCharacters(int $limit = 20, string|false $add = false): string
    {
        $value = $this->Plain();
        if (mb_strlen($value ?? '') <= $limit) {
            return $value;
        }
        return $this->addEllipsis(mb_substr($value ?? '', 0, $limit), $add);
    }

    /**
     * Limit this field's content by a number of characters and truncate
     * the field to the closest complete word. All HTML tags are stripped
     * from the field.
     *
     * @param int $limit Number of characters to limit by
     * @param string|false $add Ellipsis to add to the end of truncated string
     * @return string Plain text value with limited characters
     */
    public function LimitCharactersToClosestWord(int $limit = 20, string|false $add = false): string
    {
        // Safely convert to plain text
        $value = $this->Plain();

        // Determine if value exceeds limit before limiting characters
        if (mb_strlen($value ?? '') <= $limit) {
            return $value;
        }

        // Limit to character limit
        $value = mb_substr($value ?? '', 0, $limit);

        // If value exceeds limit, strip punctuation off the end to the last space and apply ellipsis
        $value = $this->addEllipsis(
            preg_replace(
                '/[^\w_]+$/',
                '',
                mb_substr($value ?? '', 0, mb_strrpos($value ?? '', " "))
            ),
            $add
        );
        return $value;
    }

    /**
     * Limit this field's content by a number of words.
     *
     * @param int $numWords Number of words to limit by.
     * @param string|false $add Ellipsis to add to the end of truncated string.
     */
    public function LimitWordCount(int $numWords = 26, string|false $add = false): string
    {
        $value = $this->Plain();
        $words = explode(' ', $value ?? '');
        if (count($words ?? []) <= $numWords) {
            return $value;
        }

        // Limit
        $words = array_slice($words ?? [], 0, $numWords);
        return $this->addEllipsis(implode(' ', $words), $add);
    }

    /**
     * Converts the current value for this StringField to lowercase.
     *
     * @return string Text with lowercase (HTML for some subclasses)
     */
    public function LowerCase(): string
    {
        return mb_strtolower($this->RAW() ?? '');
    }

    /**
     * Converts the current value for this StringField to uppercase.
     *
     * @return string Text with uppercase (HTML for some subclasses)
     */
    public function UpperCase(): string
    {
        return mb_strtoupper($this->RAW() ?? '');
    }

    /**
     * Plain text version of this string
     */
    public function Plain(): string
    {
        return trim($this->RAW() ?? '');
    }

    /**
     * Swap add for defaultEllipsis if need be
     */
    private function addEllipsis(string $string, string|false $add): string
    {
        if ($add === false) {
            $add = $this->defaultEllipsis();
        }

        return $string . $add;
    }

    /**
     * Get the default string to indicate that a string was cut off.
     */
    public function defaultEllipsis(): string
    {
        return _t(DBString::class . '.ELLIPSIS', '…');
    }
}
