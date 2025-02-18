<?php

namespace SilverStripe\Forms\HTMLEditor;

use DOMAttr;
use DOMElement;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\XssSanitiser;
use SilverStripe\View\Parsers\HTMLValue;
use stdClass;

/**
 * Sanitises an HTMLValue so it's contents are the elements and attributes that are allowed
 * using the given HTMLEditorConfig
 */
class HTMLEditorSanitiser
{
    use Configurable;
    use Injectable;

    /**
     * "rel" attribute value to add to link elements which have a target attribute (usually "_blank").
     *
     * This is to done to prevent reverse tabnabbing - see https://www.owasp.org/index.php/Reverse_Tabnabbing.
     * noopener includes the behaviour we want, though some browsers don't yet support it and rely
     * upon using noreferrer instead - see https://caniuse.com/rel-noopener for current browser compatibility.
     * Set this to null if you would like to disable this behaviour.
     * Set this to an empty string if you would like to remove rel attributes that were previously set.
     */
    private static string $link_rel_value = 'noopener noreferrer';

    /**
     * Rules determining which elements and attributes are allowed and which should be removed.
     */
    private HTMLEditorRuleSet $ruleSet;

    /**
     * Construct a sanitiser from a given HTMLEditorConfig
     *
     * Note that we build data structures from the current state of HTMLEditorConfig - later changes to
     * the config won't change the ruleset used by this sanitiser instance.
     *
     * @param HTMLEditorConfig $config
     */
    public function __construct(HTMLEditorConfig $config)
    {
        $this->ruleSet = $config->getElementRuleSet();
    }

    /**
     * Given an SS_HTMLValue instance, will remove and elements and attributes that are
     * not explicitly allowed in the HTMLEditorConfig
     *
     * @param HTMLValue $html - The HTMLValue to remove any non-allowed elements & attributes from
     */
    public function sanitise(HTMLValue $html)
    {
        $linkRelValue = $this->config()->get('link_rel_value');
        $doc = $html->getDocument();
        // Get a sanitiser but don't deny any specific attributes or elements, since that's
        // handled as part of the element rules.
        $xssSanitiser = XssSanitiser::create();
        $xssSanitiser->setElementsToRemove([])->setAttributesToRemove([]);

        /** @var DOMElement $el */
        foreach ($html->query('//body//*') as $el) {
            // If this element isn't allowed, strip it
            if (!$this->ruleSet->isElementAllowed($el)) {
                // If it's a script or style, we don't keep contents
                if ($el->tagName === 'script' || $el->tagName === 'style') {
                    $el->parentNode->removeChild($el);
                } else {
                    // Otherwise we replace this node with all it's children
                    // First, create a new fragment with all of $el's children moved into it
                    $frag = $doc->createDocumentFragment();
                    while ($el->firstChild) {
                        $frag->appendChild($el->firstChild);
                    }

                    // Then replace $el with the frags contents (which used to be it's children)
                    $el->parentNode->replaceChild($frag, $el);
                }
            } else {
                $elementRule = $this->ruleSet->getRuleForElement($el->tagName);
                // Otherwise tidy the element
                // First, if we're supposed to pad & this element is empty, fix that
                if ($elementRule->getPadEmpty() && !$el->firstChild) {
                    $el->nodeValue = '&nbsp;';
                }

                // Set default and forced values for attributes.
                foreach ($elementRule->getAttributeRules() as $attributeRule) {
                    // Pattern rules can't have forced or default values so we don't need to check them.
                    if ($attributeRule->getNameIsPattern()) {
                        continue;
                    }
                    $attrName = $attributeRule->getName();
                    // Set default values
                    $defaultValue = $attributeRule->getDefaultValue();
                    if ($defaultValue !== null && !$el->getAttribute($attrName)) {
                        $el->setAttribute($attrName, $defaultValue);
                    }
                    // Set forced values
                    $forcedValue = $attributeRule->getForcedValue();
                    if ($forcedValue !== null) {
                        $el->setAttribute($attrName, $forcedValue);
                    }
                }

                // Filter out any non-allowed attributes
                $children = $el->attributes;
                $i = $children->length;
                while ($i--) {
                    /** @var DOMAttr $attr */
                    $attr = $children->item($i);

                    // If this attribute isn't allowed, strip it
                    if ($attr && !$elementRule->isAttributeAllowed($attr)) {
                        $el->removeAttributeNode($attr);
                    }
                }

                // Substitute element at appropriate
                $elementRuleName = $elementRule->getName();
                if (!$elementRule->getNameIsPattern() && $elementRuleName !== $el->tagName) {
                    $replacementElement = $doc->createElement($elementRuleName);
                    foreach ($el->attributes as $attr) {
                        $replacementElement->setAttributeNode($attr);
                    }
                    foreach ($el->childNodes as $child) {
                        $replacementElement->appendChild($child);
                    }
                    $el->replaceWith($replacementElement);
                    $el = $replacementElement;
                }

                // Explicit XSS sanitisation for anything that there's really no sensible use case for in a WYSIWYG
                $xssSanitiser->sanitiseElement($el);
            }

            if ($el->tagName === 'a' && $linkRelValue !== null) {
                $this->addRelValue($el, $linkRelValue);
            }
        }
    }

    /**
     * Adds rel="noopener noreferrer" to link elements with a target attribute
     *
     * @param DOMElement $el
     * @param string|null $linkRelValue
     */
    private function addRelValue(DOMElement $el, $linkRelValue)
    {
        // user has checked the checkbox 'open link in new window'
        if ($el->getAttribute('target') && $el->getAttribute('rel') !== $linkRelValue) {
            if ($linkRelValue !== '') {
                $el->setAttribute('rel', $linkRelValue);
            } else {
                $el->removeAttribute('rel');
            }
        } elseif ($el->getAttribute('rel') === $linkRelValue && !$el->getAttribute('target')) {
            // user previously checked 'open link in new window' and noopener was added,
            // now user has unchecked the checkbox so we can remove noopener
            $el->removeAttribute('rel');
        }
    }
}
