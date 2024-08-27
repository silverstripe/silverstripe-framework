<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
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
    private static string $escape_type = 'xml';

    private static array $casting = [
        // DBString conversion / summary methods
        // Not overridden, but returns HTML instead of plain text.
        "LowerCase" => "HTMLFragment",
        "UpperCase" => "HTMLFragment",
    ];

    /**
     * Enable shortcode parsing on this field
     */
    protected bool $processShortcodes = false;

    /**
     * Check if shortcodes are enabled
     */
    public function getProcessShortcodes(): bool
    {
        return $this->processShortcodes;
    }

    /**
     * Set shortcodes on or off by default
     */
    public function setProcessShortcodes(bool $process): static
    {
        $this->processShortcodes = $process;
        return $this;
    }

    /**
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
     */
    public function setOptions(array $options = []): static
    {
        if (array_key_exists("shortcodes", $options ?? [])) {
            $this->setProcessShortcodes(!!$options["shortcodes"]);
        }

        return parent::setOptions($options);
    }

    public function forTemplate(): string
    {
        // Suppress XML encoding for DBHtmlText
        return $this->RAW() ?? '';
    }

    public function RAW(): ?string
    {
        if ($this->processShortcodes) {
            return ShortcodeParser::get_active()->parse($this->value);
        }
        return $this->value;
    }

    /**
     * Safely escape for XML string
     */
    public function CDATA(): string
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
     */
    public function Plain(): string
    {
        // Strip out HTML
        $text = strip_tags($this->RAW() ?? '');

        // Convert back to plain text
        return trim(Convert::xml2raw($text) ?? '');
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return HTMLEditorField::create($this->name, $title);
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return TextField::create($this->name, $title);
    }

    public function getSchemaValue(): ?array
    {
        // Form schema format as HTML
        $value = $this->RAW();
        if ($value) {
            return [ 'html' => $this->RAW() ];
        }
        return null;
    }

    public function exists(): bool
    {
        // Optimisation: don't process shortcode just for ->exists()
        $value = $this->getValue();
        // All truthy values and non-empty strings exist ('0' but not (int)0)
        return $value || (is_string($value) && strlen($value ?? ''));
    }
}
