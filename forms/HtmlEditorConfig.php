<?php

/**
 * A PHP version of TinyMCE's configuration, to allow various parameters to be configured on a site or section basis
 *
 * There can be multiple HtmlEditorConfig's, which should always be created / accessed using HtmlEditorConfig::get.
 * You can then set the currently active config using set_active. Whichever config is active when
 * HtmlEditorField#Field is called wins.
 *
 * @author "Hamish Friedlander" <hamish@silverstripe.com>
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorConfig {

	private static $configs = array();

	private static $current = null;

	/**
	 * Get the HtmlEditorConfig object for the given identifier. This is a correct way to get an HtmlEditorConfig
	 * instance - do not call 'new'
	 *
	 * @param $identifier string - the identifier for the config set
	 * @return HtmlEditorConfig - the configuration object. This will be created if it does not yet exist for that
	 *                            identifier
	 */
	public static function get($identifier = 'default') {
		if (!array_key_exists($identifier, self::$configs)) self::$configs[$identifier] = new HtmlEditorConfig();
		return self::$configs[$identifier];
	}

	/**
	 * Set the currently active configuration object
	 * @param $identifier string - the identifier for the config set
	 * @return null
	 */
	public static function set_active($identifier = null) {
		self::$current = $identifier;
	}

	/**
	 * Get the currently active configuration identifier
	 * @return String - the active configuration identifier
	 */
	public static function get_active_identifier() {
		$identifier = self::$current ? self::$current : 'default';
		return $identifier;
	}
	/**
	 * Get the currently active configuration object
	 * @return HtmlEditorConfig - the active configuration object
	 */
	public static function get_active() {
		$identifier = self::get_active_identifier();
		return self::get($identifier);
	}

	/**
	 * Get the available configurations as a map of friendly_name to
	 * configuration name.
	 * @return array
	 */
	public static function get_available_configs_map() {
		$configs = array();

		foreach(self::$configs as $identifier => $config) {
			$configs[$identifier] = $config->getOption('friendly_name');
		}

		return $configs;
	}

	/**
	 * Holder for all TinyMCE settings _except_ plugins and buttons
	 */
	protected $settings = array(
		'friendly_name' => '(Please set a friendly name for this config)',
		'priority' => 0,
		'mode' => "none", // initialized through HtmlEditorField.js redraw() logic
		'editor_selector' => "htmleditor",
		'width' => "100%",
		'auto_resize' => false,
		'update_interval' => 5000, // Ensure update of this data every 5 seconds to the underlying textarea
		'theme' => "advanced",

		'theme_advanced_layout_manager' => "SimpleLayout",
		'theme_advanced_toolbar_location' => "top",
		'theme_advanced_toolbar_align' => "left",
		'theme_advanced_toolbar_parent' => "right",

		'blockquote_clear_tag' => "p",
		'table_inline_editing' => true,

		'safari_warning' => false,
		'relative_urls' => true,
		'verify_html' => true,
		'browser_spellcheck' => true,
	);

	/**
	 * Holder list of enabled plugins
	 */
	protected $plugins = array(
		'contextmenu' => null,
		'table' => null,
		'emotions' => null,
		'paste' => null,
	);

	/**
	 * Holder list of buttons, organised by line
	 */
	protected $buttons = array(
		1 => array('bold','italic','underline','strikethrough','separator',
			'justifyleft','justifycenter','justifyright','justifyfull','formatselect','separator',
			'bullist','numlist','outdent','indent','blockquote','hr','charmap'),
		2 => array('undo','redo','separator','cut','copy','paste','pastetext','pasteword','separator',
			'advcode','search','replace','selectall','visualaid','separator','tablecontrols'),
		3 => array()
	);

	/**
	 * Get the current value of an option
	 * @param $k string - The key of the option to get
	 * @return mixed - The value of the specified option
	 */
	public function getOption($k) {
		if(isset($this->settings[$k])) return $this->settings[$k];
	}

	/**
	 * Set the value of one option
	 * @param $k string - The key of the option to set
	 * @param $v mixed - The value of the option to set
	 * @return HtmlEditorConfig
	 */
	public function setOption($k,$v) {
		$this->settings[$k] = $v;
		return $this;
	}

	/**
	 * Set multiple options
	 * @param $a array - The options to set, as keys and values of the array
	 * @return HtmlEditorConfig
	 */
	public function setOptions($a) {
		foreach ($a as $k=>$v) {
			$this->settings[$k] = $v;
		}
		return $this;
	}

	/**
	 * Enable one or several plugins. Will maintain unique list if already
	 * enabled plugin is re-passed. If passed in as a map of plugin-name to path,
	 * the plugin will be loaded by tinymce.PluginManager.load() instead of through tinyMCE.init().
	 * Keep in mind that these externals plugins require a dash-prefix in their name.
	 *
	 * @see http://wiki.moxiecode.com/index.php/TinyMCE:API/tinymce.PluginManager/load
	 *
	 * @param String [0..] a string, or several strings, or a single array of strings - The plugins to enable
	 * @return HtmlEditorConfig
	 */
	public function enablePlugins() {
		$plugins = func_get_args();
		if (is_array(current($plugins))) $plugins = current($plugins);
		foreach ($plugins as $plugin => $path) {
			// if plugins are passed without a path
			if(is_numeric($plugin)) {
				$plugin = $path;
				$path = null;
			}
			if (!array_key_exists($plugin, $this->plugins)) $this->plugins[$plugin] = $path;
		}
		return $this;
	}

	/**
	 * Enable one or several plugins. Will properly handle being passed a plugin that is already disabled
	 * @param String [0..] a string, or several strings, or a single array of strings - The plugins to disable
	 * @return HtmlEditorConfig
	 */
	public function disablePlugins() {
		$plugins = func_get_args();
		if (is_array(current($plugins))) $plugins = current($plugins);

		foreach ($plugins as $plugin) {
			if(array_key_exists($plugin, $this->plugins)) {
				unset($this->plugins[$plugin]);
			}
		}
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getPlugins() {
		return $this->plugins;
	}

	/**
	 * Totally re-set the buttons on a given line
	 *
	 * @param integer from 1..3 - The line number to redefine
	 * @param string  a string or several strings, or a single array of strings - The button names to make this line
	 *                contain
	 * @return HtmlEditorConfig
	 */
	public function setButtonsForLine() {
		if (func_num_args() == 2) {
			list($line, $buttons) = func_get_args();
		}
		else {
			$buttons = func_get_args();
			$line = array_shift($buttons);
		}
		$this->buttons[$line] = is_array($buttons) ? $buttons : array($buttons);
		return $this;
	}

	/**
	 * Add buttons to the end of a line
	 * @param integer from 1..3
	 * @param string a string, or several strings, or a single array of strings - The button names to add to the end
	 *               of this line
	 * @return HtmlEditorConfig
	 */
	public function addButtonsToLine() {
		$inserts = func_get_args();
		$line = array_shift($inserts);
		if (is_array($inserts[0])) $inserts = $inserts[0];

		foreach ($inserts as $button) {
			$this->buttons[$line][] = $button;
		}
		return $this;
	}

	/**
	 * Internal function for adding and removing buttons related to another button
	 * @param $name string - the name of the button to modify
	 * @param $offset integer - the offset relative to that button to perform an array_splice at - 0 for before $name,
	 *                          1 for after
	 * @param $del integer - the number of buttons to remove at the position given by index(string) + offset
	 * @param $add mixed - an array or single item to insert at the position given by index(string) + offset,
	 *                     or null for no insertion
	 * @return boolean - true if $name matched a button, false otherwise
	 */
	protected function modifyButtons($name, $offset, $del=0, $add=null) {
		foreach ($this->buttons as &$buttons) {
			if (($idx = array_search($name, $buttons)) !== false) {
				if ($add) array_splice($buttons, $idx+$offset, $del, $add);
				else     array_splice($buttons, $idx+$offset, $del, $add);
				return true;
			}
		}
		return false;
	}

	/**
	 * Insert buttons before the first occurance of another button
	 * @param string - the name of the button to insert other buttons before
	 * @param string a string, or several strings, or a single array of strings - the button names to insert before
	 *               that button
	 * @return boolean - true if insertion occured, false if it did not (because the given button name was not found)
	 */
	public function insertButtonsBefore() {
		$inserts = func_get_args();
		$before = array_shift($inserts);
		return $this->modifyButtons($before, 0, 0, $inserts);
	}

	/**
	 * Insert buttons after the first occurance of another button
	 * @param string - the name of the button to insert other buttons after
	 * @param string a string, or several strings, or a single array of strings - the button names to insert after
	 *               that button
	 * @return boolean - true if insertion occured, false if it did not (because the given button name was not found)
	 */
	public function insertButtonsAfter() {
		$inserts = func_get_args();
		$after = array_shift($inserts);
		return $this->modifyButtons($after, 1, 0, $inserts);
	}

	/**
	 * Remove the first occurance of buttons
	 * @param string one or more strings - the name of the buttons to remove
	 * @return null
	 */
	public function removeButtons() {
		$removes = func_get_args();
		foreach ($removes as $button) {
			$this->modifyButtons($button, 0, 1);
		}
	}

	/**
	 * Generate the JavaScript that will set TinyMCE's configuration:
	 * - Parse all configurations into JSON objects to be used in JavaScript
	 * - Includes TinyMCE and configurations using the {@link Requirements} system
	 */
	public static function require_js() {
		require_once 'tinymce/tiny_mce_gzip.php';
		$useGzip = Config::inst()->get('HtmlEditorField', 'use_gzip');

		$configs = array();
		$externalPlugins = array();
		$internalPlugins = array();
		$languages = array();

		foreach (self::$configs as $configID => $config) {
			$settings = $config->settings;
			// parse plugins
			$configPlugins = array();
			foreach($config->plugins as $plugin => $path) {
				if(!$path) {
					$configPlugins[] = $plugin;
					$internalPlugins[] = $plugin;
				} else {
					$configPlugins[] = '-' . $plugin;
					if ( !array_key_exists($plugin, $externalPlugins) )
					{
						$externalPlugins[$plugin] = sprintf(
							'tinymce.PluginManager.load("%s", "%s");',
							$plugin,
							$path
						);
					}
				}
			}

			// save config plugins settings
			$settings['plugins'] = implode(',', $configPlugins);

			// buttons
			foreach ($config->buttons as $i=>$buttons) {
				$settings['theme_advanced_buttons'.$i] = implode(',', $buttons);
			}

			// languages
			$languages[] = $config->getOption('language');

			// save this config settings
			$configs[$configID] = $settings;
		}

		// tinyMCE JS requirement
		if ( $useGzip )
		{
			$tag = TinyMCE_Compressor::renderTag(array(
				'url' => THIRDPARTY_DIR . '/tinymce/tiny_mce_gzip.php',
				'plugins' => implode(',', $internalPlugins),
				'themes' => 'advanced',
				'languages' => implode(",", array_filter($languages))
			), true);
			preg_match('/src="([^"]*)"/', $tag, $matches);
			Requirements::javascript(html_entity_decode($matches[1]));
		}
		else{
			Requirements::javascript(MCE_ROOT . 'tiny_mce_src.js');
		}

		// prepare external plugins js string
		$externalPlugins = array_values($externalPlugins);
		$externalPlugins = implode("\n	", $externalPlugins);

		// tinyMCE config object and external plugings
		$configsJS = "
if((typeof tinyMCE != 'undefined')) {
	$externalPlugins
	var ssTinyMceConfig = " . Convert::raw2json($configs) . ";
}";
		Requirements::customScript($configsJS, 'htmlEditorConfig');
	}
}
