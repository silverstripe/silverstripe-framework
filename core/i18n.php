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
	 * This static variable is used to store the current defined locale. Default value is 'en_US'
	 */
	static $currentlocale = 'en_US';

	/**
	 * This is the main method to build the master string tables with the original strings.
	 * It will search for existent modules that use the i18n feature, parse the _() calls
	 * and write the resultant files in the lang folder of each module.
	 */	
	static function text_collector() {
	
		if (!Permission::check("ADMIN")) die("You must be an admin to enable text collector mode");
		echo "Collecting text...<br /><br />";
		
		//Calculate base directory
		$baseDir = Director::baseFolder();

		//Search for and process existent modules, or use the passed one instead
		if (!isset($_GET['module'])) {
			$topLevel = scandir($baseDir);
			foreach($topLevel as $module) {
				i18n::process_module($baseDir, $module);
			}
		} else {
			i18n::process_module($baseDir, $_GET['module']);
		}
		
		echo "Done!";
	
	}

	/**
	 * Searches for all the files in a given module
	 *
	 * @param string $baseDir Silverstripe's base directory
	 * @param string $module Module's name
	 */
	private static function process_module($baseDir, $module) {	
		if(is_dir("$baseDir/$module") && !in_array($module, array('sapphire','jsparty','assets')) && substr($module,0,1) != '.') {
			i18n::get_files_rec("$baseDir/$module/code", $fileList);
			$mst = '';
			foreach($fileList as $file) {
				$mst .= i18n::report_calls_code($file);
			}
			$fileList = NULL;
			i18n::get_files_rec("$baseDir/$module/templates", $fileList);
			foreach($fileList as $index => $file) {
				$mst .= i18n::report_calls_tpl($index, $file);
			}
			if ($mst) {
				// Create folder for lang files
				$langFolder = $baseDir . '/' . $module . '/lang';
				if(!file_exists($baseDir. '/' . $module . '/lang')) {
					mkdir($langFolder);
				}
				
				// Open the English file and write the Master String Table
				if($fh = fopen($langFolder . '/en_US.php', "w")) {
					fwrite($fh, "<?php\n\nglobal \$lang;\n\n" . $mst . "\n?>");			
					fclose($fh);
					echo "Created file: $langFolder/en_US.php<br />";
		
				} else {
					die("Cannot write language file! Please check permissions of $langFolder/en_US.php");
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
	private static function get_files_rec($folder, &$fileList) {
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php') $fileList[substr($item,0,-4)] = "$folder/$item";
			else if(substr($item,-3) == '.ss') $fileList[$item] = "$folder/$item";
			else if(is_dir("$folder/$item")) i18n::get_files_rec("$folder/$item", $fileList);
		}
	}
	
	/**
	 * Look for calls to the underscore function in php files and build our MST 
	 * 
	 * @param string $file Path to the file to be parsed
	 * @return string Built Master String Table from this file
	 */
	private static function report_calls_code($file) {
		static $callMap;
		$content = file_get_contents($file);
		$mst = '';
		while (ereg('_t[[:space:]]*\([[:space:]]*("[^,]*"|\\\'[^,]*\\\')[[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\')([[:space:]]*,[[:space:]]*[^,)]*)?([[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))?[[:space:]]*\)', $content, $regs)) {
			$entityParts = explode('.',substr($regs[1],1,-1));
			$entity = array_pop($entityParts);
			$class = implode('.',$entityParts);
			
			if (isset($callMap[$class.'--'.$entity])) echo "Warning! Redeclaring entity $entity in file $file<br>";

			$mst .= '$lang[\'en_US\'][\'' . $class . '\'][\'' . $entity . '\'] = ';
			if ($regs[5]) {
				$mst .= "array(\n\t'" . substr($regs[2],1,-1) . "',\n\t" . substr($regs[5],1);
				if ($regs[6]) {
					if (substr($regs[6],1,1) == '"') $regs[6] = addcslashes($regs[6],'\'');
					$mst .= ",\n\t'" . substr($regs[6],2,-1) . '\''; 
				}
				$mst .= "\n);";
			} else $mst .= '\'' . substr($regs[2],1,-1) . '\';';
			$mst .= "\n";
			$content = str_replace($regs[0],"",$content);

			$callMap[$class.'--'.$entity] = $regs[2];
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
	private static function report_calls_tpl($index, $file) {
		static $callMap;
		$content = file_get_contents($file);
		$mst = '';
		while (ereg('_t[[:space:]]*\([[:space:]]*("[^,]*"|\\\'[^,]*\\\')[[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\')([[:space:]]*,[[:space:]]*[^,)]*)?([[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))?[[:space:]]*\)',$content,$regs)) {

			$entityParts = explode('.',substr($regs[1],1,-1));
			$entity = array_pop($entityParts);
			
			// Entity redeclaration check
			if (isset($callMap[$index.'--'.$entity])) echo "Warning! Redeclaring entity $entity in file $file<br>";

			if (substr($regs[2],0,1) == '"') $regs[2] = addcslashes($regs[2],'\'');
			$mst .= '$lang[\'en_US\'][\'' . $index . '\'][\'' . $entity . '\'] = ';
			if ($regs[5]) {
				$mst .= "array(\n\t'" . substr($regs[2],1,-1) . "',\n\t" . substr($regs[5],1);
				if ($regs[6]) {
					if (substr($regs[6],1,1) == '"') $regs[6] = addcslashes($regs[6],'\'\\');
					$mst .= ",\n\t'" . substr($regs[6],2,-1) . '\''; 
				}
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
	static function set_locale($locale) {
		if ($locale) i18n::$currentlocale = $locale;
	}

	/**
	 * Get the current locale
	 * 
	 * @return string Current locale in the system
	 */
	static function get_locale() {
		return i18n::$currentlocale;
	}
	
	/**
	 * Includes all available language files for a certain defined locale
	 * 
	 * @param string $locale All resources from any module in locale $locale will be loaded
	 */
	static function include_by_locale($locale) {
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
	static function include_by_class($class) {
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
		if (file_exists($file = Director::getAbsFile("{$module[1]}/lang/". i18n::get_locale() . '.php'))) {
			include_once($file);
		} else if (i18n::get_locale() != 'en_US') {
			i18n::set_locale('en_US');
			i18n::include_by_class($class);
		} else {
			user_error("Locale file $file should exist", E_USER_WARNING);
		}
	}
}

?>