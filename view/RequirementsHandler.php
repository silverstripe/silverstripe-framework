<?php
/**
 * Requirements handler for JavaScript and CSS files - does the actual
 * work of calculating what to include and where.
 * @package framework
 * @subpackage view
 */
class RequirementsHandler implements Requirements_Backend {

	/**
	 * Paths to all required .js files relative to the webroot.
	 *
	 * @var array $javascript
	 */
	protected $javascript = array();

	/**
	 * Paths to all required .css files relative to the webroot.
	 *
	 * @var array $css
	 */
	protected $css = array();

	/**
	 * All custom javascript code that is inserted
	 * directly at the bottom of the HTML <head> tag.
	 *
	 * @var array $customScript
	 */
	protected $customScript = array();

	/**
	 * All custom CSS rules which are inserted
	 * directly at the bottom of the HTML <head> tag.
	 *
	 * @var array $customCSS
	 */
	protected $customCSS = array();

	/**
	 * All custom HTML markup which is added before
	 * the closing <head> tag, e.g. additional metatags.
	 * This is preferred to entering tags directly into
	 */
	protected $customHeadTags = array();

	/**
	 * Remembers the filepaths of all cleared Requirements
	 * through {@link clear()}.
	 *
	 * @var array $disabled
	 */
	protected $disabled = array();

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
	protected $blocked = array();

	/**
	 * See {@link combine_files()}.
	 *
	 * @var array $combine_files
	 */
	public $combine_files = array();

	/**
	 * Magic setter used for deprecating previously public properties
	 */
	public function __set($name, $value) {
		if($name == 'write_header_comment' || $name == 'combine_js_with_jsmin') {
			Deprecation::notice('4.0', "Use the Requirements.{$name} config setting instead", false);
			Config::inst()->update("Requirements", $name, $value);
		}
	}

	/**
	 * Get the folder that combined files will be stored in. The default Config
	 * value for Requirements.combined_files_folder includes a placeholder which
	 * this method will replace
	 * @return string
	 */
	public function getCombinedFilesFolder() {
		$folder = Config::inst()->get('Requirements', 'combined_files_folder');
		return str_replace('$AssetsDir', ASSETS_DIR, $folder);
	}

	/**
	 * Register the given javascript file as required.
	 * Filenames should be relative to the base, eg, 'framework/javascript/loader.js'
	 */
	public function javascript($file) {
		$this->javascript[$file] = true;
	}

	/**
	 * Returns an array of all included javascript
	 * @todo Deprecate this method?
	 * @return array
	 */
	public function get_javascript() {
		return array_keys(array_diff_key($this->javascript,$this->blocked));
	}

	/**
	 * Add the javascript code to the header of the page
	 * @todo Make Requirements automatically put this into a separate file :-)
	 * @param script The script content
	 * @param uniquenessID Use this to ensure that pieces of code only get added once.
	 */
	public function customScript($script, $uniquenessID = null) {
		if($uniquenessID) $this->customScript[$uniquenessID] = $script;
		else $this->customScript[] = $script;

		$script .= "\n";
	}

	/**
	 * Include custom CSS styling to the header of the page.
	 *
	 * @param string $script CSS selectors as a string (without <style> tag enclosing selectors).
	 * @param int $uniquenessID Group CSS by a unique ID as to avoid duplicate custom CSS in header
	 */
	public function customCSS($script, $uniquenessID = null) {
		if($uniquenessID) $this->customCSS[$uniquenessID] = $script;
		else $this->customCSS[] = $script;
	}

	/**
	 * Add the following custom code to the <head> section of the page.
	 *
	 * @param string $html
	 * @param string $uniquenessID
	 */
	public function insertHeadTags($html, $uniquenessID = null) {
		if($uniquenessID) $this->customHeadTags[$uniquenessID] = $html;
		else $this->customHeadTags[] = $html;
	}

	/**
	 * Load the given javascript template with the page.
	 * @param file The template file to load.
	 * @param vars The array of variables to load.  These variables are loaded via string search & replace.
	 */
	public function javascriptTemplate($file, $vars, $uniquenessID = null) {
		$script = file_get_contents(Director::getAbsFile($file));
		$search = array();
		$replace = array();

		if($vars) foreach($vars as $k => $v) {
			$search[] = '$' . $k;
			$replace[] = str_replace("\\'","'", Convert::raw2js($v));
		}

		$script = str_replace($search, $replace, $script);
		$this->customScript($script, $uniquenessID);
	}

