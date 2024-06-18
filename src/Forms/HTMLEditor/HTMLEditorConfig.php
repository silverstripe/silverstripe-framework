<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * A PHP version of TinyMCE's configuration, to allow various parameters to be configured on a site or section basis
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
    protected static $configs = [];

    /**
     * Identifier key of current config. This will match an array key in $configs.
     * If left blank, will fall back to value of default_config set via config.
     *
     * @var string
     */
    protected static $current = null;

    /**
     * Name of default config. This will be ignored if $current is assigned a value.
     *
     * @config
     * @var string
     */
    private static $default_config = 'default';

    /**
     * List of themes defined for the frontend
     *
     * @config
     * @var array
     */
    private static $user_themes = [];

    /**
     * List of the current themes set for this config
     *
     * @var array
     */
    protected static $current_themes = null;

    /**
     * Get the HTMLEditorConfig object for the given identifier. This is a correct way to get an HTMLEditorConfig
     * instance - do not call 'new'
     *
     * @param string $identifier The identifier for the config set. If omitted, the active config is returned.
     * @return static The configuration object.
     * This will be created if it does not yet exist for that identifier
     */
    public static function get($identifier = null)
    {
        if (!$identifier) {
            return static::get_active();
        }
        // Create new instance if unconfigured
        if (!isset(HTMLEditorConfig::$configs[$identifier])) {
            HTMLEditorConfig::$configs[$identifier] = static::create();
            HTMLEditorConfig::$configs[$identifier]->setOption('editorIdentifier', $identifier);
        }
        return HTMLEditorConfig::$configs[$identifier];
    }

    /**
     * Assign a new config, or clear existing, for the given identifier
     *
     * @param string $identifier A specific identifier
     * @param HTMLEditorConfig $config Config to set, or null to clear
     * @return HTMLEditorConfig The assigned config
     */
    public static function set_config($identifier, HTMLEditorConfig $config = null)
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
     * @return array
     */
    public static function getThemes()
    {
        if (isset(static::$current_themes)) {
            return static::$current_themes;
        }
        return Config::inst()->get(static::class, 'user_themes');
    }

    /**
     * Sets the current theme
     *
     * @param array $themes
     */
    public static function setThemes($themes)
    {
        static::$current_themes = $themes;
    }

    /**
     * Set the currently active configuration object. Note that the existing active
     * config will not be renamed to the new identifier.
     *
     * @param string $identifier The identifier for the config set
     */
    public static function set_active_identifier($identifier)
    {
        HTMLEditorConfig::$current = $identifier;
    }

    /**
     * Get the currently active configuration identifier. Will fall back to default_config
     * if unassigned.
     *
     * @return string The active configuration identifier
     */
    public static function get_active_identifier()
    {
        $identifier = HTMLEditorConfig::$current ?: static::config()->get('default_config');
        return $identifier;
    }

    /**
     * Get the currently active configuration object
     *
     * @return HTMLEditorConfig The active configuration object
     */
    public static function get_active()
    {
        $identifier = HTMLEditorConfig::get_active_identifier();
        return HTMLEditorConfig::get($identifier);
    }

    /**
     * Assigns the currently active config an explicit instance
     *
     * @param HTMLEditorConfig $config
     * @return HTMLEditorConfig The given config
     */
    public static function set_active(HTMLEditorConfig $config)
    {
        $identifier = static::get_active_identifier();
        return static::set_config($identifier, $config);
    }

    /**
     * Get the available configurations as a map of friendly_name to
     * configuration name.
     *
     * @return array
     */
    public static function get_available_configs_map()
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
    abstract public function getOption($key);

    /**
     * Set the value of one option
     * @param string $key The key of the option to set
     * @param mixed $value The value of the option to set
     * @return $this
     */
    abstract public function setOption($key, $value);

    /**
     * Set multiple options. This does not merge recursively, but only at the top level.
     *
     * @param array $options The options to set, as keys and values of the array
     * @return $this
     */
    abstract public function setOptions($options);

    /**
     * Associative array of data-attributes to apply to the underlying text-area
     *
     * @return array
     */
    abstract public function getAttributes();

    /**
     * Initialise the editor on the client side
     */
    abstract public function init();

    /**
     * Provide additional schema data for the field this object configures
     *
     * @return array
     */
    public function getConfigSchemaData()
    {
        return [
            'attributes' => $this->getAttributes(),
            'editorjs' => null,
        ];
    }
}
