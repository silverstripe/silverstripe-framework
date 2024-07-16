<?php

namespace SilverStripe\Forms\HTMLEditor;

use Exception;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Default configuration for HtmlEditor specific to tinymce
 */
class TinyMCEConfig extends HTMLEditorConfig implements i18nEntityProvider
{
    /**
     * Map of locales to tinymce's supported languages
     *
     * @link https://www.tiny.cloud/get-tiny/language-packages/
     * @config
     * @var array
     */
    private static $tinymce_lang = [
        'ar_EG' => 'ar',
        'az_AZ' => 'az',
        'bg_BG' => 'bg_BG',
        'ca_AD' => 'ca',
        'ca_ES' => 'ca',
        'cs_CZ' => 'cs',
        'cy_GB' => 'cy',
        'da_DK' => 'da',
        'da_GL' => 'da',
        'de_AT' => 'de',
        'de_BE' => 'de',
        'de_CH' => 'de',
        'de_DE' => 'de',
        'de_LI' => 'de',
        'de_LU' => 'de',
        'de_BR' => 'de',
        'de_US' => 'de',
        'el_CY' => 'el',
        'el_GR' => 'el',
        'es_AR' => 'es',
        'es_BO' => 'es',
        'es_CL' => 'es',
        'es_CO' => 'es',
        'es_CR' => 'es',
        'es_CU' => 'es',
        'es_DO' => 'es',
        'es_EC' => 'es',
        'es_ES' => 'es',
        'es_GQ' => 'es',
        'es_GT' => 'es',
        'es_HN' => 'es',
        'es_MX' => 'es_MX',
        'es_NI' => 'es',
        'es_PA' => 'es',
        'es_PE' => 'es',
        'es_PH' => 'es',
        'es_PR' => 'es',
        'es_PY' => 'es',
        'es_SV' => 'es',
        'es_UY' => 'es',
        'es_VE' => 'es',
        'es_AD' => 'es',
        'es_BZ' => 'es',
        'es_US' => 'es',
        'et_EE' => 'et',
        'eo_XX' => 'eo',
        'fa_AF' => 'fa',
        'fa_IR' => 'fa',
        'fa_PK' => 'fa',
        'ff_FI' => 'fi',
        'fr_BE' => 'fr_FR',
        'fr_BF' => 'fr_FR',
        'fr_BI' => 'fr_FR',
        'fr_BJ' => 'fr_FR',
        'fr_CA' => 'fr_FR',
        'fr_CF' => 'fr_FR',
        'fr_CG' => 'fr_FR',
        'fr_CH' => 'fr_FR',
        'fr_CI' => 'fr_FR',
        'fr_CM' => 'fr_FR',
        'fr_DJ' => 'fr_FR',
        'fr_DZ' => 'fr_FR',
        'fr_FR' => 'fr_FR',
        'fr_GA' => 'fr_FR',
        'fr_GF' => 'fr_FR',
        'fr_GN' => 'fr_FR',
        'fr_GP' => 'fr_FR',
        'fr_HT' => 'fr_FR',
        'fr_KM' => 'fr_FR',
        'fr_LU' => 'fr_FR',
        'fr_MA' => 'fr_FR',
        'fr_MC' => 'fr_FR',
        'fr_MG' => 'fr_FR',
        'fr_ML' => 'fr_FR',
        'fr_MQ' => 'fr_FR',
        'fr_MU' => 'fr_FR',
        'fr_NC' => 'fr_FR',
        'fr_NE' => 'fr_FR',
        'fr_PF' => 'fr_FR',
        'fr_PM' => 'fr_FR',
        'fr_RE' => 'fr_FR',
        'fr_RW' => 'fr_FR',
        'fr_SC' => 'fr_FR',
        'fr_SN' => 'fr_FR',
        'fr_SY' => 'fr_FR',
        'fr_TD' => 'fr_FR',
        'fr_TG' => 'fr_FR',
        'fr_TN' => 'fr_FR',
        'fr_VU' => 'fr_FR',
        'fr_WF' => 'fr_FR',
        'fr_YT' => 'fr_FR',
        'fr_GB' => 'fr_FR',
        'fr_US' => 'fr_FR',
        'he_IL' => 'he_IL',
        'hi_IN' => 'hi',
        'hr_HR' => 'hr',
        'hu_HU' => 'hu_HU',
        'hu_AT' => 'hu_HU',
        'hu_RO' => 'hu_HU',
        'hu_RS' => 'hu_HU',
        'id_ID' => 'id',
        'is_IS' => 'is_IS',
        'it_CH' => 'it',
        'it_IT' => 'it',
        'it_SM' => 'it',
        'it_FR' => 'it',
        'it_HR' => 'it',
        'it_US' => 'it',
        'it_VA' => 'it',
        'ja_JP' => 'ja',
        'ko_KP' => 'ko_KR',
        'ko_KR' => 'ko_KR',
        'ko_CN' => 'ko_KR',
        'lt_LT' => 'lt',
        'lv_LV' => 'lv',
        'nb_NO' => 'nb_NO',
        'nb_SJ' => 'nb_NO',
        'ne_NP' => 'ne',
        'nl_AN' => 'nl',
        'nl_AW' => 'nl',
        'nl_BE' => 'nl_BE',
        'nl_NL' => 'nl',
        'nl_SR' => 'nl',
        'pl_PL' => 'pl',
        'pl_UA' => 'pl',
        // Note: All of the pt_* except pt_BR should map to pt_PT but that translation isn't available for tinymce6 yet.
        'pt_AO' => 'pt_BR',
        'pt_BR' => 'pt_BR',
        'pt_CV' => 'pt_BR',
        'pt_GW' => 'pt_BR',
        'pt_MZ' => 'pt_BR',
        'pt_PT' => 'pt_BR',
        'pt_ST' => 'pt_BR',
        'pt_TL' => 'pt_BR',
        'ro_MD' => 'ro',
        'ro_RO' => 'ro',
        'ro_RS' => 'ro',
        'ru_BY' => 'ru',
        'ru_KG' => 'ru',
        'ru_KZ' => 'ru',
        'ru_RU' => 'ru',
        'ru_SJ' => 'ru',
        'ru_UA' => 'ru',
        'sk_SK' => 'sk',
        'sk_RS' => 'sk',
        'sl_SI' => 'sl_SI',
        'sr_RS' => 'sr',
        'sv_FI' => 'sv_SE',
        'sv_SE' => 'sv_SE',
        'th_TH' => 'th_TH',
        'tr_CY' => 'tr',
        'tr_TR' => 'tr',
        'tr_DE' => 'tr',
        'tr_MK' => 'tr',
        'uk_UA' => 'uk',
        'vi_VN' => 'vi',
        'vi_US' => 'vi',
        'zh_CN' => 'zh_Hans',
        'zh_HK' => 'zh_Hans',
        'zh_MO' => 'zh_Hans',
        'zh_SG' => 'zh_Hans',
        'zh_TW' => 'zh_Hans',
        'zh_ID' => 'zh_Hans',
        'zh_MY' => 'zh_Hans',
        'zh_TH' => 'zh_Hans',
        'zh_US' => 'zh_Hans',
    ];

