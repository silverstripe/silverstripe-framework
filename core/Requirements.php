<?php
/**
 * Requirements tracker, for javascript and css.
 * @todo Document the requirements tracker, and discuss it with the others.
 * @package sapphire
 * @subpackage view
 */
class Requirements {

	/**
	 * Paths to all required .js files relative to the webroot.
	 * 
	 * @var array $javascript
	 */
	protected static $javascript = array();

	/**
	 * Paths to all required .css files relative to the webroot.
	 * 
	 * @var array $css
	 */
	protected static $css = array();

	/**
	 * All custom javascript code that is inserted
	 * directly at the bottom of the HTML <head> tag.
	 *
	 * @var array $customScript
	 */
	protected static $customScript = array();

	/**
	 * All custom CSS rules which are inserted
	 * directly at the bottom of the HTML <head> tag. 
	 *
	 * @var array $customCSS
	 */
	protected static $customCSS = array();

	/**
	 * All custom HTML markup which is added before
	 * the closing <head> tag, e.g. additional metatags.
	 * This is preferred to entering tags directly into 
	 */
	protected static $customHeadTags = array();

	/**
	 * Remembers the filepaths of all cleared Requirements
	 * through {@link clear()}.
	 * 
	 * @usedby {@link restore()}
	 *
	 * @var array $disabled
	 */
	protected static $disabled = array();

	/**
	 * The filepaths (relative to webroot) or
	 * uniquenessIDs of any included requirements
	 * which should be blocked when executing {@link inlcudeInHTML()}.
	 * This is useful to e.g. prevent core classes to modifying
	 * Requirements without subclassing the entire functionality.
	 * Use {@link unblock()} or {@link unblock_all()} to revert changes.
	 * 
	 * @var array $blocked
	 */
	protected static $blocked = array();
	
	/**
	 * See {@link combine_files()}.
	 * 
	 * @var array $combine_files
	 */
	public static $combine_files = array();
	
	/**
	 * Using the JSMin library to minify any
	 * javascript file passed to {@link combine_files()}.
	 *
	 * @var boolean
	 */
	public static $combine_js_with_jsmin = true;
	
	/**
	 * Put all javascript includes at the bottom of the template
	 * before the closing <body> tag instead of the <head> tag.
	 * This means script downloads won't block other HTTP-requests,
	 * which can be a performance improvement.
	 * Caution: Doesn't work when modifying the DOM from those external
	 * scripts without listening to window.onload/document.ready
	 * (e.g. toplevel document.write() calls).
	 *
	 * @see http://developer.yahoo.com/performance/rules.html#js_bottom
	 *
	 * @var boolean
	 */
	public static $write_js_to_body = false;
	
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
	 * Add the following custom code to the <head> section of the page.
	 *
	 * @param string $html
	 * @param string $uniquenessID
	 */
	static function insertHeadTags($html, $uniquenessID = null) {
		if($uniquenessID)
			Requirements::$customHeadTags[$uniquenessID] = $html;
		else {
			Requirements::$customHeadTags[] = $html;		
		}
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
			Requirements::$customHeadTags = array();
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
		if(isset(self::$blocked[$fileOrID])) unset(self::$blocked[$fileOrID]);
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
	static function includeInHTML($templateFile, $content) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Requirements::includeInHTML");
		
		if(strpos($content, '</head') !== false && (Requirements::$javascript || Requirements::$css || Requirements::$customScript || Requirements::$customHeadTags)) {
			$requirements = '';
			$jsRequirements = '';
			
			// Combine files - updates Requirements::$javascript and Requirements::$css 
 			self::process_combined_files(); 

			// 
			self::process_i18n_javascript(); 
	
			foreach(array_diff_key(self::$javascript,self::$blocked) as $file => $dummy) { 
				$path = self::path_for_file($file);
				if($path) {
					$jsRequirements .= "<script type=\"text/javascript\" src=\"$path\"></script>\n";
				}
			}
			
			// add all inline javascript *after* including external files which
			// they might rely on
			if(self::$customScript) {
				foreach(array_diff_key(self::$customScript,self::$blocked) as $script) { 
					$jsRequirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
					$jsRequirements .= "$script\n";
					$jsRequirements .= "\n//]]>\n</script>\n";
				}
			}
			
			foreach(array_diff_key(self::$css,self::$blocked) as $file => $params) {  					
				$path = self::path_for_file($file);
				if($path) {
					$media = (isset($params['media']) && !empty($params['media'])) ? " media=\"{$params['media']}\"" : "";
					$requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"$path\" />\n";
				}
			}
			foreach(array_diff_key(self::$customCSS,self::$blocked) as $css) { 
				$requirements .= "<style type=\"text/css\">\n$css\n</style>\n";
			}
			
			foreach(array_diff_key(self::$customHeadTags,self::$blocked) as $customHeadTag) { 
				$requirements .= "$customHeadTag\n"; 
			}
	
			if(self::$write_js_to_body) {
				// Remove all newlines from code to preserve layout
				$jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);
				
				// We put script tags into the body, for performance.
				// If your template already has script tags in the body, then we put our script tags at the top of the body.
				// Otherwise, we put it at the bottom.
				$p1 = strripos($content, '<script');
				$p2 = stripos($content, '<body');
				if($p1 !== false && $p1 > $p2) {
					user_error("You have a script tag in the body, moving requirements to top of <body> for compatibilty.  I recommend removing the script tag from your template's body.", E_USER_NOTICE);
					$content = eregi_replace("(<body[^>]*>)", "\\1" . $jsRequirements, $content);
				} else {
					$content = eregi_replace("(</body[^>]*>)", $jsRequirements . "\\1", $content);
				}
				
				// Put CSS at the bottom of the head			
				$content = eregi_replace("(</head[^>]*>)", $requirements . "\\1", $content);
			} else {
				$content = eregi_replace("(</head[^>]*>)", $requirements . "\\1", $content);
				$content = eregi_replace("(</head[^>]*>)", $jsRequirements . "\\1", $content);
			}
		} 
		
