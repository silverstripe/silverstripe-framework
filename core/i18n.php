<?php

/**
 * Silverstripe i18n API
 *
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 */

/**
 * Priorities definition. These constants are used in calls to _() as an optional argument
 */
define('PR_HIGH',100);
define('PR_MEDIUM',50);
define('PR_LOW',10);

class i18n extends Controller {
	
	/**
	 * This static variable is used to store the current defined locale. Default value is 'en'
	 */
	static $currentlocale = 'en';

	/**
	 * This is the main method to build the master string tables with the original strings.
	 * It will search for existent modules that use the i18n feature, parse the _() calls
	 * and write the resultant files in the lang folder of each module.
	 */	
	static function textCollector() {
	
		if (!Permission::check("ADMIN")) die("You must be an admin to enable text collector mode");
		echo "Collecting text...<br /><br />";
		
		//Calculate base directory
		$baseDir = Director::baseFolder();

		//Search for and process existent modules, or use the passed one instead
		if (!isset($_GET['module'])) {
			$topLevel = scandir($baseDir);
			foreach($topLevel as $module) {
				i18n::processModule($baseDir, $module);
			}
		} else {
			i18n::processModule($baseDir, $_GET['module']);
		}
		
		echo "Done!";
	
	}

	/**
	 * Searches for all the files in a given module
	 *
	 * @param string $baseDir Silverstripe's base directory
	 * @param string $module Module's name
	 */
	private static function processModule($baseDir, $module) {	
		if(is_dir("$baseDir/$module") && !in_array($module, array('sapphire','jsparty','assets')) && substr($module,0,1) != '.') {
			i18n::getFilesRec("$baseDir/$module/code", $fileList);
			foreach($fileList as $index => $file) {
				$mst .= i18n::reportCallsCode($index, $file);
			}
			$fileList = NULL;
			i18n::getFilesRec("$baseDir/$module/templates", $fileList);
			foreach($fileList as $index => $file) {
				$mst .= i18n::reportCallsTpl($index, $file);
			}
			if ($mst) {
				// Create folder for lang files
				$langFolder = $baseDir . '/' . $module . '/lang';
				if(!file_exists($baseDir. '/' . $module . '/lang')) {
					mkdir($langFolder);
				}
				
				// Open the English file and write the Master String Table
				if($fh = fopen($langFolder . '/en.php', "w")) {
					fwrite($fh, "<?php\n\nglobal \$lang;\n\n" . $mst . "\n?>");			
					fclose($fh);
					echo "Created file: $langFolder/en.php<br />";
		
				} else {
					die("Cannot write language file! Please check permissions of $langFolder/en.php");
				}
			}
		}
	}