    /**
     * Location of module relative to BASE_DIR. This must contain the following dirs
     * - plugins
     * - themes
     * - skins
     *
     * Supports vendor/module:path
     *
     * @config
     * @var string
     */
    private static $base_dir = null;

    /**
     * Location of tinymce translation files relative to BASE_DIR.
     *
     * Supports vendor/module:path
     *
     * @config
     * @var string
     */
    private static $lang_dir = null;

    /**
     * Extra editor.css file paths.
     *
     * Supports vendor/module:path syntax
     *
     * @config
     * @var array
     */
    private static $editor_css = [];

    /**
     * List of content css files to use for this instance, or null to default to editor_css config.
     *
     * @var string[]|null
     */
    protected $contentCSS = null;


    /**
     * List of image size preset that will appear when you select an image. Each preset can have the following:
     * * `name` to store an internal name for the preset (required)
     * * `i18n` to store a translation key (e.g.: `SilverStripe\Forms\HTMLEditor\TinyMCEConfig.BESTFIT`)
     * * `text` that will appear in the button (should be the default English translation)
     * * `width` which will define the horizontal size of the preset. If not provided, the preset will match the
     *   original size of the image.
     * @var array[]
     * @config
     */
    private static $image_size_presets = [ ];

    /**
     * TinyMCE JS settings
     *
     * @link https://www.tiny.cloud/docs/tinymce/6/tinydrive-getting-started/#configure-the-required-tinymce-options
     *
     * @var array
     */
    protected $settings = [
        'fix_list_elements' => true, // https://www.tiny.cloud/docs/tinymce/6/content-filtering/#fix_list_elements
        'formats' => [
            'alignleft' => [
                [
                    'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,li',
                    'classes' =>'text-left'
                ],
                [
                    'selector' => 'div,ul,ol,table,img,figure',
                    'classes' =>'left'
                ]
            ],
            'aligncenter' => [
                [
                    'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,li',
                    'classes' =>'text-center'
                ],
                [
                    'selector' => 'div,ul,ol,table,img,figure',
                    'classes' =>'center'
                ]
            ],
            'alignright' => [
                [
                    'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,li',
                    'classes' =>'text-right'
                ],
                [
                    'selector' => 'div,ul,ol,table,img,figure',
                    'classes' =>'right'
                ]
            ],
            'alignjustify' => [
                [
                    'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,li',
                    'classes' =>'text-justify'
                ],
            ],
        ],
        'friendly_name' => '(Please set a friendly name for this config)',
        'priority' => 0, // used for Per-member config override
        'browser_spellcheck' => true,
        'body_class' => 'typography',
        'statusbar' => true,
        'elementpath' => true, // https://www.tiny.cloud/docs/tinymce/6/statusbar-configuration-options/#elementpath
        'relative_urls' => true,
        'remove_script_host' => true,
        'convert_urls' => false, // Prevent site-root images being rewritten to base relative
        'menubar' => false,
        'language' => 'en',
        'branding' => false,
        'promotion' => false,
        'upload_folder_id' => null, // Set folder ID for insert media dialog
        'link_default_target' => '_blank', // https://www.tiny.cloud/docs/tinymce/6/autolink/#example-using-link_default_target
        'convert_unsafe_embeds' => true, // SS-2024-001
    ];

