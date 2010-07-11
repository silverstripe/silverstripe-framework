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
 * <b>Autoloading</b>
 * 
 * Sapphire class autoloader.  Requires the ManifestBuilder to work.
 * $_CLASS_MANIFEST must have been loaded up by ManifestBuilder for this to successfully load classes.  
 * Classes will be loaded from any PHP file within the application. If your class contains an underscore, 
 * for example, Page_Controller, then the filename is expected to be the stuff before the underscore.  
 * In this case, Page.php.
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
		$testManifestFile = MANIFEST_FILE . '-test';

		// The dev/build reference is some coupling but it solves an annoying bug
		if(!file_exists($testManifestFile) 
			|| (filemtime($testManifestFile) < filemtime(BASE_PATH)) 
			|| isset($_GET['flush'])) {
				
			// Build the manifest, including the tests/ folders
			$manifestInfo = self::get_manifest_info(BASE_PATH);
			$manifest = self::generate_php_file($manifestInfo);
			if($fh = fopen($testManifestFile, 'wb')) {
				fwrite($fh, $manifest);
				fclose($fh);
			} else {
				user_error("Cannot write manifest file! Check permissions of " . MANIFEST_FILE, E_USER_ERROR);
			}
		}
		
		require($testManifestFile);
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
		if($fh = fopen(MANIFEST_FILE, 'wb')) {
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
	 * Get themes from a particular directory.
	 * 
	 * @param string $baseDir Optional: Absolute path to theme directory for testing e.g. "/Users/sharvey/Sites/test24/themes"
	 * @param boolean $includeSubThemes If set to TRUE, sub-themes such as "blackcandy_blog" are included too
	 * @return array Listing of theme directories
	 */
	public static function get_themes($baseDir = null, $includeSubThemes = false) {
		// If no base directory specified, the default is the project root
		if(!$baseDir) $baseDir = BASE_PATH . DIRECTORY_SEPARATOR . THEMES_DIR;
		$themes = array();
		if(!file_exists($baseDir)) return $themes;

		$handle = opendir($baseDir);
		if($handle) {
			while(false !== ($file = readdir($handle))) {
				$fullPath = $baseDir . DIRECTORY_SEPARATOR . $file;
				if(strpos($file, '.') === false && is_dir($fullPath)) {
					$include = $includeSubThemes ? true : false;
					if(strpos($file, '_') === false) {
						$include = true;
					}
					if($include) $themes[$file] = $file;
				}
			}
			closedir($handle);
		}
		return $themes;
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
		$allPhpFiles = array();
		$templateManifest = array();
		$cssManifest = array();


		if(is_array(self::$restrict_to_modules) && count(self::$restrict_to_modules)) {
			// $restrict_to_modules is set, so we include only those specified
			// modules
			foreach(self::$restrict_to_modules as $module)
				ManifestBuilder::get_all_php_files($baseDir . '/' . $module, $excludedFolders, $allPhpFiles);
		} else {
			// Include all directories which have an _config.php file but don't
			// have an _manifest_exclude file
			$topLevel = scandir($baseDir);
			foreach($topLevel as $filename) {

				// Skip certain directories
				if($filename[0] == '.') continue;
				if($filename == THEMES_DIR) continue;
				if($filename == ASSETS_DIR) continue;
				if(in_array($filename, $excludedFolders)) continue;

				if(@is_dir("$baseDir/$filename") &&
						 file_exists("$baseDir/$filename/_config.php") &&
						 !file_exists("$baseDir/$filename/_manifest_exclude")) {
							
					// Get classes, templates, and CSS files
					ManifestBuilder::get_all_php_files("$baseDir/$filename", $excludedFolders, $allPhpFiles);
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
			$themeDirs = self::get_themes("$baseDir/themes", true);
			foreach($themeDirs as $themeDir) {
				$themeName = strtok($themeDir, '_');
				ManifestBuilder::getTemplateManifest($baseDir, THEMES_DIR . "/$themeDir", $excludedFolders, $templateManifest, $cssManifest, $themeName);
			}
		}

		// Build class-info array from class manifest
		$allClasses = ManifestBuilder::allClasses($allPhpFiles);
		
		// Pull the class filenames out
		$classManifest = $allClasses['file'];
		unset($allClasses['file']);

		// Ensure that any custom templates get favoured
		if(!$project) user_error("\$project isn't set", E_USER_WARNING);
		else if(!file_exists("$baseDir/$project")) user_error("\$project is set to '$project' but no such folder exists.", E_USER_WARNING);
		else ManifestBuilder::getTemplateManifest($baseDir, $project, $excludedFolders, $templateManifest, $cssManifest);

		$manifestInfo["globals"]["_CLASS_MANIFEST"] = $classManifest;
		$manifestInfo["globals"]["_ALL_CLASSES"] = $allClasses;
		$manifestInfo["globals"]["_TEMPLATE_MANIFEST"] = $templateManifest;
		$manifestInfo["globals"]["_CSS_MANIFEST"] = $cssManifest;

		return $manifestInfo;
	}


	/**
	 * Generates a list of all the PHP files that should be analysed by the manifest builder.
	 *
	 * @param string $folder The folder to traverse (recursively)
	 * @param array $classMap The already built class map
	 */
	private static function get_all_php_files($folder, $excludedFolders, &$allPhpFiles) {
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
				ManifestBuilder::get_all_php_files("$folder/$item", $excludedFolders, $allPhpFiles);
			} else {
				$allPhpFiles[] = "$folder/$item";
			}

		}
	}


	/**
	 * Generates the template manifest - a list of all the .ss files in the
	 * application.
	 * 
	 * See {@link SSViewer} for an overview on the array structure this class creates.
	 * 
	 * @param String $baseDir
	 * @param String $folder
	 */
	private static function getTemplateManifest($baseDir, $folder, $excludedFolders, &$templateManifest, &$cssManifest, $themeName = null) {
		$items = scandir("$baseDir/$folder");
		if($items) foreach($items as $item) {
			// Skip hidden files/folders
			if(substr($item,0,1) == '.') continue;
			
			// Parse *.ss files
			if(substr($item,-3) == '.ss') {
				// Remove extension from template name
				$templateName = substr($item, 0, -3);
				
				// The "type" is effectively a subfolder underneath $folder,
				// mostly "Includes" or "Layout".
				$templateType = substr($folder,strrpos($folder,'/')+1);
				// The parent folder counts as type "main" 
				if($templateType == "templates") $templateType = "main";

				// Write either to theme or to non-themed array
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
			// Class names are converted to lowercase for lookup to adhere to PHP's case-insensitive
			// way of dealing with them.
			$allClasses['file'][strtolower($class)] = $info['file'];
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

		if($file === null) user_error("ManifestBuilder::parse_file(): Couldn't open $filename", E_USER_ERROR);
		if(!$file) return;
		
		// We cache the parse results of each file, since only a few files will have changed between flushings
		// And, although it's accurate, TokenisedRegularExpression isn't particularly fast.
		// We use an MD5 of the file as a part of the cache key because using datetime caused problems when users
		// were upgrading their sites
		$fileMD5 = md5($file);
		$parseCacheFile = TEMP_FOLDER . "/manifestClassParse-" . str_replace(array("/", ":", "\\", "."), "_", basename($filename)) . "-$fileMD5";
		if(file_exists($parseCacheFile)) {
			include($parseCacheFile);
			// Check for a bad cache file
			if(!isset($classes) || !isset($interfaces) || !is_array($classes) || !is_array($interfaces)) {
				unset($classes);
				unset($interfaces);
			}
		}
		
		// Either the parseCacheFile doesn't exist, or its bad
		if(!isset($classes)) {
			$tokens = token_get_all($file);
			$classes = (array)self::getClassDefParser()->findAll($tokens);
			$interfaces = (array)self::getInterfaceDefParser()->findAll($tokens);
			
			$cacheContent = '<?php
				$classes = ' . var_export($classes,true) . ';
				$interfaces = ' . var_export($interfaces,true) . ';';

			if($fh = fopen($parseCacheFile, 'wb')) {
				fwrite($fh, $cacheContent);
				fclose($fh);
			}
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
			
			if(isset(self::$classArray[$className])) {
				$file1 = self::$classArray[$className]['file'];
				$file2 = $class['file'];
				user_error("There are two files both containing the same class: '$file1' and " .
					"'$file2'. This might mean that the wrong code is being used.", E_USER_WARNING);
			}
			
			self::$classArray[$className] = $class;
		}
		
		foreach($interfaces as $interface) {
			$className = $interface['interfaceName'];
			unset($interface['interfaceName']);
			$interface['file'] = $filename;
			if(!isset($interface['extends'])) $interface['extends'] = null;

			if(isset(self::$classArray[$className])) {
				$file1 = self::$classArray[$className]['file'];
				$file2 = $interface[$className];
				user_error("There are two files both containing the same class: '$file1' and " .
					"'$file2'. This might mean that the wrong code is being used.", E_USER_WARNING);
			}

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
		return (bool)!(empty($_CLASS_MANIFEST) && empty($_TEMPLATE_MANIFEST) && empty($_CSS_MANIFEST) && empty($_ALL_CLASSES));
	}

	/**
	 * Returns a flat array with all children of a given class
	 *
	 * @param string $class
	 * @param array $results
	 */
	static function up_children($class) {
		$children = self::get_Children($class);
		$results = $children;
			foreach($children as $className) {
				$results = array_merge($results, self::up_children($className));
			}
			return $results;;
	}
}