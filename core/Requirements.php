<?php
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
	 * See {@link combine_files()}.
	 * 
	 * @var array $files_to_combine
	 */
	public static $files_to_combine = array();
	
	/**
	 * Using the JSMin library to minify any
	 * javascript file passed to {@link combine_files()}.
	 *
	 * @var boolean
	 */
	public static $combine_js_with_jsmin = true;
	
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
	 * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
	 * including a <head> tag. The requirements will insert before the closing <head> tag automatically.
	 *
	 * @todo Calculate $prefix properly
	 * 
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	static function includeInHTML($templateFilePath, $content) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Requirements::includeInHTML");
		
		if(strpos($content, '</head') !== false && (Requirements::$javascript || Requirements::$css || Requirements::$customScript || Requirements::$customHeadTags)) {
			$prefix = Director::absoluteBaseURL();
			$requirements = '';
			$jsRequirements = '';
			
			// Combine files - updates Requirements::$javascript and Requirements::$css
			self::process_combined_includes();
			
			foreach(array_diff_key(self::$javascript,self::$blocked) as $file => $dummy) {
				if(substr($file,0,7) == 'http://' || Director::fileExists($file)) {
					$requirements .= "<script type=\"text/javascript\" src=\"$prefix$file\"></script>\n";
				}
			}
			
			if(self::$customScript) {
				foreach(array_diff_key(self::$customScript,self::$blocked) as $script) {
					$requirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
					$requirements .= "$script\n";
					$requirements .= "\n//]]>\n</script>\n";
				}
			}
			
			$jsRequirements=$requirements;
			
			foreach(array_diff_key(self::$css,self::$blocked) as $file => $params) {					
				if(Director::fileExists($file)) {
					$media = (isset($params['media']) && !empty($params['media'])) ? " media=\"{$params['media']}\"" : "";
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
	
	/**
	 * Concatenate several css or javascript files into a single dynamically generated
	 * file (stored in {@link Director::baseFolder()}). This increases performance
	 * by fewer HTTP requests.
	 * 
	 * The combined file is regenerated
	 * based on every file modification time. Optionally a rebuild can be triggered
	 * by appending ?flush=1 to the URL.
	 * If all files to be combined are javascript, we use the external JSMin library
	 * to minify the javascript. This can be controlled by {@link $combine_js_with_jsmin}.
	 * 
	 * All combined files will have a comment on the start of each concatenated file
	 * denoting their original position. For easier debugging, we recommend to only
	 * minify javascript if not in development mode ({@link Director::isDev()}).
	 * 
	 * CAUTION: You're responsible for ensuring that the load order for combined files
	 * is retained - otherwise combining javascript files can lead to functional errors
	 * in the javascript logic, and combining css can lead to wrong styling inheritance.
	 * Depending on the javascript logic, you also have to ensure that files are not included
	 * in more than one combine_files() call.
	 *
	 * Example for combined JavaScript:
	 * <code>
	 * Requirements::combine_files(
	 *  'foobar.js',
	 *  array(
	 * 		'mysite/javascript/foo.js',
	 * 		'mysite/javascript/bar.js',
	 * 	)
	 * );
	 * </code>
	 *
	 * Example for combined CSS:
	 * <code>
	 * Requirements::combine_files(
	 *  'foobar.css',
	 * 	array(
	 * 		'mysite/javascript/foo.css',
	 * 		'mysite/javascript/bar.css',
	 * 	)
	 * );
	 * </code>
	 *
	 * @see http://code.google.com/p/jsmin-php/
	 *
	 * @param string $combinedFileName Filename of the combined file (will be stored in {@link Director::baseFolder()} by default)
	 * @param array $files Array of filenames relative to the webroot
	 */
	static function combine_files($combinedFileName, $files){
		self::$files_to_combine[$combinedFileName] = $files;
	}
	
	/**
	 * See {@link combine_files()}.
 	 */
	static function process_combined_includes() {
		// Make a map of files that could be potentially combined
		$combinerCheck = array();
		foreach(self::$files_to_combine as $combinedFile => $sourceItems) {
			foreach($sourceItems as $sourceItem) {
				if(isset($combinerCheck[$sourceItem]) && $combinerCheck[$sourceItem] != $combinedFile){ 
					user_error("Requirements::process_combined_includes - file '$sourceItem' appears in two combined files:" .	" '{$combinerCheck[$sourceItem]}' and '$combinedFile'", E_USER_WARNING);
				}
				$combinerCheck[$sourceItem] = $combinedFile;
				
			}
		}
		
		// Figure out which ones apply to this pageview
		$combinedFiles = array();
		$newJSRequirements = array();
		$newCSSRequirements = array();
		foreach(Requirements::$javascript as $file => $dummy) {
			if(isset($combinerCheck[$file])) {
				$newJSRequirements[$combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newJSRequirements[$file] = true;
			}
		}
       
		foreach(Requirements::$css as $file => $params) {
			if(isset($combinerCheck[$file])) {
				$newCSSRequirements[$combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newCSSRequirements[$file] = $params;
			}
		}
      
		Requirements::$javascript = $newJSRequirements;
		Requirements::$css = $newCSSRequirements;

		// Process the combined files
		if($combinedFiles) {
			$base = Director::baseFolder() . '/';
			foreach($combinedFiles as $combinedFile => $dummy) {
				$fileList = self::$files_to_combine[$combinedFile];

				 // Determine if we need to build the combined include
				if(file_exists($base . $combinedFile) && !isset($_GET['flush'])) {
					$srcLastMod = 0;
					foreach($fileList as $file) {
						$srcLastMod = max(filemtime($base . $file), $srcLastMod);
					}
					$refresh = $srcLastMod > filemtime($base . $combinedFile);
				} else {
					$refresh = true;
				}

				// Rebuild, if necessary
				if($refresh) {
					$combinedData = "";
					foreach($fileList as $file) {
						$fileContent = file_get_contents($base . $file);
						if(stripos($file, '.js') && self::$combine_js_with_jsmin) {
							$fileContent = JSMin::minify($fileContent);
						}
						$combinedData .= "/****** FILE: $file *****/\n" . $fileContent . "\n";
					}
					if(!file_exists(dirname($base . $combinedFile))) 
						mkdir(dirname($base . $combinedFile), Filesystem::$folder_create_mask, true);
						
					$fh = fopen($base . $combinedFile, 'w');
					fwrite($fh, $combinedData);
					fclose($fh);
				}
			}
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