    /**
     * Holder list of enabled plugins
     *
     * @var array
     */
    protected $plugins = [
        'table' => null,
        'emoticons' => null,
        'code' => null,
        'importcss' => null,
        'lists' => null,
        'autolink' => null,
        'searchreplace' => null,
        'visualblocks' => null,
        'wordcount' => null,
    ];

    /**
     * Theme name
     *
     * @var string
     */
    protected $theme = 'silver';

    /**
     * Get the theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set the theme name
     *
     * @param string $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Holder list of buttons, organised by line. This array is 1-based indexed array
     *
     * {@link https://www.tiny.cloud/docs/tinymce/6/basic-setup/#toolbar-configuration}
     *
     * @var array
     */
    protected $buttons = [
        1 => [
            'bold', 'italic', 'underline', 'removeformat', '|',
            'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
            'bullist', 'numlist', 'outdent', 'indent',
        ],
        2 => [
            'blocks', '|',
            'pastetext', '|',
            'table', 'sslink', 'unlink', '|',
            'code', 'visualblocks'
        ],
        3 => []
    ];

    public function getOption($key)
    {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return null;
    }

    public function setOption($key, $value)
    {
        $this->settings[$key] = $value;
        return $this;
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->settings[$key] = $value;
        }
        return $this;
    }

    /**
     * Get all settings
     *
     * @return array
     */
    protected function getSettings()
    {
        return $this->settings;
    }

    public function getAttributes()
    {
        return [
            'data-editor' => 'tinyMCE', // Register ss.editorWrappers.tinyMCE
            'data-config' => json_encode($this->getConfig()),
        ];
    }

    /**
     * Enable one or several plugins. Will maintain unique list if already
     * enabled plugin is re-passed. If passed in as a map of plugin-name to path,
     * the plugin will be loaded by tinymce.PluginManager.load() instead of through tinyMCE.init().
     * Keep in mind that these externals plugins require a dash-prefix in their name.
     *
     * @see https://www.tiny.cloud/docs/tinymce/6/editor-important-options/#external_plugins
     *
     * If passing in a non-associative array, the plugin name should be located in the standard tinymce
     * plugins folder.
     *
     * If passing in an associative array, the key of each item should be the plugin name.
     * The value of each item is one of:
     *  - null - Will be treated as a standard plugin in the standard location
     *  - relative path - Will be treated as a relative url
     *  - absolute url - Some url to an external plugin
     *  - An instance of ModuleResource object containing the plugin
     *
     * @param string|array ...$plugin a string, or several strings, or a single array of strings - The plugins to enable
     * @return $this
     */
    public function enablePlugins($plugin)
    {
        $plugins = func_get_args();
        if (is_array(current($plugins ?? []))) {
            $plugins = current($plugins ?? []);
        }
        foreach ($plugins as $name => $path) {
            // if plugins are passed without a path
            if (is_numeric($name)) {
                $name = $path;
                $path = null;
            }
            if (!array_key_exists($name, $this->plugins ?? [])) {
                $this->plugins[$name] = $path;
            }
        }
        return $this;
    }

    /**
     * Enable one or several plugins. Will properly handle being passed a plugin that is already disabled
     * @param string|array ...$plugin a string, or several strings, or a single array of strings - The plugins to enable
     * @return $this
     */
    public function disablePlugins($plugin)
    {
        $plugins = func_get_args();
        if (is_array(current($plugins ?? []))) {
            $plugins = current($plugins ?? []);
        }
        foreach ($plugins as $name) {
            unset($this->plugins[$name]);
        }
        return $this;
    }

    /**
     * Gets the list of all enabled plugins as an associative array.
     * Array keys are the plugin names, and values are potentially the plugin location,
     * or ModuleResource object
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Get list of plugins without custom locations, which is the set of
     * plugins which can be loaded via the standard plugin path, and could
     * potentially be minified
     *
     * @return array
     */
    public function getInternalPlugins()
    {
        // Return only plugins with no custom url
        $plugins = [];
        foreach ($this->getPlugins() as $name => $url) {
            if (empty($url)) {
                $plugins[] = $name;
            }
        }
        return $plugins;
    }

    /**
     * Get all button rows, skipping empty rows
     *
     * @return array
     */
    public function getButtons()
    {
        return array_filter($this->buttons ?? []);
    }

    /**
     * Totally re-set the buttons on a given line
     *
     * @param int $line The line number to redefine, from 1 to 3
     * @param string|string[] $buttons,... An array of strings, or one or more strings.
     *                                     The button names to assign to this line.
     * @return $this
     */
    public function setButtonsForLine($line, $buttons)
    {
        if (func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        $this->buttons[$line] = is_array($buttons) ? $buttons : [$buttons];
        return $this;
    }

    /**
     * Add buttons to the end of a line
     * @param int $line The line number to redefine, from 1 to 3
     * @param string ...$buttons A string or several strings, or a single array of strings.
     * The button names to add to this line
     * @return $this
     */
    public function addButtonsToLine($line, $buttons)
    {
        if (func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if (!is_array($buttons)) {
            $buttons = [$buttons];
        }
        foreach ($buttons as $button) {
            $this->buttons[$line][] = $button;
        }
        return $this;
    }

    /**
     * Internal function for adding and removing buttons related to another button
     * @param string $name The name of the button to modify
     * @param int $offset The offset relative to that button to perform an array_splice at.
     * 0 for before $name, 1 for after.
     * @param int $del The number of buttons to remove at the position given by index(string) + offset
     * @param mixed $add An array or single item to insert at the position given by index(string) + offset,
     * or null for no insertion
     * @return bool True if $name matched a button, false otherwise
     */
    protected function modifyButtons($name, $offset, $del = 0, $add = null)
    {
        foreach ($this->buttons as &$buttons) {
            if (($idx = array_search($name, $buttons ?? [])) !== false) {
                if ($add) {
                    array_splice($buttons, $idx + $offset, $del, $add);
                } else {
                    array_splice($buttons, $idx + $offset, $del);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Insert buttons before the first occurrence of another button
     * @param string $before the name of the button to insert other buttons before
     * @param string ...$buttons a string, or several strings, or a single array of strings.
     * The button names to insert before that button
     * @return bool True if insertion occurred, false if it did not (because the given button name was not found)
     */
    public function insertButtonsBefore($before, $buttons)
    {
        if (func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if (!is_array($buttons)) {
            $buttons = [$buttons];
        }
        return $this->modifyButtons($before, 0, 0, $buttons);
    }

    /**
     * Insert buttons after the first occurrence of another button
     * @param string $after the name of the button to insert other buttons before
     * @param string ...$buttons a string, or several strings, or a single array of strings.
     * The button names to insert after that button
     * @return bool True if insertion occurred, false if it did not (because the given button name was not found)
     */
    public function insertButtonsAfter($after, $buttons)
    {
        if (func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if (!is_array($buttons)) {
            $buttons = [$buttons];
        }
        return $this->modifyButtons($after, 1, 0, $buttons);
    }

    /**
     * Remove the first occurrence of buttons
     * @param string|string[] $buttons,... An array of strings, or one or more strings. The button names to remove.
     */
    public function removeButtons($buttons)
    {
        if (func_num_args() > 1) {
            $buttons = func_get_args();
        }
        if (!is_array($buttons)) {
            $buttons = [$buttons];
        }
        foreach ($buttons as $button) {
            $this->modifyButtons($button, 0, 1);
        }
    }

    /**
     * Generate the JavaScript that will set TinyMCE's configuration:
     * - Parse all configurations into JSON objects to be used in JavaScript
     * - Includes TinyMCE and configurations using the {@link Requirements} system
     *
     * @return array
     */
    protected function getConfig()
    {
        $settings = $this->getSettings();

        // https://www.tiny.cloud/docs/tinymce/6/url-handling/#document_base_url
        $settings['document_base_url'] = rtrim(Director::absoluteBaseURL(), '/') . '/';

        // https://www.tiny.cloud/docs/tinymce/6/apis/tinymce.root/#properties
        $baseResource = $this->getTinyMCEResource();
        if ($baseResource instanceof ModuleResource) {
            $tinyMCEBaseURL = $baseResource->getURL();
        } else {
            $tinyMCEBaseURL = Controller::join_links(Director::baseURL(), $baseResource);
        }
        $settings['baseURL'] = $tinyMCEBaseURL;

        // map all plugins to absolute urls for loading
        $plugins = [];
        foreach ($this->getPlugins() as $plugin => $path) {
            if ($path instanceof ModuleResource) {
                $path = Director::absoluteURL($path->getURL());
            } elseif (!$path) {
                // Empty paths: Convert to urls in standard base url
                $path = Controller::join_links(
                    $tinyMCEBaseURL,
                    "plugins/{$plugin}/plugin.min.js"
                );
            } elseif (!Director::is_absolute_url($path)) {
                // Non-absolute urls are made absolute
                $path = Director::absoluteURL((string) $path);
            }
            $plugins[$plugin] = $path;
        }

        // https://www.tiny.cloud/docs/tinymce/6/editor-important-options/#external_plugins
        if ($plugins) {
            $settings['external_plugins'] = $plugins;
        }

        // https://www.tiny.cloud/docs/tinymce/6/basic-setup/#toolbar-configuration
        $buttons = $this->getButtons();
        $settings['toolbar'] = [];
        foreach ($buttons as $rowButtons) {
            $row = implode(' ', $rowButtons);
            if (count($buttons ?? []) > 1) {
                $settings['toolbar'][] = $row;
            } else {
                $settings['toolbar'] = $row;
            }
        }

        // https://www.tiny.cloud/docs/tinymce/6/add-css-options/#content_css
        $settings['content_css'] = $this->getEditorCSS();

        // https://www.tiny.cloud/docs/tinymce/6/editor-theme/#theme_url
        $theme = $this->getTheme();
        if (!Director::is_absolute_url($theme)) {
            $theme = Controller::join_links($tinyMCEBaseURL, "themes/{$theme}/theme.min.js");
        }
        $settings['theme_url'] = $theme;

        $this->initImageSizePresets($settings);

        // Send back
        return $settings;
    }

    /**
     * Initialise the image preset on the settings array. This is a custom configuration option that asset-admin reads
     * to provide some preset image sizes.
     * @param array $settings
     */
    private function initImageSizePresets(array &$settings): void
    {
        if (empty($settings['image_size_presets'])) {
            $settings['image_size_presets'] = static::config()->get('image_size_presets');
        }

        foreach ($settings['image_size_presets'] as &$preset) {
            if (isset($preset['width'])) {
                $preset['width'] = (int) $preset['width'];
            }

            if (isset($preset['i18n'])) {
                $preset['text'] = _t(
                    $preset['i18n'],
                    isset($preset['text']) ? $preset['text'] : ''
                );
            } elseif (empty($preset['text']) && isset($preset['width'])) {
                $preset['text'] = _t(
                    TinyMCEConfig::class . '.PIXEL_WIDTH',
                    '{width} pixels',
                    $preset
                );
            }
        }
    }

    /**
     * Get location of all editor.css files.
     * All resource specifiers are resolved to urls.
     *
     * @return array
     */
    protected function getEditorCSS()
    {
        $editor = [];
        $resourceLoader = ModuleResourceLoader::singleton();
        foreach ($this->getContentCSS() as $contentCSS) {
            $editor[] = $resourceLoader->resolveURL($contentCSS);
        }
        return $editor;
    }

    /**
     * Get list of resource paths to css files.
     *
     * Will default to `editor_css` config, as well as any themed `editor.css` files.
     * Use setContentCSS() to override.
     *
     * @return string[]
     */
    public function getContentCSS()
    {
        // Prioritise instance specific content
        if (isset($this->contentCSS)) {
            return $this->contentCSS;
        }

        // Add standard editor.css
        $editor = [];
        $editorCSSFiles = $this->config()->get('editor_css');
        if ($editorCSSFiles) {
            foreach ($editorCSSFiles as $editorCSS) {
                $editor[] = $editorCSS;
            }
        }

        // Themed editor.css
        $themes = HTMLEditorConfig::getThemes() ?: SSViewer::get_themes();
        $themedEditor = ThemeResourceLoader::inst()->findThemedCSS('editor', $themes);
        if ($themedEditor) {
            $editor[] = $themedEditor;
        }
        return $editor;
    }

    /**
     * Set explicit set of CSS resources to use for `content_css` option.
     *
     * Note: If merging with default paths, you should call getContentCSS() and merge
     * prior to assignment.
     *
     * @param string[] $css Array of resource paths. Supports module prefix,
     * e.g. `silverstripe/admin:client/dist/styles/editor.css`
     * @return $this
     */
    public function setContentCSS($css)
    {
        $this->contentCSS = $css;
        return $this;
    }

    /**
     * Generate gzipped TinyMCE configuration including plugins and languages.
     * This ends up "pre-loading" TinyMCE bundled with the required plugins
     * so that multiple HTTP requests on the client don't need to be made.
     *
     * @return string
     * @throws Exception
     */
    public function getScriptURL()
    {
        $generator = Injector::inst()->get(TinyMCEScriptGenerator::class);
        return $generator->getScriptURL($this);
    }

    public function init()
    {
        // include TinyMCE Javascript
        Requirements::javascript($this->getScriptURL());
    }

    public function getConfigSchemaData()
    {
        $data = parent::getConfigSchemaData();
        $data['editorjs'] = $this->getScriptURL();
        return $data;
    }

    /**
     * Get the current tinyMCE language
     *
     * @return string Language
     */
    public static function get_tinymce_lang()
    {
        $lang = static::config()->get('tinymce_lang');
        $locale = i18n::get_locale();
        if (isset($lang[$locale])) {
            return $lang[$locale];
        }
        return 'en';
    }

    /**
     * Get the URL for the language pack of the current language
     *
     * @return string Language
     */
    public static function get_tinymce_lang_url(): string
    {
        $lang = static::get_tinymce_lang();
        $dir = static::config()->get('lang_dir');
        if ($lang !== 'en' && !empty($dir)) {
            $resource = ModuleResourceLoader::singleton()->resolveResource($dir);
            return Director::absoluteURL($resource->getRelativeResource($lang . '.js')->getURL());
        }
        return '';
    }

    /**
     * Returns the full filesystem path to TinyMCE resources (which could be different from the original tinymce
     * location in the module).
     *
     * Path will be absolute.
     *
     * @return string
     * @throws Exception
     */
    public function getTinyMCEResourcePath()
    {
        $resource = $this->getTinyMCEResource();
        if ($resource instanceof ModuleResource) {
            return $resource->getPath();
        }
        return Director::baseFolder() . '/' . $resource;
    }

    /**
     * Get front-end url to tinymce resources
     *
     * @return string
     * @throws Exception
     */
    public function getTinyMCEResourceURL()
    {
        $resource = $this->getTinyMCEResource();
        if ($resource instanceof ModuleResource) {
            return $resource->getURL();
        }
        return $resource;
    }

    /**
     * Get resource root for TinyMCE, either as a string or ModuleResource instance
     * Path will be relative to BASE_PATH if string.
     *
     * @return ModuleResource|string
     * @throws Exception
     */
    public function getTinyMCEResource()
    {
        $configDir = static::config()->get('base_dir');
        if ($configDir) {
            return ModuleResourceLoader::singleton()->resolveResource($configDir);
        }

        throw new Exception(sprintf(
            'If the silverstripe/admin module is not installed you must set the TinyMCE path in %s.base_dir',
            __CLASS__
        ));
    }

    /**
     * Sets the upload folder name used by the insert media dialog
     *
     * @param string $folderName
     * @return $this
     */
    public function setFolderName(string $folderName): TinyMCEConfig
    {
        $folder = Folder::find_or_make($folderName);
        $folderID = $folder ? $folder->ID : null;
        $this->setOption('upload_folder_id', $folderID);
        return $this;
    }

    public function provideI18nEntities()
    {
        $entities = [
            TinyMCEConfig::class . '.PIXEL_WIDTH' => '{width} pixels',
        ];
        foreach (static::config()->get('image_size_presets') as $preset) {
            if (empty($preset['i18n']) || empty($preset['text'])) {
                continue;
            }
            $entities[$preset['i18n']] = $preset['text'];
        }

        return $entities;
    }
}
