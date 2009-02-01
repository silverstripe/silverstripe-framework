<?php
/**
 * Define a constant for the name of the manifest file
 */
if(!defined('MANIFEST_FILE')) define("MANIFEST_FILE", TEMP_FOLDER . "/manifest-" . str_replace('.php','',basename($_SERVER['SCRIPT_FILENAME'])));

/**
 * The ManifestBuilder class generates the manifest file and keeps it fresh.
 * 
 * The manifest file is a PHP include that contains global variables that
 * represent the collected contents of the application:
 *   - all classes ({@link __autoload()})
 *   - all templates ({@link SSViewer})
 *   - all _config.php files
 *
 * Traversing the filesystem to collect this information on everypage.
 * This information is cached so that it need not be regenerated on every
 * pageview.
 * 
 * {@link ManifestBuilder::compileManifest()} is called by {@link main.php} 
 * whenever {@link ManifestBuilder::staleManifest()} returns true.
 *
 * @see main.php, __autoload(), SSViewer, Requirements::themedCSS()
 * @package sapphire
 * @subpackage core
 */
class ManifestBuilder {

	static $restrict_to_modules = array();
	static $extendsArray = array();
	static $classArray = array();
	static $implementsArray = array();

	/**
	 * @var array $ignore_files Full filenames (without directory-path) which
	 * should be ignored by the manifest.
	 */
	public static $ignore_files = array(
		'main.php',
		'cli-script.php',
		'install.php',
		'index.php',
		'rewritetest.php',
	);

	/**
	 * @var array $ignore_folders Foldernames (without path) which
	 * should be ignored by the manifest.
	 */
	public static $ignore_folders = array(
		'mysql',
		'assets',
		'shortstat',
		'HTML',
	);
	
	/**
	 * Include the manifest, regenerating it if necessary
	 */
	static function include_manifest() {
		if(isset($_REQUEST['usetestmanifest'])) {
			self::load_test_manifest();
		} else {		
			// The dev/build reference is some coupling but it solves an annoying bug
			if(!file_exists(MANIFEST_FILE) || (filemtime(MANIFEST_FILE) < filemtime(BASE_PATH)) 
				|| isset($_GET['flush']) || (isset($_REQUEST['url']) && ($_REQUEST['url'] == 'dev/build' 
				|| $_REQUEST['url'] == BASE_URL . '/dev/build'))) {
				self::create_manifest_file();
			}
			require_once(MANIFEST_FILE);
		}
	}

	/**
	 * Load a copy of the manifest with tests/ folders included.
	 * Only loads the ClassInfo and __autoload() globals; this assumes that _config.php files are already included.
	 */
	static function load_test_manifest() {
		// Build the complete manifest
		$manifestInfo = self::get_manifest_info(BASE_PATH);
		// Load it into the current session.
		self::process_manifest($manifestInfo);
	}
	
	/**
	 * Loads all PHP class files - actually opening them and executing them.
	 */
	static function load_all_classes() {
		global $_CLASS_MANIFEST;
		foreach($_CLASS_MANIFEST as $classFile) require_once($classFile);
	}

	/**
	 * Generates a new manifest file and saves it to {@link MANIFEST_FILE}.
	 */
	static function create_manifest_file() {
		// Build the manifest, ignoring the tests/ folders
		$manifestInfo = self::get_manifest_info(BASE_PATH, array("tests"));

		$manifest = self::generate_php_file($manifestInfo);
		if($fh = fopen(MANIFEST_FILE, "w")) {
			fwrite($fh, $manifest);
			fclose($fh);
		} else {
			user_error("Cannot write manifest file! Check permissions of " . MANIFEST_FILE, E_USER_ERROR);
		}
	}
	
