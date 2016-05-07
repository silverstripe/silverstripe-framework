<?php

/**
 * Default configuration for HtmlEditor specific to tinymce
 */
class TinyMCEConfig extends HTMLEditorConfig {

	/**
	 * Location of module relative to BASE_DIR. This must contain the following dirs
	 * - plugins
	 * - themes
	 * - skins
	 *
	 * @config
	 * @var string
	 */
	private static $base_dir = 'framework/thirdparty/tinymce';

	/**
	 * TinyMCE JS settings
	 *
	 * @link https://www.tinymce.com/docs/configure/
	 *
	 * @var array
	 */
    protected $settings = array(
		'fix_list_elements' => true, // https://www.tinymce.com/docs/configure/content-filtering/#fix_list_elements
		'friendly_name' => '(Please set a friendly name for this config)',
		'priority' => 0, // used for Per-member config override
		'browser_spellcheck' => true,
		'body_class' => 'typography',
        'elementpath' => false, // https://www.tinymce.com/docs/configure/editor-appearance/#elementpath
		'relative_urls' => true,
		'remove_script_host' => true,
		'convert_urls' => false, // Prevent site-root images being rewritten to base relative
		'menubar' => false,
		'language' => 'en',
    );

	/**
	 * Holder list of enabled plugins
     *
     * @var array
	 */
	protected $plugins = array(
		'table' => null,
		'emoticons' => null,
		'paste' => null,
		'code' => null,
		'link' => null,
		'importcss' => null,
	);

	/**
	 * Theme name
	 *
	 * @var string
	 */
	protected $theme = 'modern';

	/**
	 * Get the theme
	 *
	 * @return string
	 */
	public function getTheme() {
		return $this->theme;
	}

	/**
	 * Set the theme name
	 *
	 * @param string $theme
	 * @return $this
	 */
	public function setTheme($theme) {
		$this->theme = $theme;
		return $this;
	}

	/**
	 * Holder list of buttons, organised by line. This array is 1-based indexed array
     *
     * {@link https://www.tinymce.com/docs/advanced/editor-control-identifiers/#toolbarcontrols}
     *
     * @var array
	 */
	protected $buttons = array(
		1 => array(
            'bold', 'italic', 'underline', 'removeformat', '|',
			'alignleft', 'aligncenter', 'alignright', 'alignjustify', '|',
			'bullist', 'numlist', 'outdent', 'indent',
        ),
		2 => array(
            'formatselect', '|',
			'paste', 'pastetext', '|',
			'table', 'ssmedia', 'sslink', 'unlink', '|',
			'code'
        ),
		3 => array()
	);