		if(isset($_GET['debug_profile'])) Profiler::unmark("Requirements::includeInHTML");
		
		return $content;
	}
	
	/**
	 * Automatically includes the necessary lang-files from the module
	 * according to the locale set in {@link i18n::$current_locale}.
	 * Assumes that a subfolder /javascript exists relative to the included
	 * javascript file, with a file named after the locale - 
	 * so usually <mymodule>/javascript/lang/en_US.js.
	 */
	protected static function process_i18n_javascript() {
		// ensure to include the i18n base library
		if(
			count(array_diff_key(self::$javascript,self::$blocked)) 
			&& !isset(self::$javascript[SAPPHIRE_DIR . '/javascript/i18n.js'])
		) {
			self::$javascript[THIRDPARTY_DIR . '/prototype.js'] = true;
			self::$javascript[SAPPHIRE_DIR . '/javascript/i18n.js'] = true;
		}
		
		// include the specific locale and the master locale for each module
		foreach(array_diff_key(self::$javascript,self::$blocked) as $file => $dummy) { 
			if(preg_match('/^http[s]?/', $file)) continue;
			
			$absolutePath = Director::baseFolder() . '/' . $file;
			$absoluteLangPath = dirname($absolutePath) . '/lang/' . i18n::get_locale() . '.js';
			$absoluteDefaultLangPath = dirname($absolutePath) . '/lang/' . i18n::default_locale() . '.js';
			foreach(array($absoluteDefaultLangPath, $absoluteLangPath) as $path) {
				if(Director::fileExists($path)) {
					$langFile = Director::makeRelative($path);
					// Remove rogue leading slashes from Director::makeRelative()
					$langFile = preg_replace('/^\//', '', $langFile);
					self::$javascript[$langFile] = true;
				}	
			}
		}

	}
	
	/**
	 * 
	 *
	 * @param string $fileOrUrl
	 * @return string|boolean 
	 */
	protected static function path_for_file($fileOrUrl) {
		if(preg_match('/^http[s]?/', $fileOrUrl)) {
			return $fileOrUrl;
		} elseif(Director::fileExists($fileOrUrl)) {
			$prefix = Director::absoluteBaseURL();
			$mtimesuffix = "?m=" . filemtime(Director::baseFolder() . '/' . $fileOrUrl);
			return "{$prefix}{$fileOrUrl}{$mtimesuffix}";
		} else {
			return false;
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
	 * Best practice is to include every javascript file in exactly *one* combine_files()
	 * directive to avoid the issues mentioned above - this is enforced by this function.
	 * 
	 * CAUTION: Combining CSS Files discards any "media" information.
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
	 * @todo Should we enforce unique inclusion of files, or leave it to the developer? Can auto-detection cause breaks?
	 * 
	 * @param string $combinedFileName Filename of the combined file (will be stored in {@link Director::baseFolder()} by default)
	 * @param array $files Array of filenames relative to the webroot
	 */
	static function combine_files($combinedFileName, $files) {
		// duplicate check
		foreach(self::$combine_files as $_combinedFileName => $_files) {
			$duplicates = array_intersect($_files, $files);
			if($duplicates) {
				user_error("Requirements::combine_files(): Already included files " . implode(',', $duplicates) . " in combined file '{$_combinedFileName}'", E_USER_NOTICE);
				return false;
			}
		}
		
		self::$combine_files[$combinedFileName] = $files;
	}
	
	/**
	 * @return array
	 */
	static function get_combine_files() {
		return self::$combine_files;
	}
	
	/**
	 * Deletes all dynamically generated combined files from the filesystem. 
	 * 
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	static function delete_combined_files($combinedFileName = null) {
		$combinedFiles = ($combinedFileName) ? array($combinedFileName => null) : self::$combine_files;
		foreach($combinedFiles as $combinedFile => $sourceItems) {
			$filePath = Director::baseFolder() . '/' . $combinedFile;
			if(file_exists($filePath)) {
				unlink($filePath);
			}
		}
	}
	
	/**
	 * Re-sets the combined files definition
	 */
	static function clear_combined_files() {
		self::$combine_files = array();
	}
	
	/**
	 * See {@link combine_files()}.
 	 */
	static function process_combined_files() {
		// Make a map of files that could be potentially combined
		$combinerCheck = array();
		foreach(self::$combine_files as $combinedFile => $sourceItems) {
			foreach($sourceItems as $sourceItem) {
				if(isset($combinerCheck[$sourceItem]) && $combinerCheck[$sourceItem] != $combinedFile){ 
					user_error("Requirements::process_combined_files - file '$sourceItem' appears in two combined files:" .	" '{$combinerCheck[$sourceItem]}' and '$combinedFile'", E_USER_WARNING);
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
      
		// @todo Alters the original information, which means you can't call this
		// method repeatedly - it will behave different on the second call!
		Requirements::$javascript = $newJSRequirements;
		Requirements::$css = $newCSSRequirements;

		// Process the combined files
		$base = Director::baseFolder() . '/';
		foreach(array_diff_key($combinedFiles,self::$blocked) as $combinedFile => $dummy) {
			$fileList = self::$combine_files[$combinedFile];

			 // Determine if we need to build the combined include
			if(file_exists($base . $combinedFile) && !isset($_GET['flush'])) {
				// file exists, check modification date of every contained file
				$srcLastMod = 0;
				foreach($fileList as $file) {
					$srcLastMod = max(filemtime($base . $file), $srcLastMod);
				}
				$refresh = $srcLastMod > filemtime($base . $combinedFile);
			} else {
				// file doesn't exist, or refresh was explicitly required
				$refresh = true;
			}

			if(!$refresh) continue;

			$combinedData = "";
			foreach(array_diff($fileList,self::$blocked) as $file) {
				$fileContent = file_get_contents($base . $file);
				// if we have a javascript file and jsmin is enabled, minify the content
				if(stripos($file, '.js') && self::$combine_js_with_jsmin) {
					require_once('thirdparty/jsmin/JSMin.php');
					$fileContent = JSMin::minify($fileContent);
				}
				// write a header comment for each file for easier identification and debugging
				$combinedData .= "/****** FILE: $file *****/\n" . $fileContent . "\n";
			}
			if(!file_exists(dirname($base . $combinedFile))) {
				Filesytem::makeFolder(dirname($base . $combinedFile));
			}
			$fh = fopen($base . $combinedFile, 'w');
			fwrite($fh, $combinedData);
			fclose($fh);
			unset($fh);
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
		Debug::show(Requirements::$combine_files);
	}
}


?>