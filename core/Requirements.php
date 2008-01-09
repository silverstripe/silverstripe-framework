<?php

/**
 * @package sapphire
 * @subpackage view
 */

/**
 * Requirements tracker, for javascript and css.
 * @todo Document the requirements tracker, and discuss it with the others.
 * @package sapphire
 * @subpackage view
 */
class Requirements {
	private static $javascript = array();
	private static $css = array();
	private static $customScript = array();
	private static $customCSS = array();
	private static $customHeadTags = "";
	private static $disabled = array();
	private static $blocked = array();
	
	/**
	 * Register the given javascript file as required.
	 * Filenames should be relative to the base, eg, 'sapphire/javascript/loader.js'
	 */
	static function javascript($file) {		
		Requirements::$javascript[$file] = true;	
	}
	
	/**
	 * Add the javascript code to the header of the page
	 * @todo Make Requirements automatically put this into a separate file :-)
	 * @param script The script content
	 * @param uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	static function customScript($script, $uniquenessID = null) {
		if($uniquenessID)
			Requirements::$customScript[$uniquenessID] = $script;
		else {
			Requirements::$customScript[] = $script;		
		}
		$script .= "\n";
	}

	/**
	 * Add the CSS styling to the header of the page
	 * @todo Make Requirements automatically put this into a separate file :-)
	 */
	static function customCSS($script, $uniquenessID = null) {
		if($uniquenessID)
			Requirements::$customCSS[$uniquenessID] = $script;
		else {
			Requirements::$customCSS[] = $script;		
		}
	}
	
	/**
	 * Add the following custom code to the <head> section of the page
	 */
	static function insertHeadTags($tags) {
		Requirements::$customHeadTags .= $tags . "\n";
	}
	 
	
	/**
	 * Load the given javascript template with the page.
	 * @param file The template file to load.
	 * @param vars The array of variables to load.  These variables are loaded via string search & replace.
	 */
	static function javascriptTemplate($file, $vars, $uniquenessID = null) {
		$script = file_get_contents(Director::getAbsFile($file));
		foreach($vars as $k => $v) {
			$search[] = '$' . $k;
			$replace[] = str_replace("\\'","'", Convert::raw2js($v));
		}
		$script = str_replace($search, $replace, $script);
		Requirements::customScript($script, $uniquenessID);
	}

	/**
	 * Register the given stylesheet file as required.
	 * 
	 * @param $file String Filenames should be relative to the base, eg, 'jsparty/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	static function css($file, $media = null) {
		Requirements::$css[$file] = array(
			"media" => $media
		);
	}
	
	/**
	 * Register the given "themeable stylesheet" as required.
	 * Themeable stylesheets have globally unique names, just like templates and PHP files.
	 * Because of this, they can be replaced by similarly named CSS files in the theme directory.
	 * 
	 * @param $name String The identifier of the file.  For example, css/MyFile.css would have the identifier "MyFile"
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 */
	static function themedCSS($name, $media = null) {
		global $_CSS_MANIFEST;
		
		$theme = SSViewer::current_theme();
		
		if($theme && isset($_CSS_MANIFEST[$name]) && isset($_CSS_MANIFEST[$name]['themes']) 
			&& isset($_CSS_MANIFEST[$name]['themes'][$theme])) 
			Requirements::css($_CSS_MANIFEST[$name]['themes'][$theme], $media);

		else if(isset($_CSS_MANIFEST[$name]) && isset($_CSS_MANIFEST[$name]['unthemed'])) Requirements::css($_CSS_MANIFEST[$name]['unthemed'], $media);
		// Normal requirements fails quietly when there is no css - we should do the same
		// else user_error("themedCSS - No CSS file '$name.css' found.", E_USER_WARNING);
	}
	
