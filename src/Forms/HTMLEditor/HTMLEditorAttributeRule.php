<?php

namespace SilverStripe\Forms\HTMLEditor;

use DOMAttr;
use InvalidArgumentException;
use SilverStripe\Core\ArrayLib;

/**
 * Rules used to define whether a given attribute or regex pattern of attributes is allowed in the content of an HTMLEditorField.
 * Each attribute rule is applied to a single HTMLEditorElementRule matching an element or a regex patter of elements.
 */
class HTMLEditorAttributeRule
{
    public const string VALUE_DEFAULT = 'default';

    public const string VALUE_FORCED = 'forced';

    public const string VALUE_VALID = 'valid';

    /**
     * Name of the attribute(s) this rule applies to.
     * This can also be a regex pattern - in which case nameIsPattern should be set to `true`.
     */
    private string $name;

    /**
     * Indicates whether name is a regex pattern to match against.
     */
    private bool $nameIsPattern = false;

    /**
     * A default value which will be used if none is set.
     * Can only be set in the constructor.
     */
    private ?string $defaultValue = null;

    /**
     * The value the attribute(s) this rule applies to will be forced to, overriding any user-set value.
     * Can only be set in the constructor.
     */
    private ?string $forcedValue = null;

    /**
     * An allow list of values for the attribute(s) this rule applies to.
     * If empty, any value is allowed.
     * Can only be set in the constructor.
     */
    private array $validValues = [];

    /**
     * If true, the attribute(s) this rule applies to must be present for the elements this rule applies to.
     * Elements which don't have their required attributes are removed.
     */
    private bool $isRequired = false;

    /**
     * Returns a HTMLEditorAttributeRule based on an associative array defining the rule.
     * If the rule array is empty, the defaults apply.
     */
    public static function fromArray(string $name, array $ruleArray): static
    {
        if (!empty($ruleArray) && !ArrayLib::is_associative($ruleArray)) {
            throw new InvalidArgumentException('Attribute rule array must be associative.');
        }
        $nameIsPattern = HTMLEditorRuleSet::nameIsPattern($name);
        if ($nameIsPattern) {
            $name = HTMLEditorRuleSet::patternToRegex($name);
        }
        $attributeRule = new HTMLEditorAttributeRule(
            $name,
            $nameIsPattern,
            $ruleArray['value'] ?? null,
            $ruleArray['valueType'] ?? HTMLEditorAttributeRule::VALUE_VALID
        );
        if ($ruleArray['isRequired'] ?? false) {
            $attributeRule->setIsRequired(true);
        }
        return $attributeRule;
    }

    public function __construct(string $name, bool $nameIsPattern = false, null|array|string $value = null, string $valueType = HTMLEditorAttributeRule::VALUE_VALID)
    {
        if ($nameIsPattern && $valueType !== HTMLEditorAttributeRule::VALUE_VALID && !empty($value)) {
            throw new InvalidArgumentException(
                'Cannot set forced or default values for attributes with regex pattern.'
                . ' Define this rule with explicit attribute names.'
            );
        }
        if (is_array($value) && $valueType !== HTMLEditorAttributeRule::VALUE_VALID) {
            throw new InvalidArgumentException(
                '$value can only be an array when setting allowed values. Forced or default values must be a single string value.'
            );
        }
        if ($value !== null) {
            switch ($valueType) {
                case HTMLEditorAttributeRule::VALUE_DEFAULT:
                    $this->defaultValue = $value;
                    $this->validValues = [];
                    break;
                case HTMLEditorAttributeRule::VALUE_FORCED:
                    $this->forcedValue = $value;
                    $this->validValues = (array) $value;
                    break;
                case HTMLEditorAttributeRule::VALUE_VALID:
                    $this->validValues = (array) $value;
                    break;
                default:
                    throw new InvalidArgumentException('$valueType must be one of the HTMLEditorAttributeRule::VALUE_* consts.');
            }
        }
        $this->name = $name;
        $this->nameIsPattern = $nameIsPattern;
    }

    /**
     * Get the name of the attribute(s) this rule applies to.
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
     * Get the default value the attribute(s) this rule applies to will be set to, if any.
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * Get the value the attribute(s) this rule applies to will be forced to, if any.
     * A forced value will override any user-provided value.
     */
    public function getForcedValue(): ?string
    {
        return $this->forcedValue;
    }

    /**
     * Get whether this attribute rule has a forced or default value.
     */
    public function hasForcedOrDefaultValue(): bool
    {
        return $this->forcedValue !== null || $this->defaultValue !== null;
    }

    /**
     * Get the set of values the attribute(s) this rule applies to is allowed to be set to.
     * If the array is empty, any value is valid.
     */
    public function getValidValues(): array
    {
        return $this->validValues;
    }

    /**
     * Set whether the attribute(s) this rule applies to must be present for the element to be allowed.
     */
    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    /**
     * Get whether the attribute(s) this rule applies to must be present for the element to be allowed.
     */
    public function getIsRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Check wither the given attribute is allowed or not according to this rule.
     *
     * Note that this method assumes this rule applies to the attribute - it does
     * not check the name of the attribute as part of its conditional logic.
     */
    public function isAttributeAllowed(DOMAttr $attribute): bool
    {
        // If the rule has a set of valid values, check them to see if this attribute has one
        if (!empty($this->validValues) && !in_array($attribute->value, $this->validValues ?? [])) {
            return false;
        }

        // No further tests required, attribute passes
        return true;
    }
}
