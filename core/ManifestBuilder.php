<?php

/**
 * Generates the manifest file and keeps it fresh.
 * The manifest file is a PHP include that contains global variables that represent the collected
 * contents of the application: 
 * <ul><li>all classes</li>
 * <li>all templates</li>
 * <li>all _config.php files</li></ul>
 * Traversing the filesystem to collect this information on everypage
 * This information is cached so that it need not be regenerated on every pageview.
 */
 
define("MANIFEST_FILE", TEMP_FOLDER . "/manifest" . str_replace(array("/",":", "\\"),"_", $_SERVER['SCRIPT_FILENAME']));

class ManifestBuilder {
	
	static $restrict_to_modules = array();
	
	/**
	 * Returns true if the manifest file should be regenerated
	 */
	static function staleManifest() {
		/*if(Director::isDev() || Director::isTest()) $lastEdited = Filesystem::folderModTime(".", array('ss','php'));
		else*/ $lastEdited = filemtime("../");

		return !file_exists(MANIFEST_FILE) 
			|| (filemtime(MANIFEST_FILE) < $lastEdited) 
			|| (filemtime(MANIFEST_FILE) < time() - 3600)
			|| isset($_GET['buildmanifest']) || isset($_GET['flush']);
	}
	
	/**
	 * Generates a new manifest file and saves it to MANIFEST_FILE
	 */
	static function compileManifest() {

		// Config manifest
		$baseDir = dirname($_SERVER['SCRIPT_FILENAME']) . "/..";	
		$baseDir = ereg_replace("/[^/]+/\\.\\.","",$baseDir);

		// locate the exclude file
		$topLevel = scandir( $baseDir );
		
		foreach($topLevel as $file) {
			$fullPath = $baseDir . '/' . $file;
			
			// echo $fullPath . '<br />';
			
			if( is_dir($fullPath . '/') && file_exists($fullPath . '/_exclude.php') )
				require_once($fullPath . '/_exclude.php');
		}
		
		$classManifest = array();
		
		// Class manifest
		if( is_array(self::$restrict_to_modules) && count(self::$restrict_to_modules) ) {
			foreach(self::$restrict_to_modules as $module)
				ManifestBuilder::getClassManifest($baseDir.'/'.$module, $classManifest); 
		} else {
			// Only include directories if they have an _config.php file
			$topLevel = scandir($baseDir);
			foreach($topLevel as $filename) {
				if(is_dir("$baseDir/$filename") && file_exists("$baseDir/$filename/_config.php")) {
					ManifestBuilder::getClassManifest("$baseDir/$filename", $classManifest);
				}
			}
		}
			
		
		$manifest = "\$_CLASS_MANIFEST = " . var_export($classManifest, true) . ";\n";

		// Load the manifest in, so that the autoloader works
		global $_CLASS_MANIFEST;
		$_CLASS_MANIFEST = $classManifest;
		

		// _config.php manifest
		global $databaseConfig;
		$topLevel = scandir($baseDir);
		foreach($topLevel as $filename) {
			if(is_dir("$baseDir/$filename/") && file_exists("$baseDir/$filename/_config.php")) {
				$manifest .= "require_once(\"$baseDir/$filename/_config.php\");\n";
				// Include this so that we're set up for connecting to the database in the rest of the manifest builder
				require_once("$baseDir/$filename/_config.php");		
			}
		}

		if(!project()) user_error("\$project isn't set", E_USER_WARNING);

		// Template & CSS manifest
		$templateManifest = array();
		$cssManifest = array();

		// Only include directories if they have an _config.php file
		$topLevel = scandir($baseDir);
		foreach($topLevel as $filename) {
			if(substr($filename,0,1) == '.') continue;
			if($filename != 'themes' && is_dir("$baseDir/$filename") && file_exists("$baseDir/$filename/_config.php")) {
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

		if($fh = fopen(MANIFEST_FILE,"w")) {			

			fwrite($fh, $manifest);
			fclose($fh);

		} else {
			die("Cannot write manifest file!  Check permissions of " . MANIFEST_FILE);
		}
	}

	/**
	 * Generates the class manifest - a list of all the PHP files in the application
	 */
	private static function getClassManifest($folder, &$classMap) {
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			if($item == 'main.php' || $item == 'cli-script.php' || $item == 'install.php' || $item == 'index.php' || $item == 'check-php.php' || $item == 'rewritetest.php') continue;
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php' && substr($item,0,1) != '_') {
				$itemCode = substr($item,0,-4);
				if($classMap && array_key_exists($itemCode, $classMap)) user_error("Warning: there are two '$itemCode' files: '$folder/$item' and '{$classMap[$itemCode]}'.  This might mean that the wrong code is being used.", E_USER_WARNING);
				$classMap[$itemCode] = "$folder/$item";
			} else if(is_dir("$folder/$item") && !in_array($item, array('mysql', 'assets', 'shortstat', 'HTML'))) ManifestBuilder::getClassManifest("$folder/$item", $classMap);
		}
	}
	
	/**
	 * Generates the template/css manifest - a list of all the .SS & .CSS files in the application
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


			} else if(is_dir("$baseDir/$folder/$item")) {
				ManifestBuilder::getTemplateManifest($baseDir, "$folder/$item", $templateManifest, $cssManifest, $themeName);
			}
		}
	}
	
	private static function allClasses($classManifest) {
		
		// Include everything, so we actually have *all* classes
		foreach($classManifest as $file) {
			$b = basename($file);
			if($b != 'cli-script.php' && $b != 'main.php') include_once($file);
		}
		
		if(DB::isActive()) {
			$tables = DB::getConn()->tableList();
		} else {
			$tables = array();
		}
		
		$allClasses['hastable'] = array();
		
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
	
	static function includeEverything() {
		global $_CLASS_MANIFEST;
		foreach($_CLASS_MANIFEST as $filename) {
			if( preg_match( '/.*cli-script\.php$/', $filename ) )
				continue;
				
			require_once($filename);
		}
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
		$topLevel = scandir($baseDir);

		foreach($topLevel as $filename) {
			if(is_dir("$baseDir/$filename/") && file_exists("$baseDir/$filename/_config.php")) {
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