	/**
	 * Turn an array produced by get_manifest_info() into the content of the manifest PHP include
	 */
	static function generate_php_file($manifestInfo) {
		$output = "<?php\n";
		
		foreach($manifestInfo['globals'] as $globalName => $globalVal) {
			$output .= "global \$$globalName;\n\$$globalName = " . var_export($globalVal, true) . ";\n\n";
		}
		foreach($manifestInfo['require_once'] as $requireItem) {
			$output .= 'require_once("' . addslashes($requireItem) . "\");\n";
		}
		
		return $output;
	}

 
	/**
 	 * Parse the $manifestInfo array, updating the appropriate globals and loading the appropriate _config files.
 	 */
 	static function process_manifest($manifestInfo) {
 		foreach($manifestInfo['globals'] as $globalName => $globalVal) {
 			global $$globalName;
 			$$globalName = $globalVal;
 		}
 		foreach($manifestInfo['require_once'] as $requireItem) {
 			require_once("$requireItem");
 		}
 	}
	
	/**
	 * Return an array containing information for the manifest
	 * @param $baseDir The root directory to analyse
	 * @param $excludedFolders An array folder names to exclude.  These don't care about where the
	 *        folder appears in the hierarchy, so be careful
	 */
	static function get_manifest_info($baseDir, $excludedFolders = array()) {
		// locate and include the exclude files
		$topLevel = scandir($baseDir);
		foreach($topLevel as $file) {
			if($file[0] == '.') continue
			
			$fullPath = '';
			$fullPath = $baseDir . '/' . $file;

			if(@is_dir($fullPath . '/') && file_exists($fullPath . '/_exclude.php')) {
				require_once($fullPath . '/_exclude.php');
			}
		}
		
		// Project - used to give precedence to template files
		$project = null;

		// Class, CSS, template manifest
		$classManifest = array();
		$templateManifest = array();
		$cssManifest = array();


		if(is_array(self::$restrict_to_modules) && count(self::$restrict_to_modules)) {
			// $restrict_to_modules is set, so we include only those specified
			// modules
			foreach(self::$restrict_to_modules as $module)
				ManifestBuilder::getClassManifest($baseDir . '/' . $module, $excludedFolders, $classManifest);
		} else {
			// Include all directories which have an _config.php file but don't
			// have an _manifest_exclude file
			$topLevel = scandir($baseDir);
			foreach($topLevel as $filename) {
				if($filename[0] == '.') continue;
				if($filename == 'themes') continue;
				if(in_array($filename, $excludedFolders)) continue;

				if(@is_dir("$baseDir/$filename") &&
						 file_exists("$baseDir/$filename/_config.php") &&
						 !file_exists("$baseDir/$filename/_manifest_exclude")) {
							
					// Get classes, templates, and CSS files
					ManifestBuilder::getClassManifest("$baseDir/$filename", $excludedFolders, $classManifest);
					ManifestBuilder::getTemplateManifest($baseDir, $filename, $excludedFolders, $templateManifest, $cssManifest);

					// List the _config.php files
					$manifestInfo["require_once"][] = "$baseDir/$filename/_config.php";
					// Find the $project variable in the relevant config file without having to execute the config file
					if(preg_match("/\\\$project\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("$baseDir/$filename/_config.php"), $parts)) {
						eval($parts[0]);
					}

				}
			}
		}

		// Get themes
		if(file_exists("$baseDir/themes")) {
			$themeDirs = scandir("$baseDir/themes");
			foreach($themeDirs as $themeDir) {
				if(substr($themeDir,0,1) == '.') continue;
				// The theme something_forum is understood as being a part of the theme something
				$themeName = strtok($themeDir, '_');
				ManifestBuilder::getTemplateManifest($baseDir, "themes/$themeDir", $excludedFolders, $templateManifest, $cssManifest, $themeName);
			}
		}

		// Build class-info array from class manifest
		$allClasses = ManifestBuilder::allClasses($classManifest);

		// Ensure that any custom templates get favoured
		if(!$project) user_error("\$project isn't set", E_USER_WARNING);
		ManifestBuilder::getTemplateManifest($baseDir, $project, $excludedFolders, $templateManifest, $cssManifest);

		$manifestInfo["globals"]["_CLASS_MANIFEST"] = $classManifest;
		$manifestInfo["globals"]["_ALL_CLASSES"] = $allClasses;
		$manifestInfo["globals"]["_TEMPLATE_MANIFEST"] = $templateManifest;
		$manifestInfo["globals"]["_CSS_MANIFEST"] = $cssManifest;

		return $manifestInfo;
	}


	/**
	 * Generates the class manifest - a list of all the PHP files in the
	 * application
	 *
	 * @param string $folder The folder to traverse (recursively)
	 * @param array $classMap The already built class map
	 */
	private static function getClassManifest($folder, $excludedFolders, &$classMap) {
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			// Skip some specific PHP files
			if(in_array($item, self::$ignore_files)) continue;

			// ignore hidden files and folders
			if(substr($item,0,1) == '.') continue;

			// ignore files without php-extension
			if(substr($item,-4) != '.php' && !@is_dir("$folder/$item")) continue;

			// ignore files and folders with underscore-prefix
			if(substr($item,0,1) == '_') continue;

			// ignore certain directories
			if(@is_dir("$folder/$item") && in_array($item, self::$ignore_folders)) continue;

			// ignore directories with _manifest_exlude file
			if(@is_dir("$folder/$item") && file_exists("$folder/$item/_manifest_exclude")) continue;

			// i18n: ignore language files (loaded on demand)
			if($item == 'lang' && @is_dir("$folder/$item") && ereg_replace("/[^/]+/\\.\\.","",$folder.'/..') == Director::baseFolder()) continue;

			if(@is_dir("$folder/$item")) {
				// Folder exclusion - used to skip over tests/ folders
				if(in_array($item, $excludedFolders)) continue;
				
				// recurse into directories (if not in $ignore_folders)
				ManifestBuilder::getClassManifest("$folder/$item", $excludedFolders, $classMap);
			} else {
				// include item in the manifest
				$itemCode = substr($item,0,-4);
				// if $itemCode is already in manifest, check if the two files do really contain the same class
				if($classMap && array_key_exists($itemCode, $classMap)) {
					$regex = '/class\s' . $itemCode .'/';
					if(
						preg_match($regex, file_get_contents("$folder/$item"))
						&& preg_match($regex,  file_get_contents($classMap[$itemCode]))
					) {
						user_error("Warning: there are two '$itemCode' files both containing the same class: '$folder/$item' and '{$classMap[$itemCode]}'.
							This might mean that the wrong code is being used.", E_USER_WARNING);
					} else {
						user_error("Warning: there are two '$itemCode' files with the same filename: '$folder/$item' and '{$classMap[$itemCode]}'.
							This might mean that the wrong code is being used.", E_USER_NOTICE);
					}
				} else {
					$classMap[$itemCode] = "$folder/$item";
				}
			}

		}
	}


	/**
	 * Generates the template manifest - a list of all the .SS files in the
	 * application
	 */
	private static function getTemplateManifest($baseDir, $folder, $excludedFolders, &$templateManifest, &$cssManifest, $themeName = null) {
		$items = scandir("$baseDir/$folder");
		if($items) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-3) == '.ss') {
				$templateName = substr($item, 0, -3);
				$templateType = substr($folder,strrpos($folder,'/')+1);
				if($templateType == "templates") $templateType = "main";

				if($themeName) {
					$templateManifest[$templateName]['themes'][$themeName][$templateType] = "$baseDir/$folder/$item";
				} else {
					$templateManifest[$templateName][$templateType] = "$baseDir/$folder/$item";
				}

			} else if(substr($item,-4) == '.css') {
					$cssName = substr($item, 0, -4);
					// Debug::message($item);

					if($themeName) {
						$cssManifest[$cssName]['themes'][$themeName] = "$folder/$item";
					} else {
						$cssManifest[$cssName]['unthemed'] = "$folder/$item";
					}


			} else if(@is_dir("$baseDir/$folder/$item")) {
				// Folder exclusion - used to skip over tests/ folders
				if(in_array($item, $excludedFolders)) continue;

				ManifestBuilder::getTemplateManifest($baseDir, "$folder/$item", $excludedFolders, $templateManifest, $cssManifest, $themeName);
			}
		}
	}


	/**
	 * Include everything, so that actually *all* classes are available and
	 * build a map of classes and their subclasses
	 * 
	 * @param $classManifest An array of all Sapphire classes; keys are class names and values are filenames
	 *
	 * @return array Returns an array that holds all class relevant
	 *               information.
	 */
	private static function allClasses($classManifest) {
		self::$classArray = array();
		self::$extendsArray = array();
		self::$implementsArray = array();
		
		// Include everything, so we actually have *all* classes
		foreach($classManifest as $file) {
			$b = basename($file);
			if($b != 'cli-script.php' && $b != 'main.php')
				self::parse_file($file);
		}

		$allClasses["parents"] = self::find_parents();
		$allClasses["children"] = self::find_children();
		$allClasses["implementors"] = self::$implementsArray;

		foreach(self::$classArray as $class => $info) {
			$allClasses['exists'][$class] = $class;
		}

		// Build a map of classes and their subclasses
		$_classes = get_declared_classes();

		foreach($_classes as $class) {
			$allClasses['exists'][$class] = $class;
			foreach($_classes as $subclass) {
				if(is_subclass_of($class, $subclass)) $allClasses['parents'][$class][$subclass] = $subclass;
				if(is_subclass_of($subclass, $class)) $allClasses['children'][$class][$subclass] = $subclass;
			}
		}
		
		return $allClasses;
	}

	/**
	 * Parses a php file and adds any class or interface information into self::$classArray
	 *
	 * @param string $filename
	 */
	private static function parse_file($filename) {
		$file = file_get_contents($filename);

		$implements = "";
		$extends = "";
		$class="";

		if(!$file) user_error("ManifestBuilder::parse_file(): Couldn't open $filename", E_USER_ERROR);
		
		// We cache the parse results of each file, since only a few files will have changed between flushings
		// And, although it's accurate, TokenisedRegularExpression isn't particularly fast
		$parseCacheFile = TEMP_FOLDER . "/manifestClassParse-" . str_replace(array("/",":", "\\"),"_", realpath($filename));
		if(!file_exists($parseCacheFile) || filemtime($parseCacheFile) < filemtime($filename)) {
			$tokens = token_get_all($file);
			$classes = self::getClassDefParser()->findAll($tokens);
			$interfaces = self::getInterfaceDefParser()->findAll($tokens);
			
			$cacheContent = '<?php
				$classes = ' . var_export($classes,true) . ';
				$interfaces = ' . var_export($interfaces,true) . ';';
			if($fh = fopen($parseCacheFile,'w')) {
				fwrite($fh, $cacheContent);
				fclose($fh);
			}
			
		} else {
			include($parseCacheFile);
		}

		foreach($classes as $class) {
			$className = $class['className'];
			unset($class['className']);
			$class['file'] = $filename;
			if(!isset($class['extends'])) $class['extends'] = null;
			
			if($class['extends']) self::$extendsArray[$class['extends']][$className] = $className;
			if(isset($class['interfaces'])) foreach($class['interfaces'] as $interface) {
				self::$implementsArray[$interface][$className] = $className;
			}
			
			self::$classArray[$className] = $class;
		}

		foreach($interfaces as $interface) {
			$className = $interface['interfaceName'];
			unset($interface['interfaceName']);
			$interface['file'] = $filename;
			if(!isset($interface['extends'])) $interface['extends'] = null;
			self::$classArray[$className] = $interface;
		}
	}
	
	/**
	 * Returns a {@link TokenisedRegularExpression} object that will parse class definitions
	 * @return TokenisedRegularExpression
	 */
	public static function getClassDefParser() {
		require_once('core/TokenisedRegularExpression.php');
		
		return new TokenisedRegularExpression(array(
			0 => T_CLASS,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'can_jump_to' => array(7, 14), 'save_to' => 'className'),
			3 => T_WHITESPACE,
			4 => T_EXTENDS,
			5 => T_WHITESPACE,
			6 => array(T_STRING, 'save_to' => 'extends', 'can_jump_to' => 14),
			7 => T_WHITESPACE,
			8 => T_IMPLEMENTS,
			9 => T_WHITESPACE,
			10 => array(T_STRING, 'can_jump_to' => 14, 'save_to' => 'interfaces[]'),
			11 => array(T_WHITESPACE, 'optional' => true),
			12 => array(',', 'can_jump_to' => 10),
			13 => array(T_WHITESPACE, 'can_jump_to' => 10),
			14 => array(T_WHITESPACE, 'optional' => true),
			15 => '{',
		));
	}

	/**
	 * Returns a {@link TokenisedRegularExpression} object that will parse class definitions
	 * @return TokenisedRegularExpression
	 */
	public static function getInterfaceDefParser() {
		require_once('core/TokenisedRegularExpression.php');

		return new TokenisedRegularExpression(array(
			0 => T_INTERFACE,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'can_jump_to' => 7, 'save_to' => 'interfaceName'),
			3 => T_WHITESPACE,
			4 => T_EXTENDS,
			5 => T_WHITESPACE,
			6 => array(T_STRING, 'save_to' => 'extends'),
			7 => array(T_WHITESPACE, 'optional' => true),
			8 => '{',
		));
	}
	

	/**
	 * Moves through self::$classArray and creates an array containing parent data
	 *
	 * @return array
	 */
	private static function find_parents() {
		$parentArray = array();
		foreach(self::$classArray as $class => $info) {
			$extendArray = array();

			$parent = $info["extends"];

			while($parent) {
				$extendArray[$parent] = $parent;
				$parent = isset(self::$classArray[$parent]["extends"]) ? self::$classArray[$parent]["extends"] : null;
			}
			$parentArray[$class] = array_reverse($extendArray);
		}
		return $parentArray;
	}

	/**
	 * Iterates through self::$classArray and returns an array with any descendant data
	 *
	 * @return array
	 */
	private static function find_children() {
		$childrenArray = array();
		foreach(self::$extendsArray as $class => $children) {
			$allChildren = $children;
			foreach($children as $childName) {
				$allChildren = array_merge($allChildren, self::up_children($childName));
			}
			$childrenArray[$class] = $allChildren;
		}
		return $childrenArray;
	}

	/**
	 * Helper function to find all children of give class
	 *
	 * @param string $class
	 * @return array
	 */
	private static function get_children($class) {
		return isset(self::$extendsArray[$class]) ? self::$extendsArray[$class] : array();
	}
	
	/**
	 * Returns if the Manifest has been included
	 * 
	 * @return Boolean
	 */
	static function has_been_included() {
		global $_CLASS_MANIFEST, $_TEMPLATE_MANIFEST, $_CSS_MANIFEST, $_ALL_CLASSES;
		return (bool)(empty($_CLASS_MANIFEST) && empty($_TEMPLATE_MANIFEST) && empty($_CSS_MANIFEST) && empty($_ALL_CLASSES));
	}

	/**
	 * Returns a flat array with all children of a given class
	 *
	 * @param string $class
	 * @param array $results
	 */
	function up_children($class) {
		$children = self::get_Children($class);
		$results = $children;
			foreach($children as $className) {
				$results = array_merge($results, self::up_children($className));
			}
			return $results;;
	}
}

?>