	/**
	 * Register the given stylesheet file as required.
	 *
	 * @param $file String Filenames should be relative to the base, eg, 'framework/javascript/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public function css($file, $media = null) {
		$this->css[$file] = array(
			"media" => $media
		);
	}

	/**
	 * @todo Deprecate this method?
	 */
	public function get_css() {
		return array_diff_key($this->css, $this->blocked);
	}

	/**
	 * Needed to actively prevent the inclusion of a file,
	 * e.g. when using your own jQuery version.
	 * Blocking should only be used as an exception, because
	 * it is hard to trace back. You can just block items with an
	 * ID, so make sure you add an unique identifier to customCSS() and customScript().
	 *
	 * @param string $fileOrID
	 */
	public function block($fileOrID) {
		$this->blocked[$fileOrID] = $fileOrID;
	}

	/**
	 * Clear either a single or all requirements.
	 * Caution: Clearing single rules works only with customCSS and customScript if you specified a {@uniquenessID}.
	 *
	 * @param $file String
	 */
	public function clear($fileOrID = null) {
		if($fileOrID) {
			foreach(array('javascript','css', 'customScript', 'customCSS', 'customHeadTags') as $type) {
				if(isset($this->{$type}[$fileOrID])) {
					$this->disabled[$type][$fileOrID] = $this->{$type}[$fileOrID];
					unset($this->{$type}[$fileOrID]);
				}
			}
		} else {
			$this->disabled['javascript'] = $this->javascript;
			$this->disabled['css'] = $this->css;
			$this->disabled['customScript'] = $this->customScript;
			$this->disabled['customCSS'] = $this->customCSS;
			$this->disabled['customHeadTags'] = $this->customHeadTags;

			$this->javascript = array();
			$this->css = array();
			$this->customScript = array();
			$this->customCSS = array();
			$this->customHeadTags = array();
		}
	}

	/**
	 * Removes an item from the blocking-list.
	 * CAUTION: Does not "re-add" any previously blocked elements.
	 * @param string $fileOrID
	 */
	public function unblock($fileOrID) {
		if(isset($this->blocked[$fileOrID])) unset($this->blocked[$fileOrID]);
	}

	/**
	 * Removes all items from the blocking-list.
	 */
	public function unblockAll() {
		$this->blocked = array();
	}

	/**
	 * Restore requirements cleared by call to Requirements::clear
	 */
	public function restore() {
		$this->javascript = $this->disabled['javascript'];
		$this->css = $this->disabled['css'];
		$this->customScript = $this->disabled['customScript'];
		$this->customCSS = $this->disabled['customCSS'];
		$this->customHeadTags = $this->disabled['customHeadTags'];
	}

