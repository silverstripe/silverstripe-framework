<?php

namespace SilverStripe\Forms\HTMLEditor;

use DOMAttr;
use DOMElement;
use InvalidArgumentException;
use SilverStripe\Core\ArrayLib;

/**
 * Rules used to define whether a given element or regex pattern of elements is allowed in the content of an HTMLEditorField.
 */
class HTMLEditorElementRule
{
    /**
     * Name of the global rule for a given rule set
     */
    public const string GLOBAL_NAME = '_global';

    /**
     * Name of the element(s) this rule applies to.
     * This can also be a regex pattern - in which case nameIsPattern should be set to `true`.
     */
    private string $name;

    /**
     * Indicates whether name is a regex pattern to match against.
     */
    private bool $nameIsPattern = false;

    /**
     * Associative array of attribute rules which apply to this element rule.
     * Excludes rules which use regex - see $attributePatternRules
     * @var HTMLEditorAttributeRule[]
     */
    private array $attributeRules = [];

    /**
     * Associative array of attribute rules with regex expressions which apply to this element rule.
     * See also $attributeRules
     * @var HTMLEditorAttributeRule[]
     */
    private array $attributePatternRules = [];

    /**
     * Array of attribute rules defining attributes that must be present.
     *
     * If none of the attributes are present, the element(s) this rule applies to is not allowed.
     * Note that only one of the attributes in this list has to be present for the element to be allowed.
     * @var HTMLEditorAttributeRule[]
     */
    private array $requiredAttributeRules = [];

    /**
     * If true, empty elements will have a non-breaking space added inside them.
     */
    private bool $padEmpty = false;

    /**
     * If true, empty elements will be removed.
     */
    private bool $removeIfEmpty = false;

    /**
     * If true, elements with no attributes will be removed.
     */
    private bool $removeIfNoAttributes = false;

    private ?HTMLEditorElementRule $globalRule = null;

    /**
     * Returns a HTMLEditorAttributeRule based on an associative array defining the rule.
     * If the rule array is empty, the defaults apply.
     * Excludes attributes - get those separately using HTMLEditorAttributeRule::fromArray().
     */
    public static function fromArray(string $name, array $ruleArray): static
    {
        if (!empty($ruleArray) && !ArrayLib::is_associative($ruleArray)) {
            throw new InvalidArgumentException('Element rule array must be associative.');
        }
        $nameIsPattern = HTMLEditorRuleSet::nameIsPattern($name);
        if ($nameIsPattern) {
            $name = HTMLEditorRuleSet::patternToRegex($name);
        }
        $elementRule = new HTMLEditorElementRule(
            $name,
            $nameIsPattern,
            $ruleArray['padEmpty'] ?? false,
            $ruleArray['removeIfEmpty'] ?? false,
            $ruleArray['removeIfNoAttributes'] ?? false,
        );
        return $elementRule;
    }

    public function __construct(string $name, bool $nameIsPattern = false, bool $padEmpty = false, bool $removeIfEmpty = false, bool $removeIfNoAttributes = false)
    {
        $this->name = $name;
        $this->nameIsPattern = $nameIsPattern;
        $this->padEmpty = $padEmpty;
        $this->removeIfEmpty = $removeIfEmpty;
        $this->removeIfNoAttributes = $removeIfNoAttributes;
    }

    /**
     * Get the name of the element(s) this rule applies to.
     * This can also be a regex pattern - in which case getNameIsPattern() should return `true`.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns true if the value returned from getName() is a regex pattern.
     */
    public function getNameIsPattern(): bool
    {
        return $this->nameIsPattern;
    }

    /**
     * Get all attribute rules
     *
     * @return HTMLEditorAttributeRule[]
     */
    public function getAttributeRules(): array
    {
        return array_merge($this->attributeRules, $this->attributePatternRules);
    }

    /**
     * Get whether empty elements will have a non-breaking space added inside them.
     */
    public function getPadEmpty(): bool
    {
        return $this->padEmpty;
    }

