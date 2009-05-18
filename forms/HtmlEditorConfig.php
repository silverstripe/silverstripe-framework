<?php 

/**
 * A PHP version of TinyMCE's configuration, to allow various parameters to be configured on a site or section basis
 * 
 * There can be multiple HtmlEditorConfig's, which should always be created / accessed using HtmlEditorConfig::get. You can then set 
 * the currently active config using set_active. Whichever config is active when HtmlEditorField#Field is called wins.
 *  
 * @author "Hamish Friedlander" <hamish@silverstripe.com>
 */
class HtmlEditorConfig {

	static $configs = array();
	static $current = null;
	
	/**
	 * Get the HtmlEditorConfig object for the given identifier. This is a correct way to get an HtmlEditorConfig instance - do not call 'new'
	 * @param $identifier string - the identifier for the config set
	 * @return HtmlEditorConfig - the configuration object. This will be created if it does not yet exist for that identifier
	 */
	static function get($identifier = 'default') {
		if (!array_key_exists($identifier, self::$configs)) self::$configs[$identifier] = new HtmlEditorConfig();
		return self::$configs[$identifier];
	}
	
	/**
	 * Set the currently active configuration object
	 * @param $identifier string - the identifier for the config set
	 * @return null
	 */
	static function set_active($identifier = null) {
		self::$current = $identifier;
	}
	
	/**
	 * Get the currently active configuration object
	 * @return HtmlEditorConfig - the active configuration object
	 */
	static function get_active() {
		$identifier = self::$current ? self::$current : 'default';
		return self::get($identifier);
	}
	
	/**
	 * Holder for all TinyMCE settings _except_ plugins and buttons
	 */
	protected $settings = array(
		'mode' => "specific_textareas",
		'editor_selector' => "htmleditor",
		'width' => "100%",
		'auto_resize' => false,
		'theme' => "advanced",

		'theme_advanced_layout_manager' => "SimpleLayout",
		'theme_advanced_toolbar_location' => "top",
		'theme_advanced_toolbar_align' => "left",
		'theme_advanced_toolbar_parent' => "right",
		
		'blockquote_clear_tag' => "p",
		'table_inline_editing' => true,

		'safari_warning' => false,
		'relative_urls' => true,
		'verify_html' => true
	);
	
	/**
	 * Holder list of enabled plugins
	 */
	protected $plugins = array(
		'blockquote', 'contextmenu', 'table', 'emotions', 'paste', '../../tinymce_advcode', 'spellchecker'
	);

	/**
	 * Holder list of buttons, organised by line
	 */
	protected $buttons = array(
		1 => array('bold','italic','underline','strikethrough','separator','justifyleft','justifycenter','justifyright','justifyfull','formatselect','separator','bullist','numlist','outdent','indent','blockquote','hr','charmap'),
		2 => array('undo','redo','separator','cut','copy','paste','pastetext','pasteword','spellchecker','separator','advcode','search','replace','selectall','visualaid','separator','tablecontrols'),
		3 => array()
	);

	/**
	 * Get the current value of an option
	 * @param $k string - The key of the option to get
	 * @return mixed - The value of the specified option 
	 */
	function getOption($k) {
		return $this->settings[$k];
	}
	
	/**
	 * Set the value of one option
	 * @param $k string - The key of the option to set
	 * @param $v mixed - The value of the option to set
	 * @return mixed - $v returned for chaining
	 */
	function setOption($k,$v) {
		return $this->settings[$k] = $v;
	}
	
	/**
	 * Set multiple options
	 * @param $a array - The options to set, as keys and values of the array
	 * @return null
	 */
	function setOptions($a) {
		foreach ($a as $k=>$v) {
			$this->settings[$k] = $v;
		}
	}
	
	/**
	 * Enable one or several plugins. Will maintain unique list if already enabled plugin is re-passed
	 * @param[0..] a string, or several strings, or a single array of strings - The plugins to enable
	 * @return null
	 */
	function enablePlugins() {
		$plugins = func_get_args();
		if (is_array($plugins[0])) $plugins = $plugins[0];
		
		foreach ($plugins as $plugin) {
			if (!in_array($plugin, $this->plugins)) $this->plugins[] = $plugin;
		}
	}

