<?php

namespace SilverStripe\Forms\HTMLEditor;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\ArrayLib;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * A generic API for WYSIWYG editor configuration, to allow various parameters to be configured on a site or section basis
 *
 * There can be multiple HTMLEditorConfig's, which should always be created / accessed using HTMLEditorConfig::get.
 * You can then set the currently active config using set_active.
 * The order of precedence for which config is used is (lowest to highest):
 *
 * - default_config config setting
 * - Active config assigned
 * - Config name assigned to HTMLEditorField
 * - Config instance assigned to HTMLEditorField
 *
 * Typically global config changes should set the active config.
 *
 * The default config class can be changed via dependency injection to replace HTMLEditorConfig.
 *
 * @author "Hamish Friedlander" <hamish@silverstripe.com>
 */
abstract class HTMLEditorConfig
{
    use Configurable;
    use Injectable;

    /**
     * Array of registered configurations
     *
     * @var HTMLEditorConfig[]
     */
    protected static array $configs = [];

    /**
     * Identifier key of current config. This will match an array key in $configs.
     * If left blank, will fall back to value of default_config set via config.
     */
    protected static string $current = '';

    /**
     * Name of default config. This will be ignored if $current is assigned a value.
     */
    private static string $default_config = 'default';

    /**
     * Associative array of predefined HTMLEditorConfig definitions that can be accessed with the get() method.
     *
     * The key is the identifier for the config which will be passed into get().
     * - 'configClass' is the FCQN of the HTMLEditorConfig subclass that is instantiated.
     * - 'elementRules' can be used to define what elements and attributes are allowed. If this isn't set,
     * default_element_rules will be used. @see default_element_rules for syntax.
     * - 'extraElementRules' can be used to define additional element and attribute rules.
     * - 'options' can be used to define values to be passed into setOptions() on instantiation.
     */
    private static array $default_config_definitions = [
        'cms' => [
            'configClass' => TextAreaConfig::class,
            'extraElementRules' => [
                'iframe' => [
                    'attributes' => [
                        'align' => true,
                        'frameborder' => true,
                        'height' => true,
                        'marginheight' => true,
                        'marginwidth' => true,
                        'name' => true,
                        'scrolling' => true,
                        'src' => true,
                        'width' => true,
                    ],
                ],
                'object' => [
                    'attributes' => [
                        'data' => true,
                        'height' => true,
                        'type' => true,
                        'width' => true,
                    ],
                ],
                'param' => [
                    'attributes' => [
                        'name' => true,
                        'value' => true,
                    ],
                ],
                'map' => [
                    'attributes' => [
                        'class' => true,
                        'id' => true,
                        'name' => true,
                    ],
                ],
                'area' => [
                    'attributes' => [
                        'alt' => true,
                        'coords' => true,
                        'href' => true,
                        'shape' => true,
                        'target' => true,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Default set of rules to define which elements and attributes are allowed, and how to treat them.
     *
     * Every element that is allowed in the HTML content must be expicitly allowed, either on its own
     * or as part of a pattern.
     * Element names or patterns are keys in the associative array. Patterns can use the following special characters:
     * - `*` Matches between zero and unlimited characters (equivalent to `.*` in regex).
     * - `?` Matches between zero and one characters (equivalent to `.?` in regex).
     * - `+` Matches exactly one character (equivalent to `.+` in regex).
     *
     * The special name "_global" can be used to define which attributes are allowed for ALL elements.
     *
     * The value in the array can be:
     * - `true` which means the element (or elements matching the pattern) are allowed,
     * - `false` or `null` which means the element is explicitly NOT allowed (useful to override previously set
     * configuration),
     * - an array of rules which means the element is allowed but specific rules apply to it.
     *
     * The array of element rules is associative. The following can be included in the array:
     * - `"padEmpty"`: Set to `true` to add a non-breaking space to elements which have no child nodes.
     * - `"removeIfEmpty"`: Set to `true` to remove elements which have no child nodes.
     * - `"removeIfNoAttributes"`: Set to `true` to remove elements which have no attributes.
     * - `"convertTo"`: Set to the string name of a specific attrbiute this element should be converted to. For example
     * convert `<b>` to `<strong>`.
     * - `"attributes"`: An associative array of attributes allowed on this element and rules for them.
     *
     * Every attribute that is allowed for an element must be explicitly allowed, either on its own
     * or as part of a pattern. Attributes listed against the global element rule apply for all elements.
     * Patterns work the same way for attribute rules as they do for element rules.
     *
     * Like with elements, an attribute can have `true` or an associative array to mark it as allowed,
     * or it can be ommitted, or have a `false` or `null` value to disallow it.
     *
     * The array of attribute rules is associative. The following can be included in the array:
     * - `"isRequired"`: Set to `true` to make this attribute mandatory. If the attribute is missing, the element will
     * be removed.
     * - `"value"`: If "valueType" is set to "valid", set this to an array of allowed values. Otherwise set it to a
     * string indicating the forced or default value.
     * - `"valueType"`: If "value" is used, set this to either `"default"` to define the default value, `"forced"` to
     * force a specific value (overriding user-set values), or `"valid"` to define a set of allowed values for this
     * attribute. If an invalid value is used, the attribute will be removed.
     *
     * See _config/html.yml for the default definition.
     */
    private static $default_element_rules = [];

    /**
     * List of themes defined for the frontend
     */
    private static array $user_themes = [];

    /**
     * The height for the editable portion of editor in number of rows.
     * Note that some WYSIWYG implementations may ignore this.
     */
    private static int $fixed_row_height = 20;

    /**
     * List of the current themes set for this config
     */
    protected static array $current_themes = [];

    /**
     * A string used to identify this config class in the CMS JavaScript.
     * Must be overridden in subclasses to a unique name.
     */
    protected static string $configType = '';

    /**
     * The name of the client-side component to inject for fields using this config.
     * Must be overridden in subclasses to a valid component name.
     */
    protected static string $schemaComponent = '';

    private ?int $rows = null;

    /**
     * Get the HTMLEditorConfig object for the given identifier. This is a correct way to get an HTMLEditorConfig
     * instance - do not call 'new'
     *
     * The config instance will be created if one does not yet exist for that identifier.
     *
     * @param string $identifier The identifier for the config set. If omitted, the active config is returned.
     */
    public static function get($identifier = null): HTMLEditorConfig
    {
        if (!$identifier) {
            return static::get_active();
        }
        // Create new instance if unconfigured
        if (!isset(HTMLEditorConfig::$configs[$identifier])) {
            $predefined = HTMLEditorConfig::config()->get('default_config_definitions');
            if (isset($predefined[$identifier])) {
                // Use predefined configuration if available
                $configDefinition = $predefined[$identifier];
                $configClass = $configDefinition['configClass'] ?? HTMLEditorConfig::class;
                HTMLEditorConfig::$configs[$identifier] = $configClass::create();
                // Set the element rules, either from the given definition or from the configured default for that class.
                if (isset($configDefinition['elementRules'])) {
                    $elementRulesArray = $configDefinition['elementRules'];
                } else {
                    $elementRulesArray = $configClass::config()->get('default_element_rules');
                }
                if (isset($configDefinition['extraElementRules'])) {
                    // Allow setting additional element rules, so the full set doesn't have to be redefined.
                    $elementRulesArray = array_merge($elementRulesArray, $configDefinition['extraElementRules']);
                }
                HTMLEditorConfig::$configs[$identifier]->setElementRulesFromArray($elementRulesArray);
                if (isset($configDefinition['options'])) {
                    // Set any predefined options. Note this must be done AFTER setting element rules from array
                    // because options might override or add to those rules in some scenarios.
                    HTMLEditorConfig::$configs[$identifier]->setOptions($configDefinition['options']);
                }
            } else {
                // Fall back on just creating whatever injector wants us to create
                HTMLEditorConfig::$configs[$identifier] = static::create();
                HTMLEditorConfig::$configs[$identifier]->setElementRulesFromArray(static::config()->get('default_element_rules'));
            }
            HTMLEditorConfig::$configs[$identifier]->setOption('editorIdentifier', $identifier);
        }
        return HTMLEditorConfig::$configs[$identifier];
    }

    /**
     * Assign a new config, or clear existing, for the given identifier
     *
     * @param string $identifier A specific identifier
     * @param ?HTMLEditorConfig $config Config to set, or null to clear
     * @return ?HTMLEditorConfig The assigned config or null if cleared
     */
    public static function set_config(string $identifier, ?HTMLEditorConfig $config = null): ?HTMLEditorConfig
    {
        if ($config) {
            HTMLEditorConfig::$configs[$identifier] = $config;
            HTMLEditorConfig::$configs[$identifier]->setOption('editorIdentifier', $identifier);
        } else {
            unset(HTMLEditorConfig::$configs[$identifier]);
        }
        return $config;
    }

    /**
     * Gets the current themes, if it is not set this will fallback to config
     */
    public static function getThemes(): array
    {
        if (!empty(static::$current_themes)) {
            return static::$current_themes;
        }
        return Config::inst()->get(static::class, 'user_themes');
    }

    /**
     * Sets the current theme
     */
    public static function setThemes(array $themes): void
    {
        static::$current_themes = $themes;
    }

    /**
     * Set the currently active configuration object. Note that the existing active
     * config will not be renamed to the new identifier.
     *
     * @param string $identifier The identifier for the config set
     */
    public static function set_active_identifier(string $identifier): void
    {
        HTMLEditorConfig::$current = $identifier;
    }

    /**
     * Get the currently active configuration identifier. Will fall back to default_config
     * if unassigned.
     */
    public static function get_active_identifier(): string
    {
        $identifier = HTMLEditorConfig::$current ?: static::config()->get('default_config');
        return $identifier;
    }

    /**
     * Get the currently active configuration object
     */
    public static function get_active(): HTMLEditorConfig
    {
        $identifier = HTMLEditorConfig::get_active_identifier();
        return HTMLEditorConfig::get($identifier);
    }

    /**
     * Assigns the currently active config an explicit instance
     *
     * @return HTMLEditorConfig The given config
     */
    public static function set_active(HTMLEditorConfig $config): HTMLEditorConfig
    {
        $identifier = static::get_active_identifier();
        return static::set_config($identifier, $config);
    }

    /**
     * Get the available configurations as a map of friendly_name to
     * configuration name.
     */
    public static function get_available_configs_map(): array
    {
        $configs = [];

        foreach (HTMLEditorConfig::$configs as $identifier => $config) {
            $configs[$identifier] = $config->getOption('friendly_name');
        }

        return $configs;
    }

    /**
     * Get the current value of an option
     *
     * @param string $key The key of the option to get
     * @return mixed The value of the specified option
     */
    abstract public function getOption(string $key): mixed;

    /**
     * Set the value of one option
     * @param string $key The key of the option to set
     * @param mixed $value The value of the option to set
     * @return $this
     */
    abstract public function setOption(string $key, mixed $value): static;

    /**
     * Get the options for this config
     */
    abstract public function getOptions(): array;

    /**
     * Set multiple options. This does not merge recursively, but only at the top level.
     *
     * @param array $options The options to set, as keys and values of the array
     * @return $this
     */
    abstract public function setOptions(array $options): static;

    /**
     * Associative array of data-attributes to apply to the underlying text-area
     */
    public function getAttributes(): array
    {
        return [
            'data-editor' => $this->getConfigType(),
        ];
    }

    /**
     * Initialise the editor on the client side
     */
    abstract public function init(): void;

    /**
     * Get the element rules for server-side sanitisation.
     * Changes made to this ruleset may not affect the config. If you alter it,
     * make sure you pass it into a call to setElementRuleSet().
     */
    abstract public function getElementRuleSet(): HTMLEditorRuleSet;

    /**
     * Set the rules for allowed elements and attributes from a HTMLEditorRuleSet.
     * This will override any previously defined allowed element or attribute rules.
     */
    abstract public function setElementRuleSet(HTMLEditorRuleSet $ruleset): static;

    /**
     * Set the rules for allowed elements and attributes from an associative array.
     * This will override any previously defined allowed element or attribute rules.
     */
    public function setElementRulesFromArray(array $rulesArray): static
    {
        if (empty($rulesArray)) {
            $this->setElementRuleSet(new HTMLEditorRuleSet());
            return $this;
        }
        if (!ArrayLib::is_associative($rulesArray)) {
            throw new InvalidArgumentException('Element rules array must be associative.');
        }

        $ruleset = new HTMLEditorRuleSet();

        // The global rule is a bit special
        if (isset($rulesArray[HTMLEditorElementRule::GLOBAL_NAME])) {
            $globalRuleDefinition = $rulesArray[HTMLEditorElementRule::GLOBAL_NAME];
            // Only the 'attributes' key is allowed for the global rule.
            if (!empty(array_diff(array_keys($globalRuleDefinition), ['attributes']))) {
                throw new InvalidArgumentException(HTMLEditorElementRule::GLOBAL_NAME . ' element rule can only have attributes.');
            }
            // Add global rule attributes
            $this->addAttributeRulesToElementRule($globalRuleDefinition['attributes'] ?? [], $ruleset->getGlobalRule());
            unset($rulesArray[HTMLEditorElementRule::GLOBAL_NAME]);
        }

        // Add conversion rules first.
        foreach ($rulesArray as $ruleName => $ruleDefinition) {
            if (!is_array($ruleDefinition)) {
                continue;
            }
            $convertTo = $ruleDefinition['convertTo'] ?? '';
            // Add the conversion rule if there is one, and if the element to convert to is allowed.
            if (!empty($convertTo) && isset($rulesArray[$convertTo]) && $rulesArray[$convertTo] !== false) {
                $ruleset->addElementSubstitutionRule($ruleName, $convertTo);
            }
        }
        // Add concrete element rules
        foreach ($rulesArray as $ruleName => $ruleDefinition) {
            // Allow rules to be disabled through overridden configuration.
            // Also skip elements slated for conversion because we added those already.
            if ($ruleDefinition === null || $ruleDefinition === false || isset($ruleDefinition['convertTo'])) {
                continue;
            }
            // Use default rule definition if none is supplied
            if ($ruleDefinition === true) {
                $ruleDefinition = [];
            }
            $elementRule = HTMLEditorElementRule::fromArray($ruleName, $ruleDefinition);
            if (isset($ruleDefinition['attributes'])) {
                $this->addAttributeRulesToElementRule($ruleDefinition['attributes'], $elementRule);
            }
            $ruleset->addElementRule($elementRule);
        }

        return $this->setElementRuleSet($ruleset);
    }

    /**
     * Get the name of the client-side component to inject for fields using this config
     */
    public function getSchemaComponent(): string
    {
        if (!static::$schemaComponent) {
            throw new LogicException('schemaComponent must be set on ' . static::class);
        }
        return static::$schemaComponent;
    }

    /**
     * Provide additional schema data for the field this object configures
     */
    public function getConfigSchemaData(): array
    {
        return [
            'attributes' => $this->getAttributes(),
            'editorjs' => null,
        ];
    }

    /**
     * Get the number of rows this config will use in its editable area.
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }

    /**
     * Set the number of rows this config will use in its editable area.
     * This is set by HTMLEditorField - set the number of rows in your field.
     */
    public function setRows(int $numRows): static
    {
        $this->rows = $numRows;
        return $this;
    }

    /**
     * Get the string used to identify this config class in the CMS JavaScript.
     */
    protected function getConfigType(): string
    {
        if (!static::$configType) {
            throw new LogicException('configType must be set on ' . static::class);
        }
        return static::$configType;
    }

    /**
     * Given an associative array of attribute rule definitions, instantiate the HTMLEditorAttributeRules
     * and add them to the given HTMLEditorElementRule
     */
    private function addAttributeRulesToElementRule(array $attributeRuleDefinitions, HTMLEditorElementRule $elementRule): void
    {
        if (empty($attributeRuleDefinitions)) {
            return;
        }
        if (!ArrayLib::is_associative($attributeRuleDefinitions)) {
            throw new InvalidArgumentException('Attribute rules array must be associative.');
        }
        foreach ($attributeRuleDefinitions as $ruleName => $ruleDefinition) {
            // Allow rules to be disabled through overridden configuration
            if ($ruleDefinition === null || $ruleDefinition === false) {
                continue;
            }
            // Use default rule definition if none is supplied
            if ($ruleDefinition === true) {
                $ruleDefinition = [];
            }
            $attributeRule = HTMLEditorAttributeRule::fromArray($ruleName, $ruleDefinition);
            $elementRule->addAttributeRule($attributeRule);
        }
    }
}