	/**
	 * Update the given HTML content with the appropriate include tags for the registered
	 * requirements. Needs to receive a valid HTML/XHTML template in the $content parameter,
	 * including a <head> tag. The requirements will insert before the closing <head> tag automatically.
	 *
	 * @todo Calculate $prefix properly
	 *
	 * @param string $templateFilePath Absolute path for the *.ss template file
	 * @param string $content HTML content that has already been parsed from the $templateFilePath
	 *                        through {@link SSViewer}.
	 * @return string HTML content thats augumented with the requirements before the closing <head> tag.
	 */
	public function includeInHTML($templateFile, $content) {
		if(
			(strpos($content, '</head>') !== false || strpos($content, '</head ') !== false)
			&& ($this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags)
		) {
			$requirements = '';
			$jsRequirements = '';

			// Combine files - updates $this->javascript and $this->css
			$this->processCombinedFiles();

			foreach(array_diff_key($this->javascript, $this->blocked) as $file => $dummy) {
				$path = Convert::raw2xml($this->pathForFile($file));
				if($path) {
					$jsRequirements .= "<script type=\"text/javascript\" src=\"$path\"></script>\n";
				}
			}

			// add all inline javascript *after* including external files which
			// they might rely on
			if($this->customScript) {
				foreach(array_diff_key($this->customScript, $this->blocked) as $script) {
					$jsRequirements .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
					$jsRequirements .= "$script\n";
					$jsRequirements .= "\n//]]>\n</script>\n";
				}
			}

			foreach(array_diff_key($this->css, $this->blocked) as $file => $params) {
				$path = Convert::raw2xml($this->pathForFile($file));
				if($path) {
					$media = (isset($params['media']) && !empty($params['media']))
						? " media=\"{$params['media']}\"" : "";
					$requirements .= "<link rel=\"stylesheet\" type=\"text/css\"{$media} href=\"$path\" />\n";
				}
			}

			foreach(array_diff_key($this->customCSS, $this->blocked) as $css) {
				$requirements .= "<style type=\"text/css\">\n$css\n</style>\n";
			}

			foreach(array_diff_key($this->customHeadTags, $this->blocked) as $customHeadTag) {
				$requirements .= "$customHeadTag\n";
			}

			if (Config::inst()->get('Requirements', 'force_js_to_bottom')) {
				// Remove all newlines from code to preserve layout
				$jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);

				// We put script tags into the body, for performance.
				// We forcefully put it at the bottom instead of before
				// the first script-tag occurence
				$content = preg_replace("/(<\/body[^>]*>)/i", $jsRequirements . "\\1", $content);
				
				// Put CSS at the bottom of the head
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);				
			} elseif(Config::inst()->get('Requirements', 'write_js_to_body')) {
				// Remove all newlines from code to preserve layout
				$jsRequirements = preg_replace('/>\n*/', '>', $jsRequirements);

				// We put script tags into the body, for performance.
				// If your template already has script tags in the body, then we try to put our script
				// tags just before those. Otherwise, we put it at the bottom.
				$p2 = stripos($content, '<body');
				$p1 = stripos($content, '<script', $p2);

				$commentTags = array();
				$canWriteToBody = ($p1 !== false)
					&&
					//check that the script tag is not inside a html comment tag
					!(
						preg_match('/.*(?|(<!--)|(-->))/U', $content, $commentTags, 0, $p1)
						&& 
						$commentTags[1] == '-->'
					);
				if($canWriteToBody) {
					$content = substr($content,0,$p1) . $jsRequirements . substr($content,$p1);
				} else {
					$content = preg_replace("/(<\/body[^>]*>)/i", $jsRequirements . "\\1", $content);
				}

				// Put CSS at the bottom of the head
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);
			} else {
				$content = preg_replace("/(<\/head>)/i", $requirements . "\\1", $content);
				$content = preg_replace("/(<\/head>)/i", $jsRequirements . "\\1", $content);
			}
		}

		return $content;
	}

	/**
	 * Attach requirements inclusion to X-Include-JS and X-Include-CSS headers on the HTTP response
	 */
	public function includeInResponse(SS_HTTPResponse $response) {
		$this->processCombinedFiles();
		$jsRequirements = array();
		$cssRequirements = array();

		foreach(array_diff_key($this->javascript, $this->blocked) as $file => $dummy) {
			$path = $this->pathForFile($file);
			if($path) {
				$jsRequirements[] = str_replace(',', '%2C', $path);
			}
		}

		$response->addHeader('X-Include-JS', implode(',', $jsRequirements));

		foreach(array_diff_key($this->css, $this->blocked) as $file => $params) {
			$path = $this->pathForFile($file);
			if($path) {
				$path = str_replace(',', '%2C', $path);
				$cssRequirements[] = isset($params['media']) ? "$path:##:$params[media]" : $path;
			}
		}

		$response->addHeader('X-Include-CSS', implode(',', $cssRequirements));
	}

	/**
	 * Add i18n files from the given javascript directory.  SilverStripe expects that the given directory
	 * will contain a number of java script files named by language: en_US.js, de_DE.js, etc.
	 *
	 * @param String The javascript lang directory, relative to the site root, e.g., 'framework/javascript/lang'
	 * @param Boolean Return all relative file paths rather than including them in requirements
	 * @param Boolean Only include language files, not the base libraries
	 */
	public function addI18nJavaScript($langDir, $return = false, $langOnly = false) {
		$files = array();
		$base = Director::baseFolder() . '/';
		if(i18n::config()->js_i18n) {
			// Include i18n.js even if no languages are found.  The fact that
			// add_i18n_javascript() was called indicates that the methods in
			// here are needed.
			if(!$langOnly) $files[] = FRAMEWORK_DIR . '/javascript/i18n.js';

			if(substr($langDir,-1) != '/') $langDir .= '/';

			$candidates = array(
				'en.js',
				'en_US.js',
				i18n::get_lang_from_locale(i18n::default_locale()) . '.js',
				i18n::default_locale() . '.js',
				i18n::get_lang_from_locale(i18n::get_locale()) . '.js',
				i18n::get_locale() . '.js',
			);
			foreach($candidates as $candidate) {
				if(file_exists($base . DIRECTORY_SEPARATOR . $langDir . $candidate)) {
					$files[] = $langDir . $candidate;
				}
			}
		} else {
			// Stub i18n implementation for when i18n is disabled.
			if(!$langOnly) $files[] = FRAMEWORK_DIR . '/javascript/i18nx.js';
		}

		if($return) {
			return $files;
		} else {
			foreach($files as $file) $this->javascript($file);
		}
	}

	/**
	 * Finds the path for specified file.
	 *
	 * @param string $fileOrUrl
	 * @return string|boolean
	 */
	protected function pathForFile($fileOrUrl) {
		if(preg_match('{^//|http[s]?}', $fileOrUrl)) {
			return $fileOrUrl;
		} elseif(Director::fileExists($fileOrUrl)) {
			$filePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $fileOrUrl);
			$prefix = Director::baseURL();
			$mtimesuffix = "";
			$suffix = '';
			if(Config::inst()->get('Requirements', 'suffix_requirements')) {
				$mtimesuffix = "?m=" . filemtime($filePath);
				$suffix = '&';
			}
			if(strpos($fileOrUrl, '?') !== false) {
				if (strlen($suffix) == 0) {
					$suffix = '?';
				}
				$suffix .= substr($fileOrUrl, strpos($fileOrUrl, '?')+1);
				$fileOrUrl = substr($fileOrUrl, 0, strpos($fileOrUrl, '?'));
			} else {
				$suffix = '';
			}
			return "{$prefix}{$fileOrUrl}{$mtimesuffix}{$suffix}";
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
	 * @todo Should we enforce unique inclusion of files, or leave it to the developer? Can auto-detection cause
	 *       breaks?
	 *
	 * @param string $combinedFileName Filename of the combined file (will be stored in {@link Director::baseFolder()}
	 *                                 by default)
	 * @param array $files Array of filenames relative to the webroot
	 * @param string $media Comma-separated list of media-types (e.g. "screen,projector").
	 */
	public function combineFiles($combinedFileName, $files, $media = null) {
		// duplicate check
		foreach($this->combine_files as $_combinedFileName => $_files) {
			$duplicates = array_intersect($_files, $files);
			if($duplicates && $combinedFileName != $_combinedFileName) {
				user_error("RequirementsHandler::combineFiles(): Already included files " . implode(',', $duplicates)
					. " in combined file '{$_combinedFileName}'", E_USER_NOTICE);
				return false;
			}
		}
		foreach($files as $index=>$file) {
			if(is_array($file)) {
				// Either associative array path=>path type=>type or numeric 0=>path 1=>type
				// Otherwise, assume path is the first item
				if (isset($file['type']) && in_array($file['type'], array('css', 'javascript', 'js'))) {
					switch ($file['type']) {
						case 'css':
							$this->css($file['path'], $media);
							break;
						default:
							$this->javascript($file['path']);
							break;
					}
					$files[$index] = $file['path'];
				} elseif (isset($file[1]) && in_array($file[1], array('css', 'javascript', 'js'))) {
					switch ($file[1]) {
						case 'css':
							$this->css($file[0], $media);
							break;
						default:
							$this->javascript($file[0]);
							break;
					}
					$files[$index] = $file[0];
				} else {
					$file = array_shift($file);
				}
			}
			if (!is_array($file)) {
				if(substr($file, -2) == 'js') {
					$this->javascript($file);
				} elseif(substr($file, -3) == 'css') {
					$this->css($file, $media);
				} else {
					user_error("RequirementsHandler::combineFiles(): Couldn't guess file type for file '$file', "
						. "please specify by passing using an array instead.", E_USER_NOTICE);
				}
			}
		}
		$this->combine_files[$combinedFileName] = $files;
	}

	/**
	 * Returns all combined files.
	 * @return array
	 */
	public function getCombineFiles() {
		return $this->combine_files;
	}

	/**
	 * Deletes all dynamically generated combined files from the filesystem.
	 *
	 * @param string $combinedFileName If left blank, all combined files are deleted.
	 */
	public function deleteCombinedFiles($combinedFileName = null) {
		$combinedFiles = ($combinedFileName) ? array($combinedFileName => null) : $this->combine_files;
		$folderName = $this->getCombinedFilesFolder();
		$combinedFolder = ($folderName) ? Director::baseFolder() . '/' . $folderName : Director::baseFolder();
		foreach($combinedFiles as $combinedFile => $sourceItems) {
			$filePath = $combinedFolder . '/' . $combinedFile;
			if(file_exists($filePath)) {
				unlink($filePath);
			}
		}
	}

	/**
	 * Deletes all generated combined files in the configured combined files directory,
	 * but doesn't delete the directory itself.
	 */
	public function deleteAllCombinedFiles() {
		$combinedFolder = $this->getCombinedFilesFolder();
		if(!$combinedFolder) return false;

		$path = Director::baseFolder() . '/' . $combinedFolder;
		if(file_exists($path)) {
			Filesystem::removeFolder($path, true);
		}
	}

	public function clearCombinedFiles() {
		$this->combine_files = array();
	}

	/**
	 * See {@link combine_files()}
	 */
	public function processCombinedFiles() {
		// The class_exists call prevents us from loading SapphireTest.php (slow) just to know that
		// SapphireTest isn't running :-)
		if(class_exists('SapphireTest', false)) $runningTest = SapphireTest::is_running_test();
		else $runningTest = false;

		if(
			(Director::isDev() && ! $runningTest && ! isset($_REQUEST['combine']))
			|| ( ! Config::inst()->get('Requirements', 'combined_files_enabled'))
		) {
			return;
		}

		// Make a map of files that could be potentially combined
		$combinerCheck = array();
		foreach($this->combine_files as $combinedFile => $sourceItems) {
			foreach($sourceItems as $sourceItem) {
				if(isset($combinerCheck[$sourceItem]) && $combinerCheck[$sourceItem] != $combinedFile){
					user_error("RequirementsHandler::process_combined_files - file '$sourceItem' appears in two " .
						"combined files:" .	" '{$combinerCheck[$sourceItem]}' and '$combinedFile'", E_USER_WARNING);
				}
				$combinerCheck[$sourceItem] = $combinedFile;

			}
		}

		// Work out the relative URL for the combined files from the base folder
		$combinedFilesFolder = $this->getCombinedFilesFolder();
		$combinedFilesFolder = ($combinedFilesFolder) ? $combinedFilesFolder . '/' : '';

		// Figure out which ones apply to this pageview
		$combinedFiles = array();
		$newJSRequirements = array();
		$newCSSRequirements = array();
		foreach($this->javascript as $file => $dummy) {
			if(isset($combinerCheck[$file])) {
				$newJSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = true;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newJSRequirements[$file] = true;
			}
		}

		foreach($this->css as $file => $params) {
			if(isset($combinerCheck[$file])) {
				// Inherit the parameters from the last file in the combine set.
				$newCSSRequirements[$combinedFilesFolder . $combinerCheck[$file]] = $params;
				$combinedFiles[$combinerCheck[$file]] = true;
			} else {
				$newCSSRequirements[$file] = $params;
			}
		}

		// Process the combined files
		$base = Director::baseFolder() . '/';
		foreach(array_diff_key($combinedFiles, $this->blocked) as $combinedFile => $dummy) {
			$fileList = $this->combine_files[$combinedFile];
			$combinedFilePath = $base . $combinedFilesFolder . '/' . $combinedFile;


			// Make the folder if necessary
			if(!file_exists(dirname($combinedFilePath))) {
				Filesystem::makeFolder(dirname($combinedFilePath));
			}

			// If the file isn't writeable, don't even bother trying to make the combined file and return (falls back
			//  to uncombined).  Complex test because is_writable fails if the file doesn't exist yet.
			if((file_exists($combinedFilePath) && !is_writable($combinedFilePath))
				|| (!file_exists($combinedFilePath) && !is_writable(dirname($combinedFilePath)))
			) {
				user_error("RequirementsHandler::process_combined_files(): Couldn't create '$combinedFilePath'",
					E_USER_WARNING);
				return false;
			}

			// Determine if we need to build the combined include
			if(file_exists($combinedFilePath)) {
				// file exists, check modification date of every contained file
				$srcLastMod = 0;
				foreach($fileList as $file) {
					if(file_exists($base . $file)) {
						$srcLastMod = max(filemtime($base . $file), $srcLastMod);
					}
				}
				$refresh = $srcLastMod > filemtime($combinedFilePath);
			} else {
				// file doesn't exist, or refresh was explicitly required
				$refresh = true;
			}

			if(!$refresh) continue;

			$combinedData = "";
			$failedToMinify = false;
			foreach(array_diff($fileList, $this->blocked) as $file) {
				$fileContent = file_get_contents($base . $file);
				
				$fileContent = file_get_contents($base . $file);
				
				try {
					$fileContent = $this->minifyFile($file, $fileContent);
				} catch(Exception $e) {
					$failedToMinify = true;
				}

				if (Config::inst()->get('Requirements', 'write_header_comment')) {
					// write a header comment for each file for easier identification and debugging
					// also the semicolon between each file is required for jQuery to be combinable properly
					$combinedData .= "/****** FILE: $file *****/\n";
				}

				$combinedData .= $fileContent . "\n";
			}

			$successfulWrite = false;
			$fh = fopen($combinedFilePath, 'wb');
			if($fh) {
				if(fwrite($fh, $combinedData) == strlen($combinedData)) $successfulWrite = true;
				fclose($fh);
				unset($fh);
			}

			if($failedToMinify) {
				// Failed to minify, use unminified. This warning is raised at the end to allow code execution
				// to complete in case this warning is caught inside a try-catch block. 
				user_error('Failed to minify '.$file.', exception: '.$e->getMessage(), E_USER_WARNING);
			}

			// Unsuccessful write - just include the regular JS files, rather than the combined one
			if(!$successfulWrite) {
				user_error("RequirementsHandler::process_combined_files(): Couldn't create '$combinedFilePath'",
					E_USER_WARNING);
				continue;
			}
		}

		// @todo Alters the original information, which means you can't call this
		// method repeatedly - it will behave different on the second call!
		$this->javascript = $newJSRequirements;
		$this->css = $newCSSRequirements;
	}

	protected function minifyFile($filename, $content) {
		// if we have a javascript file and jsmin is enabled, minify the content
		$isJS = stripos($filename, '.js');
		if($isJS && Config::inst()->get('Requirements', 'combine_js_with_jsmin')) {
			require_once('thirdparty/jsmin/jsmin.php');

			increase_time_limit_to();
			$content = JSMin::minify($content);
		}
		$content .= ($isJS ? ';' : '') . "\n";
		return $content;
	}

	public function getCustomScripts() {
		$requirements = "";

		if($this->customScript) {
			foreach($this->customScript as $script) {
				$requirements .= "$script\n";
			}
		}

		return $requirements;
	}

	/**
	 * @see Requirements::themedCSS()
	 */
	public function themedCSS($name, $module = null, $media = null) {
		$theme = SSViewer::get_theme_folder();
		$project = project();
		$absbase = BASE_PATH . DIRECTORY_SEPARATOR;
		$abstheme = $absbase . $theme;
		$absproject = $absbase . $project;
		$css = "/css/$name.css";
		
		if(file_exists($absproject . $css)) {
			$this->css($project . $css, $media);
		} elseif($module && file_exists($abstheme . '_' . $module.$css)) {
			$this->css($theme . '_' . $module . $css, $media);
		} elseif(file_exists($abstheme . $css)) {
			$this->css($theme . $css, $media);
		} elseif($module) {
			$this->css($module . $css, $media);
		}
	}

	public function debug() {
		Debug::show($this->javascript);
		Debug::show($this->css);
		Debug::show($this->customCSS);
		Debug::show($this->customScript);
		Debug::show($this->customHeadTags);
		Debug::show($this->combine_files);
	}

}