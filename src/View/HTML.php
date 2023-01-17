<?php

namespace SilverStripe\View;

use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;

/**
 * HTML Helper class
 */
class HTML
{
    use Configurable;

    /**
     * List of HTML5 void elements
     *
     * @see https://www.w3.org/TR/html51/syntax.html#void-elements
     * @config
     * @var array
     */
    private static $void_elements = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

    /**
     * List of attributes that should be rendered even if they contain no value
     *
     * @config
     * @var array
     */
    private static $legal_empty_attributes = [
        'alt',
    ];

    /**
     * Construct and return HTML tag.
     *
     * @param string $tag
     * @param array $attributes
     * @param string $content Content to use between two tags. Not valid for void elements (e.g. link)
     * @return string
     */
    public static function createTag($tag, $attributes, $content = null)
    {
        $tag = strtolower($tag ?? '');

        // Build list of arguments
        $legalEmptyAttributes = static::config()->get('legal_empty_attributes');
        $preparedAttributes = '';
        foreach ($attributes as $attributeKey => $attributeValue) {
            $whitelisted = in_array($attributeKey, $legalEmptyAttributes ?? []);

            // Only set non-empty strings (ensures strlen(0) > 0)
            if (strlen($attributeValue ?? '') > 0 || $whitelisted) {
                $preparedAttributes .= sprintf(
                    ' %s="%s"',
                    $attributeKey,
                    Convert::raw2att($attributeValue)
                );
            }
        }

        // Check void element type
        if (in_array($tag, static::config()->get('void_elements') ?? [])) {
            if ($content) {
                throw new InvalidArgumentException("Void element \"{$tag}\" cannot have content");
            }
            return "<{$tag}{$preparedAttributes}>";
        }

        // Closed tag type
        return "<{$tag}{$preparedAttributes}>{$content}</{$tag}>";
    }
}