	/**
	 * Enable one or several plugins. Will properly handle being passed a plugin that is already disabled
	 * @param[0..] a string, or several strings, or a single array of strings - The plugins to disable
	 * @return null
	 */
	function disablePlugins() {
		$plugins = func_get_args();
		if (is_array($plugins[0])) $plugins = $plugins[0];
		
		foreach ($plugins as $plugin) {
			if (($idx = array_search($plugin, $this->plugins)) !== false) {
				array_splice($this->plugins, $idx, 1);
				continue;	
			}
		}
	}
	
	/**
	 * Totally re-set the buttons on a given line
	 * @param[0] integer from 1..3 - The line number to redefine
	 * @param[1..] a string or several strings, or a single array of strings - The button names to make this line contain 
	 * @return null
	 */
	function setButtonsForLine() {
		if (func_num_args() == 2) {
			list($line, $buttons) = func_get_args();
		}
		else {
			$buttons = func_get_args();
			$line = array_shift($buttons);
		}
		$this->buttons[$line] = is_array($buttons) ? $buttons : array($buttons);
	}
	
	/**
	 * Add buttons to the end of a line
	 * @param[0] integer from 1..3
	 * @param[1..] a string, or several strings, or a single array of strings - The button names to add to the end of this line 
	 * @return null
	 */
	function addButtonsToLine() {
		$inserts = func_get_args();
		$line = array_shift($inserts);
		if (is_array($inserts[0])) $inserts = $inserts[0];
		
		foreach ($inserts as $button) {
			$this->buttons[$line][] = $button;
		}
	}
	
	/**
	 * Internal function for adding and removing buttons related to another button
	 * @param $name string - the name of the button to modify
	 * @param $offset integer - the offset relative to that button to perform an array_splice at - 0 for before $name, 1 for after 
	 * @param $del integer - the number of buttons to remove at the position given by index(string) + offset
	 * @param $add mixed - an array or single item to insert at the position given by index(string) + offset, or null for no insertion
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
	 * @param[0] string - the name of the button to insert other buttons before
	 * @param[1..] a string, or several strings, or a single array of strings - the button names to insert before that button 
	 * @return boolean - true if insertion occured, false if it did not (because the given button name was not found)
	 */
	function insertButtonsBefore() {
		$inserts = func_get_args();
		$before = array_shift($inserts);
		return $this->modifyButtons($before, 0, 0, $inserts);
	}
	
	/**
	 * Insert buttons after the first occurance of another button
	 * @param[0] string - the name of the button to insert other buttons after
	 * @param[1..] a string, or several strings, or a single array of strings - the button names to insert after that button 
	 * @return boolean - true if insertion occured, false if it did not (because the given button name was not found)
	 */
	function insertButtonsAfter() {
		$inserts = func_get_args();
		$after = array_shift($inserts);
		return $this->modifyButtons($after, 1, 0, $inserts);
	}
	
	/**
	 * Remove the first occurance of buttons
	 * @param[0..] string - the name of the buttons to remove
	 * @return null
	 */
	function removeButtons() {
		$removes = func_get_args();
		foreach ($removes as $button) {
			$this->modifyButtons($button, 0, 1);
		}
	}
	
	/**
	 * Generate the javascript that will set tinyMCE's configuration to that of the current settings of this object
	 * @return string - the javascript
	 */
	function generateJS() {
		$config = $this->settings;
		$config['plugins'] = implode(',', $this->plugins);
		
		foreach ($this->buttons as $i=>$buttons) {
			$config['theme_advanced_buttons'.$i] = implode(',', $buttons);
		}
		
		return "
if((typeof tinyMCE != 'undefined')) {
	tinyMCE.init(" . Convert::raw2json($config) . ");
}
";
	}
}