    /**
     * Get whether empty elements will be removed.
     */
    public function getRemoveIfEmpty(): bool
    {
        return $this->removeIfEmpty;
    }

    /**
     * Get whether elements with no attributes will be removed.
     */
    public function getRemoveIfNoAttributes(): bool
    {
        return $this->removeIfNoAttributes;
    }

    public function setGlobalRule(HTMLEditorElementRule $rule): static
    {
        $this->globalRule = $rule;
        return $this;
    }

    /**
     * Add an attribute rule which must apply to any elements covered by this element rule.
     */
    public function addAttributeRule(HTMLEditorAttributeRule $rule): static
    {
        if ($rule->getIsRequired()) {
            $this->requiredAttributeRules[$rule->getName()] = $rule;
        } else {
            // Unset in case this is overriding a previously defined rule.
            unset($this->requiredAttributeRules[$rule->getName()]);
        }
        // Add as a regular attribute or a pattern attribute.
        if ($rule->getNameIsPattern()) {
            $this->attributePatternRules[$rule->getName()] = $rule;
        } else {
            $this->attributeRules[$rule->getName()] = $rule;
        }
        return $this;
    }

    /**
     * Remove an attribute rule so it no longer applies to any elements covered by this element rule.
     */
    public function removeAttributeRule(string $ruleName): static
    {
        unset($elementRule->attributePatternRules[$ruleName]);
        unset($elementRule->attributeRules[$ruleName]);
        return $this;
    }

    /**
     * Check whether the given DOM element is allowed according to this rule.
     *
     * Note that this method assumes this rule applies to the element - it does
     * not check the tag name of the element as part of its conditional logic.
     */
    public function isElementAllowed(DOMElement $element): bool
    {
        // If the rule has attributes required, check them to see if this element has at least one
        // Note that if this rule gives default or forced values to some attribute, the element is assumed to have
        // those attributes.
        if (!empty($this->requiredAttributeRules)) {
            $hasMatch = false;

            foreach ($this->requiredAttributeRules as $rule) {
                if ($rule->hasForcedOrDefaultValue() || $element->getAttribute($rule->getName())) {
                    $hasMatch = true;
                    break;
                }
            }

            if (!$hasMatch) {
                return false;
            }
        }

        // If the rule says to remove elements with no attributes, and there are none, remove it.
        // Note that if this rule gives default or forced values to some attribute, the element is assumed to have
        // those attributes.
        if ($this->removeIfNoAttributes && !$element->hasAttributes() && !$this->hasDefaultAttributeValues()) {
            return false;
        }

        // If the rule says to remove empty elements, and this element is empty, remove it
        if ($this->removeIfEmpty && !$element->hasChildNodes()) {
            return false;
        }

        // No further tests required, element passes
        return true;
    }

    /**
     * Check whether the give attribute is allowed for elements covered by this element rule.
     */
    public function isAttributeAllowed(DOMAttr $attribute): bool
    {
        return (bool) $this->getRuleForAttribute($attribute->name)?->isAttributeAllowed($attribute);
    }

    /**
     * Given an attribute name, get the attribute rule which applies if there is one.
     */
    private function getRuleForAttribute(string $name): ?HTMLEditorAttributeRule
    {
        if (isset($this->attributeRules[$name])) {
            return $this->attributeRules[$name];
        }
        foreach ($this->attributePatternRules as $attributeRule) {
            if (preg_match($attributeRule->getName(), $name)) {
                return $attributeRule;
            }
        }
        return $this->globalRule?->getRuleForAttribute($name);
    }

    /**
     * Check if this element rule has any attribute rule which has a default or forced value.
     */
    private function hasDefaultAttributeValues(): bool
    {
        foreach ($this->getAttributeRules() as $attributeRule) {
            if ($attributeRule->hasForcedOrDefaultValue()) {
                return true;
            }
        }
        return false;
    }
}
