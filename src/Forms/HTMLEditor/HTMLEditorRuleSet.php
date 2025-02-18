<?php

namespace SilverStripe\Forms\HTMLEditor;

use DOMElement;
use InvalidArgumentException;

/**
 * A set of rules to determine which elements and attributes are allowed for a given HTMLEditorConfig.
 * This ruleset will be used by HTMLEditorSanitiser for server-side sanitisation of HTML.
 */
class HTMLEditorRuleSet
{
    private HTMLEditorElementRule $globalRule;

    /**
     * Associative array of element rules which are included in this ruleset.
     * Excludes rules which use regex - see $elementPatternRules
     * @var HTMLEditorElementRule[]
     */
    private array $elementRules = [];

    /**
     * Associative array of element rules with regex expressions which are included in this ruleset.
     * See also $elementRules
     * @var HTMLEditorElementRule[]
     */
    private array $elementPatternRules = [];

    private array $elementSubstitutionRules = [];

    /**
     * Check if the name given for an element or attribute rule is a pattern.
     * If so, it should be passed through patternToRegex() before being passed into the rule's constructor.
     */
    public static function nameIsPattern(string $name): bool
    {
        $hasPatternsRegExp = '/[*?+]/';
        return preg_match($hasPatternsRegExp, $name);
    }

    /**
     * Given a raw pattern, create a regex that does the match.
     *
     * Raw patterns can use the following special characters:
     * - `*` Matches between zero and unlimited characters (equivalent to `.*` in regex).
     * - `?` Matches between zero and one characters (equivalent to `.?` in regex).
     * - `+` Matches exactly one character (equivalent to `.+` in regex).
     */
    public static function patternToRegex(string $pattern): string
    {
        return '/^' . preg_replace('/([?+*])/', '.$1', $pattern) . '$/';
    }

    /**
     * Take a regex and convert it to a raw pattern compatible with
     * arrays passed into HTMLEditorConfig::setElementRulesFromArray().
     */
    public static function regexToPattern(string $regex): string
    {
        return str_replace('.', '', trim($regex, '/^$'));
    }

    public function __construct()
    {
        $this->globalRule = new HTMLEditorElementRule(HTMLEditorElementRule::GLOBAL_NAME);
    }

    /**
     * Get the global rule that applies to all elements in this rule set.
     * The global rule will only contain attribute rules - e.g. getPadEmpty() will always return false.
     */
    public function getGlobalRule(): HTMLEditorElementRule
    {
        return $this->globalRule;
    }

    /**
     * Get all element rules
     *
     * @return HTMLEditorElementRule[]
     */
    public function getElementRules(): array
    {
        return array_merge($this->elementRules, $this->elementPatternRules);
    }

    /**
     * Get all element substitution rules.
     *
     * @return array<string, string>
     */
    public function getElementSubstitutionRules(): array
    {
        return $this->elementSubstitutionRules;
    }

    /**
     * Add an element rule to this ruleset.
     */
    public function addElementRule(HTMLEditorElementRule $rule): static
    {
        $rule->setGlobalRule($this->globalRule);
        $ruleName = $rule->getName();
        // Intentionally overrides existing rule(s)
        if ($rule->getNameIsPattern()) {
            $this->elementPatternRules[$ruleName] = $rule;
        } else {
            $this->elementRules[$ruleName] = $rule;
        }
        if (isset($this->elementSubstitutionRules[$ruleName])) {
            unset($this->elementSubstitutionRules[$ruleName]);
        }
        return $this;
    }

    /**
     * Add a rule for substituting one element with another.
     */
    public function addElementSubstitutionRule(string $from, string $to): static
    {
        if (static::nameIsPattern($from) || static::nameIsPattern($to)) {
            throw new InvalidArgumentException('Cannot add element substitutions using patterns.');
        }
        $this->elementSubstitutionRules[$from] = $to;
        if (isset($this->elementRules[$from])) {
            unset($this->elementRules[$from]);
        }
        return $this;
    }

    /**
     * Remove an element rule from this ruleset.
     */
    public function removeElementRule(string $ruleName): static
    {
        unset($this->elementPatternRules[$ruleName]);
        unset($this->elementRules[$ruleName]);
        unset($this->elementSubstitutionRules[$ruleName]);
        return $this;
    }

    /**
     * Given an element name, get the element rule which applies if there is one.
     */
    public function getRuleForElement(string $tag): ?HTMLEditorElementRule
    {
        if (isset($this->elementRules[$tag])) {
            return $this->elementRules[$tag];
        }
        if (isset($this->elementSubstitutionRules[$tag])) {
            return $this->getRuleForElement($this->elementSubstitutionRules[$tag]);
        }
        foreach ($this->elementPatternRules as $rule) {
            if (preg_match($rule->getName(), $tag)) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Check whether the given DOM element is allowed according to this rule.
     */
    public function isElementAllowed(DOMElement $element): bool
    {
        return (bool) $this->getRuleForElement($element->tagName)?->isElementAllowed($element);
    }
}