	/**
	 * Clear either a single or all requirements.
	 * Caution: Clearing single rules works only with customCSS and customScript if you specified a {@uniquenessID}. 
	 * 
	 * @param $file String
	 */
	static function clear($fileOrID = null) {
		if($fileOrID) {
			foreach(array('javascript','css', 'customScript', 'customCSS') as $type) {
				if(isset(Requirements::${$type}[$fileOrID])) {
					Requirements::$disabled[$type][$fileOrID] = Requirements::${$type}[$fileOrID];
					unset(Requirements::${$type}[$fileOrID]);
				}
			}
		} else {
			Requirements::$disabled['javascript'] = Requirements::$javascript;
			Requirements::$disabled['css'] = Requirements::$css;
			Requirements::$disabled['customScript'] = Requirements::$customScript;
			Requirements::$disabled['customCSS'] = Requirements::$customCSS;
		
			Requirements::$javascript = array();
			Requirements::$css = array();
			Requirements::$customScript = array();
			Requirements::$customCSS = array();
		}
	}
	
	/**
	 * Needed to actively prevent the inclusion of a file,
	 * e.g. when using your own prototype.js.
	 * Blocking should only be used as an exception, because
	 * it is hard to trace back. You can just block items with an
	 * ID, so make sure you add an unique identifier to customCSS() and customScript().
	 * 
	 * @param string $fileOrID
	 */
	static function block($fileOrID) {
		self::$blocked[$fileOrID] = $fileOrID;
	}

	/**
	 * Removes an item from the blocking-list.
	 * CAUTION: Does not "re-add" any previously blocked elements.
	 * @param string $fileOrID
	 */
	static function unblock($fileOrID) {
		unset(self::$blocked[$fileOrID]);
	}

	/**
	 * Removes all items from the blocking-list.
	 */
	static function unblock_all() {
		self::$blocked = array();
	}
	
	/**
	 * Restore requirements cleared by call to Requirements::clear
	 */
	static function restore() {
		Requirements::$javascript = Requirements::$disabled['javascript'];
		Requirements::$css = Requirements::$disabled['css'];
		Requirements::$customScript = Requirements::$disabled['customScript'];
		Requirements::$customCSS = Requirements::$disabled['customCSS'];
	}
	
	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements.
	 * @todo Calculate $prefix properly
	 */
	static function includeInHTML($templateFile, $content) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Requirements::includeInHTML");
		
		if(strpos($content, '</head') !== false && (Requirements::$javascript || Requirements::$css || Requirements::$customScript || Requirements::$customHeadTags)) {
			$prefix = "";
			$requirements = '';
	
			foreach(array_diff_key(self::$javascript,self::$blocked) as $file => $dummy) {
				if(substr($file,0,7) == 'http://' || Director::fileExists($file)) {
					$requirements .= "<script type=\"text/javascript\" src=\"$prefix$file\"></script>\n";
				}
			}
			
			if(self::$customScript) {
				$requirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
				foreach(array_diff_key(self::$customScript,self::$blocked) as $script) {
					$requirements .= "$script\n";
				}
				$requirements .= "\n//]]>\n</script>\n";
			}
			foreach(array_diff_key(self::$css,self::$blocked) as $file => $params) {					
				if(Director::fileExists($file)) {
					$media = (isset($params['media'])) ? " media=\"{$params['media']}\"" : "";
					$requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"$prefix$file\" />\n";
				}
			}
			foreach(array_diff_key(self::$customCSS,self::$blocked) as $css) {
				$requirements .= "<style type=\"text/css\">\n$css\n</style>\n";
			}
			
			$requirements .= self::$customHeadTags;
	
			if(isset($_GET['debug_profile'])) Profiler::unmark("Requirements::includeInHTML");
			return eregi_replace("(</head[^>]*>)", $requirements . "\\1", $content);
			
		} else {
			if(isset($_GET['debug_profile'])) Profiler::unmark("Requirements::includeInHTML");
			return $content;
		}
	}
	
	static function get_custom_scripts() {
		$requirements = "";
		
		if(Requirements::$customScript) {
			foreach(Requirements::$customScript as $script) {
				$requirements .= "$script\n";
			}
		}
		
		return $requirements;
	}
	
	static function debug() {
		Debug::show(Requirements::$javascript);
		Debug::show(Requirements::$css);
		Debug::show(Requirements::$customCSS);
		Debug::show(Requirements::$customScript);
		Debug::show(Requirements::$customHeadTags);
	}
}


?>