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
     * Construct and return HTML tag.
     *
     * @param string $tag
     * @param array $attributes
     * @param string $content Content to use between two tags. Not valid for void elements (e.g. link)
     * @return string
     */
    public static function createTag($tag, $attributes, $content = null)
    {
        $tag = strtolower($tag);

        // Build list of arguments
        $preparedAttributes = '';
        foreach ($attributes as $attributeKey => $attributeValue) {
            // Only set non-empty strings (ensures strlen(0) > 0)
            if (strlen($attributeValue) > 0) {
                $preparedAttributes .= sprintf(
                    ' %s="%s"',
                    $attributeKey,
                    Convert::raw2att($attributeValue)
                );
            }
        }

        // Check void element type
        if (in_array($tag, static::config()->get('void_elements'))) {
            if ($content) {
                throw new InvalidArgumentException("Void element \"{$tag}\" cannot have content");
            }
            return "<{$tag}{$preparedAttributes} />";
        }

        // Closed tag type
        return "<{$tag}{$preparedAttributes}>{$content}</{$tag}>";
    }
}
