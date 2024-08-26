<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Control\HTTP;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Parsers\HTMLValue;
use SilverStripe\View\Parsers\ShortcodeParser;

/**
 * Represents a large text field that contains HTML content.
 * This behaves similarly to {@link Text}, but the template processor won't escape any HTML content within it.
 *
 * Options can be specified in a $db config via one of the following:
 *  - "HTMLFragment(['shortcodes' => true, 'whitelist' => 'meta,link'])"
 *  - "HTMLFragment(['whitelist' => 'meta,link'])"
 *  - "HTMLFragment(['shortcodes' => true])". "HTMLText" is also a synonym for this.
 *  - "HTMLFragment(['shortcodes' => true])"
 *
 * @see HTMLVarchar
 * @see Text
 * @see Varchar
 */
class DBHTMLText extends DBText
{
    private static string $escape_type = 'xml';

    private static array $casting = [
        "AbsoluteLinks" => "HTMLFragment",
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
     * List of html properties to whitelist
     */
    protected array $whitelist = [];

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
     * List of html properties to whitelist
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * Set list of html properties to whitelist
     */
    public function setWhitelist(string|array $whitelist): static
    {
        if (!is_array($whitelist)) {
            $whitelist = preg_split('/\s*,\s*/', $whitelist);
        }
        $this->whitelist = $whitelist;
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

        if (array_key_exists("whitelist", $options ?? [])) {
            $this->setWhitelist($options['whitelist']);
        }

        return parent::setOptions($options);
    }

    public function RAW(): ?string
    {
        if ($this->processShortcodes) {
            return ShortcodeParser::get_active()->parse($this->value);
        }
        return $this->value;
    }

    /**
     * Return the value of the field with relative links converted to absolute urls (with placeholders parsed).
     */
    public function AbsoluteLinks(): string
    {
        return HTTP::absoluteURLs($this->forTemplate());
    }

    public function forTemplate(): string
    {
        // Suppress XML encoding for DBHtmlText
        return $this->RAW() ?? '';
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

    public function prepValueForDB(mixed $value): array|string|null
    {
        return parent::prepValueForDB($this->whitelistContent($value));
    }

    /**
     * Filter the given $value string through the whitelist filter
     *
     * @param string $value Input html content
     * @return string Value with all non-whitelisted content stripped (if applicable)
     */
    public function whitelistContent(mixed $value): mixed
    {
        if ($this->whitelist) {
            $dom = HTMLValue::create($value);

            $query = [];
            $textFilter = ' | //body/text()';
            foreach ($this->whitelist as $tag) {
                if ($tag === 'text()') {
                    $textFilter = ''; // Disable text filter if allowed
                } else {
                    $query[] = 'not(self::' . $tag . ')';
                }
            }

            foreach ($dom->query('//body//*[' . implode(' and ', $query) . ']' . $textFilter) as $el) {
                if ($el->parentNode) {
                    $el->parentNode->removeChild($el);
                }
            }

            $value = $dom->getContent();
        }
        return $value;
    }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return HTMLEditorField::create($this->name, $title);
    }

    public function scaffoldSearchField(?string $title = null): ?FormField
    {
        return new TextField($this->name, $title);
    }

    /**
     * Get plain-text version
     */
    public function Plain(): string
    {
        // Preserve line breaks
        $text = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $this->RAW() ?? '');

        // Convert paragraph breaks to multi-lines
        $text = preg_replace('/\<\/p\>/i', "\n\n", $text ?? '');

        // Strip out HTML tags
        $text = strip_tags($text ?? '');

        // Implode >3 consecutive linebreaks into 2
        $text = preg_replace('~(\R){2,}~u', "\n\n", $text ?? '');

        // Decode HTML entities back to plain text
        return trim(Convert::xml2raw($text) ?? '');
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
