<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * Name of the manifest file
 */
define("MANIFEST_FILE", TEMP_FOLDER . "/manifest" . str_replace(array("/",":", "\\"),"_", $_SERVER['SCRIPT_FILENAME']));

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
		'check-php.php',
		'rewritetest.php'
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
	 * Returns true if the manifest file should be regenerated
	 *
	 * @return bool Returns TRUE if the manifest file should be regenerated,
	 *              otherwise FALSE.
	 */
	static function staleManifest() {
		/*if(Director::isDev() || Director::isTest())
			$lastEdited = Filesystem::folderModTime(".", array('ss','php'));
		else*/
			$lastEdited = filemtime("../");

		return !file_exists(MANIFEST_FILE)
			|| (filemtime(MANIFEST_FILE) < $lastEdited)
			|| (filemtime(MANIFEST_FILE) < time() - 3600)
			|| isset($_GET['buildmanifest']) || isset($_GET['flush']);
	}


	/**
	 * Generates a new manifest file and saves it to {@link MANIFEST_FILE}
	 */
	static function compileManifest() {

		// Config manifest
		$baseDir = dirname($_SERVER['SCRIPT_FILENAME']) . "/..";
		$baseDir = ereg_replace("/[^/]+/\\.\\.", "", $baseDir);
		$baseDir = preg_replace("/\\\\/", "/", $baseDir);

		// locate and include the exclude files
		$topLevel = scandir($baseDir);
		foreach($topLevel as $file) {
			if($file[0] == '.') continue
			
			$fullPath = $baseDir . '/' . $file;

			if(@is_dir($fullPath . '/') && file_exists($fullPath . '/_exclude.php'))
				require_once($fullPath . '/_exclude.php');
		}


		// Class manifest
		$classManifest = array();
		if(is_array(self::$restrict_to_modules) && count(self::$restrict_to_modules)) {
			// $restrict_to_modules is set, so we include only those specified
			// modules
			foreach(self::$restrict_to_modules as $module)
				ManifestBuilder::getClassManifest($baseDir . '/' . $module,
																					$classManifest);
		} else {
			// Include all directories which have an _config.php file but don't
			// have an _manifest_exclude file
			$topLevel = scandir($baseDir);
			foreach($topLevel as $filename) {
				if($filename[0] == '.') continue;
				if(@is_dir("$baseDir/$filename") &&
						 file_exists("$baseDir/$filename/_config.php") &&
						 !file_exists("$baseDir/$filename/_manifest_exclude")) {
					ManifestBuilder::getClassManifest("$baseDir/$filename",
																						$classManifest);
				}
			}
		}


		$manifest = "\$_CLASS_MANIFEST = " . var_export($classManifest, true) .
			";\n";

		// Load the manifest in, so that the autoloader works
		global $_CLASS_MANIFEST;
		$_CLASS_MANIFEST = $classManifest;


		// _config.php manifest
		global $databaseConfig;
		$topLevel = scandir($baseDir);
		foreach($topLevel as $filename) {
			if($filename[0] == '.') continue;
			if(@is_dir("$baseDir/$filename/") &&
					 file_exists("$baseDir/$filename/_config.php") &&
					 !file_exists("$baseDir/$filename/_manifest_exclude")) {
				$manifest .= "require_once(\"$baseDir/$filename/_config.php\");\n";
				// Include this so that we're set up for connecting to the database
				// in the rest of the manifest builder
				require_once("$baseDir/$filename/_config.php");
			}
		}

		if(!project())
			user_error("\$project isn't set", E_USER_WARNING);

		// Template & CSS manifest
		$templateManifest = array();
		$cssManifest = array();

		// Only include directories if they have an _config.php file
		$topLevel = scandir($baseDir);
		foreach($topLevel as $filename) {
			if($filename[0] == '.') continue;
			if($filename != 'themes' && @is_dir("$baseDir/$filename") && file_exists("$baseDir/$filename/_config.php")) {
				ManifestBuilder::getTemplateManifest($baseDir, $filename, $templateManifest, $cssManifest);
			}
		}

		// Get themes
		if(file_exists("$baseDir/themes")) {
			$themeDirs = scandir("$baseDir/themes");
			foreach($themeDirs as $themeDir) {
				if(substr($themeDir,0,1) == '.') continue;
				// The theme something_forum is understood as being a part of the theme something
				$themeName = strtok($themeDir, '_');
				ManifestBuilder::getTemplateManifest($baseDir, "themes/$themeDir", $templateManifest, $cssManifest, $themeName);
			}
		}

		// Ensure that any custom templates get favoured
		ManifestBuilder::getTemplateManifest($baseDir, project(), $templateManifest, $cssManifest);

		$manifest .= "\$_TEMPLATE_MANIFEST = " . var_export($templateManifest, true) . ";\n";
		$manifest .= "\$_CSS_MANIFEST = " . var_export($cssManifest, true) . ";\n";
		DB::connect($databaseConfig);

		// Database manifest
		$allClasses = ManifestBuilder::allClasses($classManifest);

		$manifest .= "\$_ALL_CLASSES = " . var_export($allClasses, true) . ";\n";

		global $_ALL_CLASSES;
		$_ALL_CLASSES = $allClasses;

		// Write manifest to disk
		$manifest = "<?php\n$manifest\n?>";

		if($fh = fopen(MANIFEST_FILE, "w")) {
			fwrite($fh, $manifest);
			fclose($fh);
		} else {
			die("Cannot write manifest file! Check permissions of " .
					MANIFEST_FILE);
		}
	}


	/**
	 * Generates the class manifest - a list of all the PHP files in the
	 * application
	 *
	 * @param string $folder The folder to traverse (recursively)
	 * @param array $classMap The already built class map
	 */
	private static function getClassManifest($folder, &$classMap) {
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
				// recurse into directories (if not in $ignore_folders)
				ManifestBuilder::getClassManifest("$folder/$item", $classMap);
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
	private static function getTemplateManifest($baseDir, $folder, &$templateManifest, &$cssManifest, $themeName = null) {
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
				ManifestBuilder::getTemplateManifest($baseDir, "$folder/$item", $templateManifest, $cssManifest, $themeName);
			}
		}
	}


	/**
	 * Include everything, so that actually *all* classes are available and
	 * build a map of classes and their subclasses and the information if
	 * the class has a database table
	 *
	 * @return array Returns an array that holds all class relevant
	 *               information.
	 */
	private static function allClasses($classManifest) {

		// Include everything, so we actually have *all* classes
		foreach($classManifest as $file) {
			$b = basename($file);
			if($b != 'cli-script.php' && $b != 'main.php')
				self::parse_file($file);
		}

		$tables = DB::isActive() ? DB::getConn()->tableList() : array();

		$allClasses["parents"] = self::find_parents();
		$allClasses["children"] = self::find_children();
		$allClasses["implementors"] = self::$implementsArray;

		foreach(self::$classArray as $class => $info) {
			$allClasses['exists'][$class] = $class;
			if(isset($tables[strtolower($class)])) $allClasses['hastable'][$class] = $class;
		}

		// Build a map of classes and their subclasses
		$_classes = get_declared_classes();

		foreach($_classes as $class) {
			$allClasses['exists'][$class] = $class;
			if(isset($tables[strtolower($class)])) $allClasses['hastable'][$class] = $class;
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

		if(!$file) die("Couldn't open $filename<br />");

		$classes = array();
		$size = preg_match_all('/class (.*)[ \n]*{/m', $file, $classes);

		for($i=0; $i < $size; $i++) {
				//we have a class
				$args = split("implements", $classes[1][$i]);
				$implements = isset($args[1]) ? $args[1] : null;

				$interfaces = explode(",", trim($implements));
				
				$args = split("extends", $args[0]);
				$extends = trim(isset($args[1]) ? $args[1] : null);
				$class = trim($args[0]);
				if($extends) self::$extendsArray[trim($extends)][$class] = $class;

				foreach($interfaces as $interface) {
					self::$implementsArray[trim($interface)][$class] = $class;
				}

				self::$classArray[$class] = array(
					"interfaces" => $interfaces,
					"extends" => $extends,
					"file" => $filename
				);
			}

			$interfaces = array();
			$size = preg_match_all('/interface (.*){/', $file, $interfaces);

			for($i=0;$i<$size;$i++) {
				$class = trim($interfaces[1][$i]);
				self::$classArray[$class] = array(
					"interfaces"=>array(),
					"extends" => "",
					"isinterface"=>true
				);
			}
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

	/**
	 * Updates the active table list in the class info in the manifest, but leaves everything else as-is.
	 * Much quicker to run than compileManifest :-)
	 */
	static function update_db_tables() {
		global $_ALL_CLASSES;
		$_ALL_CLASSES['hastable'] = array();

		$tables = DB::getConn()->tableList();

		// We need to iterate through the full class lists, because the table names come out in lowercase
		foreach($_ALL_CLASSES['exists'] as $class) {
			if(isset($tables[strtolower($class)])) $_ALL_CLASSES['hastable'][$class] = $class;
		}

		self::write_manifest();
	}

	/**
	 * Write the manifest file, containing the updated values in the applicable globals
	 */
	static function write_manifest() {
		global $_CLASS_MANIFEST, $_TEMPLATE_MANIFEST, $_CSS_MANIFEST, $_ALL_CLASSES;

		$manifest = "\$_CLASS_MANIFEST = " . var_export($_CLASS_MANIFEST, true) . ";\n";

		// Config manifest
		$baseDir = dirname($_SERVER['SCRIPT_FILENAME']) . "/..";
		$baseDir = ereg_replace("/[^/]+/\\.\\.","",$baseDir);
		$baseDir = preg_replace("/\\\\/", "/", $baseDir);
		$topLevel = scandir($baseDir);

		foreach($topLevel as $filename) {
			if($filename[0] == '.') continue;
			if(@is_dir("$baseDir/$filename/") && file_exists("$baseDir/$filename/_config.php")) {
				$manifest .= "require_once(\"$baseDir/$filename/_config.php\");\n";
			}
		}

		$manifest .= "\$_TEMPLATE_MANIFEST = " . var_export($_TEMPLATE_MANIFEST, true) . ";\n";
		$manifest .= "\$_CSS_MANIFEST = " . var_export($_CSS_MANIFEST, true) . ";\n";
		$manifest .= "\$_ALL_CLASSES = " . var_export($_ALL_CLASSES, true) . ";\n";
		$manifest = "<?php\n$manifest\n?>";

		if($fh = fopen(MANIFEST_FILE,"w")) {
			fwrite($fh, $manifest);
			fclose($fh);

		} else {
			die("Cannot write manifest file!  Check permissions of " . MANIFEST_FILE);
		}
	}

}


?>
