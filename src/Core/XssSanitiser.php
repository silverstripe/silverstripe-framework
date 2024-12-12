<?php

namespace SilverStripe\Core;

use DOMAttr;
use DOMElement;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\Parsers\HTMLValue;

/**
 * Sanitises HTML to prevent XSS attacks.
 */
class XssSanitiser
{
    use Injectable;

    /**
     * Attributes which will be removed from any element.
     * If an asterisk is at the start of the attribute name, all attributes ending with this name will be removed.
     * If an asterisk is at the end of the attribute name, all attributes starting with this name will be removed.
     * For example `on*` will remove `onerror`, `onmouseover`, etc
     */
    private array $attributesToRemove = [
        'on*',
        'accesskey',
    ];

    private array $elementsToRemove = [
        'embed',
        'object',
        'script',
        'style',
        'svg',
    ];

    private bool $keepInnerHtmlOnRemoveElement = true;

    private bool $removeDataSvg = true;

    private bool $removeSvgFile = true;

    /**
     * Remove XSS attack vectors from an HTML fragment string
     */
    public function sanitiseString(string $html): string
    {
        $htmlValue = HTMLValue::create($html);
        $this->sanitiseHtmlValue($htmlValue);
        return $htmlValue->getContent();
    }

    /**
     * Remove XSS attack vectors from HTMLValue content
     */
    public function sanitiseHtmlValue(HTMLValue $html): void
    {
        foreach ($html->query('//*') as $element) {
            if (!is_a($element, DOMElement::class)) {
                continue;
            }
            $this->sanitiseElement($element);
        }
    }

    /**
     * Remove XSS attack vectors from a DOMElement
     */
    public function sanitiseElement(DOMElement $element): void
    {
        // Remove elements first - if we remove the element, we don't have any attributes to check so exit early
        $removed = $this->stripElement($element);
        if ($removed) {
            return;
        }
        $this->stripAttributes($element);
        $this->stripAttributeContents($element);
    }

    /**
     * Get the names of elements which will be removed.
     */
    public function getElementsToRemove(): array
    {
        return $this->elementsToRemove;
    }

    /**
     * Set the names of elements which will be removed.
     * Note that allowing the elements which are included in the default list could result in XSS vulnerabilities.
     */
    public function setElementsToRemove(array $elements): static
    {
        $this->elementsToRemove = $elements;
        return $this;
    }

    /**
     * Get the names of attributes which will be removed from any elements that have them.
     */
    public function getAttributesToRemove(): array
    {
        return $this->attributesToRemove;
    }

    /**
     * Set the names of attributes which will be removed from any elements that have them.
     * Note that allowing the attributes which are included in the default list could result in XSS vulnerabilities.
     */
    public function setAttributesToRemove(array $attributes): static
    {
        $this->attributesToRemove = $attributes;
        return $this;
    }

    /**
     * Get whether the inner contents of an element will be kept for elements that get removed.
     */
    public function getKeepInnerHtmlOnRemoveElement(): bool
    {
        return $this->keepInnerHtmlOnRemoveElement;
    }

    /**
     * Set whether to keep the inner contents of an element if it gets removed.
     */
    public function setKeepInnerHtmlOnRemoveElement(bool $keep): static
    {
        $this->keepInnerHtmlOnRemoveElement = $keep;
        return $this;
    }

    /**
     * If $element is one of the elements in $elementsToRemove, replace it
     * with a text node.
     */
    private function stripElement(DOMElement $element): bool
    {
        if (!in_array($element->tagName, $this->getElementsToRemove())) {
            return false;
        }
        // Make sure we don't remove any child nodes
        $parentNode = $element->parentNode;
        if ($this->getKeepInnerHtmlOnRemoveElement() && $parentNode && $element->hasChildNodes()) {
            // We can't just iterate through $node->childNodes because that seems to skip some children
            while ($element->hasChildNodes()) {
                $parentNode->insertBefore($element->firstChild, $element);
            }
        }
        $element->remove();
        return true;
    }

    /**
     * Remove all attributes in $attributesToRemove from the element.
     */
    private function stripAttributes(DOMElement $element): void
    {
        $attributesToRemove = $this->getAttributesToRemove();
        if (empty($attributesToRemove)) {
            return;
        }
        $attributes = $element->attributes;
        for ($i = count($attributes) - 1; $i >= 0; $i--) {
            /** @var DOMAttr $attr */
            $attr = $attributes->item($i);
            foreach ($attributesToRemove as $toRemove) {
                if (str_starts_with($toRemove, '*') && str_ends_with($attr->name, str_replace('*', '', $toRemove))) {
                    $element->removeAttributeNode($attr);
                } elseif (str_ends_with($toRemove, '*') && str_starts_with($attr->name, str_replace('*', '', $toRemove))) {
                    $element->removeAttributeNode($attr);
                } elseif (!str_contains($toRemove, '*') && $attr->name === $toRemove) {
                    $element->removeAttributeNode($attr);
                }
            }
        }
    }

    /**
     * Strip out attributes which have dangerous content which might otherwise execute javascript.
     * This is content that we will always remove regardless of whether the attributes and elements in question
     * are otherwise allowed, e.g. via WYSIWYG configuration.
     */
    private function stripAttributeContents(DOMElement $element): void
    {
        $regex = $this->getStripAttributeContentsRegex();
        foreach (['lowsrc', 'src', 'href', 'data'] as $dangerAttribute) {
            if ($element->hasAttribute($dangerAttribute)) {
                $attrContent = $element->getAttribute($dangerAttribute);
                if (preg_match($regex, $attrContent)) {
                    $element->removeAttribute($dangerAttribute);
                }
            }
        }
    }

    private function getStripAttributeContentsRegex(): string
    {
        $regexes = [
            $this->splitWithWhitespaceRegex('javascript:'),
            $this->splitWithWhitespaceRegex('data:text/html'),
            $this->splitWithWhitespaceRegex('vbscript:'),
        ];
        // Regex is "starts with any of these, with optional whitespace at the start, case insensitive"
        return '#^\s*(' . implode('|', $regexes) . ')#iu';
    }

    private function splitWithWhitespaceRegex(string $string): string
    {
        // Note that `\s` explicitly includes ALL invisible characters when used with the `u` modifier.
        // That includes unicode characters like the non-breaking space.
        return implode('\s*', str_split($string));
    }
}
