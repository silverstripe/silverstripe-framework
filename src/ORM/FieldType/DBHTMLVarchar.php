<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Represents a short text field that is intended to contain HTML content.
 *
 * This behaves similarly to Varchar, but the template processor won't escape any HTML content within it.
 */
class DBHTMLVarchar extends DBVarchar
{

    private static $escape_type = 'xml';

    private static $casting = [
        // DBString conversion / summary methods
        // Not overridden, but returns HTML instead of plain text.
        "LowerCase" => "HTMLFragment",
        "UpperCase" => "HTMLFragment",
    ];

    /**
     * Enable shortcode parsing on this field
     *
     * @var bool
     */
    protected $processShortcodes = false;

    /**
     * Check if shortcodes are enabled
     *
     * @return bool
     */
    public function getProcessShortcodes()
    {
        return $this->processShortcodes;
    }

    /**
     * Set shortcodes on or off by default
     *
     * @param bool $process
     * @return $this
     */
    public function setProcessShortcodes($process)
    {
        $this->processShortcodes = (bool)$process;
        return $this;
    }
    /**
     * @param array $options
     *
     * Options accepted in addition to those provided by Text:
     *
     *   - shortcodes: If true, shortcodes will be turned into the appropriate HTML.
     *                 If false, shortcodes will not be processed.
     *
     *   - whitelist: If provided, a comma-separated list of elements that will be allowed to be stored
     *                (be careful on relying on this for XSS protection - some seemingly-safe elements allow
     *                attributes that can be exploited, for instance <img onload="exploiting_code();" src="..." />)
     *                Text nodes outside of HTML tags are filtered out by default, but may be included by adding
     *                the text() directive. E.g. 'link,meta,text()' will allow only <link /> <meta /> and text at
     *                the root level.
     *
     * @return $this
     */
    public function setOptions(array $options = [])
    {
        if (array_key_exists("shortcodes", $options ?? [])) {
            $this->setProcessShortcodes(!!$options["shortcodes"]);
        }

        return parent::setOptions($options);
    }

    public function forTemplate()
    {
        // Suppress XML encoding for DBHtmlText
        return $this->RAW();
    }

    public function RAW()
    {
        if ($this->processShortcodes) {
            return ShortcodeParser::get_active()->parse($this->value);
        }
        return $this->value;
    }

    /**
     * Safely escape for XML string
     *
     * @return string
     */
    public function CDATA()
    {
        return sprintf(
            '<![CDATA[%s]]>',
            str_replace(']]>', ']]]]><![CDATA[>', $this->RAW() ?? '')
        );
    }

    /**
     * Get plain-text version.
     *
     * Note: unlike DBHTMLText, this doesn't respect line breaks / paragraphs
     *
     * @return string
     */
    public function Plain()
    {
        // Strip out HTML
        $text = strip_tags($this->RAW() ?? '');

        // Convert back to plain text
        return trim(Convert::xml2raw($text) ?? '');
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return HTMLEditorField::create($this->name, $title);
    }

    public function scaffoldSearchField($title = null)
    {
        return TextField::create($this->name, $title);
    }

    public function getSchemaValue()
    {
        // Form schema format as HTML
        $value = $this->RAW();
        if ($value) {
            return [ 'html' => $this->RAW() ];
        }
        return null;
    }

    public function exists()
    {
        // Optimisation: don't process shortcode just for ->exists()
        $value = $this->getValue();
        // All truthy values and non-empty strings exist ('0' but not (int)0)
        return $value || (is_string($value) && strlen($value ?? ''));
    }
}