	public function getOption($key) {
		if(isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return null;
	}

	public function setOption($key,$value) {
		$this->settings[$key] = $value;
		return $this;
	}

	public function setOptions($options) {
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
    protected function getSettings() {
        return $this->settings;
    }

    public function getAttributes() {
        return [
            'data-editor' => 'tinyMCE', // Register ss.editorWrappers.tinyMCE
            'data-config' => Convert::array2json($this->getConfig())
        ];
    }

	/**
	 * Enable one or several plugins. Will maintain unique list if already
	 * enabled plugin is re-passed. If passed in as a map of plugin-name to path,
	 * the plugin will be loaded by tinymce.PluginManager.load() instead of through tinyMCE.init().
	 * Keep in mind that these externals plugins require a dash-prefix in their name.
	 *
	 * @see http://wiki.moxiecode.com/index.php/TinyMCE:API/tinymce.PluginManager/load
	 *
	 * If passing in a non-associative array, the plugin name should be located in the standard tinymce
	 * plugins folder.
	 *
	 * If passing in an associative array, the key of each item should be the plugin name.
	 * The value of each item is one of:
	 *  - null - Will be treated as a stardard plugin in the standard location
	 *  - relative path - Will be treated as a relative url
	 *  - absolute url - Some url to an external plugin
	 *
	 * @param string $plugin,... a string, or several strings, or a single array of strings - The plugins to enable
	 * @return $this
	 */
	public function enablePlugins($plugin) {
		$plugins = func_get_args();
		if (is_array(current($plugins))) {
            $plugins = current($plugins);
        }
		foreach ($plugins as $name => $path) {
			// if plugins are passed without a path
			if(is_numeric($name)) {
				$name = $path;
				$path = null;
			}
			if (!array_key_exists($name, $this->plugins)) {
                $this->plugins[$name] = $path;
            }
		}
		return $this;
	}

	/**
	 * Enable one or several plugins. Will properly handle being passed a plugin that is already disabled
	 * @param string $plugin,... a string, or several strings, or a single array of strings - The plugins to enable
	 * @return $this
	 */
	public function disablePlugins($plugin) {
		$plugins = func_get_args();
		if (is_array(current($plugins))) {
            $plugins = current($plugins);
        }
		foreach ($plugins as $name) {
			unset($this->plugins[$name]);
		}
		return $this;
	}

	/**
     * Gets the list of all enabled plugins as an associative array.
     * Array keys are the plugin names, and values are potentially the plugin location
     *
	 * @return array
	 */
	public function getPlugins() {
		return $this->plugins;
	}

	/**
	 * Get list of plugins without custom locations, which is the set of
	 * plugins which can be loaded via the standard plugin path, and could
	 * potentially be minified
	 *
	 * @return array
	 */
	public function getInternalPlugins() {
		// Return only plugins with no custom url
		$plugins = [];
		foreach($this->getPlugins() as $name => $url) {
			if(empty($url)) {
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
    public function getButtons() {
        return array_filter($this->buttons);
    }

	/**
	 * Totally re-set the buttons on a given line
	 *
	 * @param int $line The line number to redefine, from 1 to 3
	 * @param string $buttons,... A string or several strings, or a single array of strings.
     * The button names to assign to this line.
	 * @return $this
	 */
	public function setButtonsForLine($line, $buttons) {
		if (func_num_args() > 2) {
			$buttons = func_get_args();
			array_shift($buttons);
		}
		$this->buttons[$line] = is_array($buttons) ? $buttons : array($buttons);
		return $this;
	}

	/**
	 * Add buttons to the end of a line
	 * @param int $line The line number to redefine, from 1 to 3
	 * @param string $buttons,... A string or several strings, or a single array of strings.
     * The button names to add to this line
	 * @return $this
	 */
	public function addButtonsToLine($line, $buttons) {
        if(func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if(!is_array($buttons)) {
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
	protected function modifyButtons($name, $offset, $del=0, $add=null) {
		foreach ($this->buttons as &$buttons) {
			if (($idx = array_search($name, $buttons)) !== false) {
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
	 * Insert buttons before the first occurance of another button
	 * @param string $before the name of the button to insert other buttons before
	 * @param string $buttons,... a string, or several strings, or a single array of strings.
     * The button names to insert before that button
	 * @return bool True if insertion occured, false if it did not (because the given button name was not found)
	 */
	public function insertButtonsBefore($before, $buttons) {
        if(func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if(!is_array($buttons)) {
            $buttons = [$buttons];
        }
		return $this->modifyButtons($before, 0, 0, $buttons);
	}

	/**
	 * Insert buttons after the first occurance of another button
	 * @param string $after the name of the button to insert other buttons before
	 * @param string $buttons,... a string, or several strings, or a single array of strings.
     * The button names to insert after that button
	 * @return bool True if insertion occured, false if it did not (because the given button name was not found)
	 */
	public function insertButtonsAfter($after, $buttons) {
		if(func_num_args() > 2) {
            $buttons = func_get_args();
            array_shift($buttons);
        }
        if(!is_array($buttons)) {
            $buttons = [$buttons];
        }
		return $this->modifyButtons($after, 1, 0, $buttons);
	}

	/**
	 * Remove the first occurance of buttons
	 * @param string $buttons,... one or more strings - the name of the buttons to remove
	 * @return null
	 */
	public function removeButtons($buttons) {
        if(func_num_args() > 1) {
            $buttons = func_get_args();
        }
        if(!is_array($buttons)) {
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
	protected function getConfig() {
        $settings = $this->getSettings();

		// https://www.tinymce.com/docs/configure/url-handling/#document_base_url
        $settings['document_base_url'] = Director::absoluteBaseURL();

		// https://www.tinymce.com/docs/api/class/tinymce.editormanager/#baseURL
		$tinyMCEBaseURL = Controller::join_links(
			Director::absoluteBaseURL(),
			$this->config()->base_dir
		);
		$settings['baseURL'] = $tinyMCEBaseURL;

        // map all plugins to absolute urls for loading
		$plugins = array();
        foreach($this->getPlugins() as $plugin => $path) {
			if(!$path) {
				// Empty paths: Convert to urls in standard base url
				$path = Controller::join_links(
					$tinyMCEBaseURL,
					"plugins/{$plugin}/plugin.min.js"
				);
			} elseif(!Director::is_absolute_url($path)) {
				// Non-absolute urls are made absolute
				$path = Director::absoluteURL($path);
			}
            $plugins[$plugin] = $path;
        }

        // https://www.tinymce.com/docs/configure/integration-and-setup/#external_plugins
        if($plugins) {
            $settings['external_plugins'] = $plugins;
        }

        // https://www.tinymce.com/docs/configure/editor-appearance/#groupingtoolbarcontrols
        $buttons = $this->getButtons();
        $settings['toolbar'] = [];
        foreach($buttons as $rowButtons) {
            $row = implode(' ', $rowButtons);
            if(count($buttons) > 1) {
                $settings['toolbar'][] = $row;
            } else {
                $settings['toolbar'] = $row;
            }
        }

        // https://www.tinymce.com/docs/configure/content-appearance/#content_css
		$settings['content_css'] = $this->getEditorCSS();

		// https://www.tinymce.com/docs/configure/editor-appearance/#theme_url
		$theme = $this->getTheme();
		if(!Director::is_absolute_url($theme)) {
			$theme = Controller::join_links($tinyMCEBaseURL, "themes/{$theme}/theme.min.js");
		}
		$settings['theme_url'] = $theme;

        // Send back
        return $settings;
	}

    /**
     * Get location of all editor.css files
     *
     * @return array
     */
    protected function getEditorCSS() {
        $editor = array();
		$editor[] = Controller::join_links(
			Director::absoluteBaseURL(),
			FRAMEWORK_ADMIN_DIR . '/client/dist/styles/editor.css'
		);
		if($theme = SSViewer::get_theme_folder()) {
			$editorDir = $theme . '/css/editor.css';
			if(file_exists(BASE_PATH . '/' . $editorDir)) {
				$editor[] = Controller::join_links(
					Director::absoluteBaseURL(),
					$editorDir
				);
			}
		}
		return $editor;
	}

	/**
	 * Generate gzipped TinyMCE configuration including plugins and languages.
	 * This ends up "pre-loading" TinyMCE bundled with the required plugins
	 * so that multiple HTTP requests on the client don't need to be made.
	 *
	 * @return string
	 */
	public function getScriptURL() {
		// If gzip is disabled just return core script url
		$useGzip = Config::inst()->get('HTMLEditorField', 'use_gzip');
		if(!$useGzip) {
			return THIRDPARTY_DIR . '/tinymce/tinymce.min.js';
		}

		// tinyMCE JS requirement
		require_once THIRDPARTY_PATH . '/tinymce/tiny_mce_gzip.php';
		$tag = TinyMCE_Compressor::renderTag(array(
			'url' => THIRDPARTY_DIR . '/tinymce/tiny_mce_gzip.php',
			'plugins' => implode(',', $this->getInternalPlugins()),
			'themes' => $this->getTheme(),
			'languages' => $this->getOption('language')
		), true);
		preg_match('/src="([^"]*)"/', $tag, $matches);
		return html_entity_decode($matches[1]);
	}

	public function init() {
		// These should be 'provides' by bundle-dist.js
		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/ssui.core.js');

		// include TinyMCE Javascript
		Requirements::javascript($this->getScriptURL());
		Requirements::javascript(FRAMEWORK_DIR ."/client/dist/js/HtmlEditorField.js");

		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
	}
}