	/**
	 * Helper function that searches for potential files to be parsed
	 * 
	 * @param string $folder base directory to scan (will scan recursively)
	 * @param array $fileList Array where potential files will be added to
	 */
	private static function getFilesRec($folder, &$fileList) {
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php') $fileList[substr($item,0,-4)] = "$folder/$item";
			else if(substr($item,-3) == '.ss') $fileList[$item] = "$folder/$item";
			else if(is_dir("$folder/$item")) i18n::getFilesRec("$folder/$item", $fileList);
		}
	}
	
	/**
	 * Look for calls to the underscore function in php files and build our MST 
	 * 
	 * @param string $index Classname used to namespace strings 
	 * @param string $file Path to the file to be parsed
	 * @return string Built Master String Table from this file
	 */
	private static function reportCallsCode($index, $file) {
		static $callMap;
		$content = file_get_contents($file);
		while (ereg('_\(([^$][^,"\']*|"[^,]*"|\'[^,]*\')(,[^$][^,]*)(,[^$][^,)]*)(,[^,)]*)?(,[^)]*)?\)',$content,$regs)) {

			$class = ($regs[1] == '__FILE__' ? $index : $regs[1]);
			$entity = substr($regs[2],2,-1);
			
			if ($callMap[$class.'--'.$entity]) echo "Warning! Redeclaring entity $entity in file $file<br>";

			$mst .= '$lang[\'en\'][\'' . $class . '\'][\'' . substr($regs[2],2,-1) . '\'] = ';
			if ($regs[4]) {
				$mst .= "array(\n\t'" . substr($regs[3],2,-1) . "',\n\t" . substr($regs[4],1);
				if ($regs[5]) $mst .= ",\n\t'" . substr($regs[5],2,-1) . '\''; 
				$mst .= "\n);";
			} else $mst .= '\'' . substr($regs[3],2,-1) . '\';';
			$mst .= "\n";
			$content = str_replace($regs[0],"",$content);

			$callMap[$class.'--'.$entity] = $regs[3];
		}
		
		return $mst;
	}

	/**
	 * Look for calls to the underscore function in template files and build our MST 
	 * Template version - no "class" argument
	 * 
	 * @param string $index Index used to namespace strings 
	 * @param string $file Path to the file to be parsed
	 * @return string Built Master String Table from this file
	 */
	private static function reportCallsTpl($index, $file) {
		static $callMap;
		$content = file_get_contents($file);
		while (ereg('_\(([^$][^,"\']*|"[^,]*"|\'[^,]*\')(,[^$][^,)]*)(,[^,)]*)?(,[^)]*)?\)',$content,$regs)) {

			$entity = substr($regs[1],2,-1);
			
			if ($callMap[$index.'--'.$entity]) echo "Warning! Redeclaring entity $entity in file $file<br>";

			$mst .= '$lang[\'en\'][\'' . $index . '\'][\'' . substr($regs[1],1,-1) . '\'] = ';
			if ($regs[3]) {
				$mst .= "array(\n\t'" . substr($regs[2],2,-1) . "',\n\t" . substr($regs[3],1);
				if ($regs[4]) $mst .= ",\n\t'" . substr($regs[4],2,-1) . '\''; 
				$mst .= "\n);";
			} else $mst .= '\'' . substr($regs[2],2,-1) . '\';';
			$mst .= "\n";
			$content = str_replace($regs[0],"",$content);

			$callMap[$index.'--'.$entity] = $regs[3];
		}
		
		return $mst;
	}

	/**
	 * Set the current locale
	 * See http://unicode.org/cldr/data/diff/supplemental/languages_and_territories.html for a list of possible locales
	 * 
	 * @param string $locale Locale to be set 
	 */
	static function setLocale($locale) {
		if ($locale) i18n::$currentlocale = $locale;
	}

	/**
	 * Get the current locale
	 * 
	 * @return string Current locale in the system
	 */
	static function getLocale() {
		return i18n::$currentlocale;
	}
	
	/**
	 * Includes all available language files for a certain defined locale
	 * 
	 * @param string $locale All resources from any module in locale $locale will be loaded
	 */
	static function includeByLocale($locale) {
		if (file_exists($file = Director::getAbsFile("cms/lang/$locale.php"))) include_once($file);
		$topLevel = array_diff(scandir(Director::baseFolder()),array('cms'));
		foreach($topLevel as $module) {
			if (file_exists($file = Director::getAbsFile("$module/lang/$locale.php"))) { 
				include_once($file);
			}
		}
	}
	
	/**
	 * Given a class name (a "locale namespace"), will search for its module and, if available,
	 * will load the resources for the currently defined locale.
	 * If not available, the original English resource will be loaded instead (to avoid blanks)
	 * 
	 * @param string $class Resources for this class will be included, according to the set locale.
	 */
	static function includeByClass($class) {
		if (substr($class,-3) == '.ss') {
			global $_TEMPLATE_MANIFEST;
			$path = current($_TEMPLATE_MANIFEST[substr($class,0,-3)]);
			ereg('.*/([^/]+)/templates/',$path,$module);
		}
		else {
			global $_CLASS_MANIFEST;
			$path = $_CLASS_MANIFEST[$class];
			ereg('.*/([^/]+)/code/',$path,$module);
		}
		if (file_exists($file = Director::getAbsFile("{$module[1]}/lang/". i18n::getLocale() . '.php'))) {
			include_once($file);
		} else if (i18n::getLocale() != 'en') {
			i18n::setLocale('en');
			i18n::includeByClass($class);
		} else {
			user_error("Locale file $file should exist", E_USER_WARNING);
		}
	}
}

?>