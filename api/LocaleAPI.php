<?php

class LocaleAPI extends Controller {
	static $currentlocale = 'en';
	
	static function textCollector() {
	
		echo "Collecting text...<br /><br />";
		
		//Calculate base directory
		$baseDir = Director::baseFolder();

		//Search for and process existent modules, or use the passed one instead
		if (!isset($_GET['module'])) {
			$topLevel = scandir($baseDir);
			foreach($topLevel as $module) {
				LocaleAPI::processModule($baseDir, $module);
			}
		} else {
			LocaleAPI::processModule($baseDir, $_GET['module']);
		}
		
		echo "Done!";
	
	}

	private static function processModule($baseDir, $module) {	
		if(is_dir("$baseDir/$module") && !in_array($module, array('sapphire','jsparty','assets')) && substr($module,0,1) != '.') {
			LocaleAPI::getFilesRec("$baseDir/$module/code", $fileList);
			foreach($fileList as $index => $file) {
				$mst .= LocaleAPI::reportCallsCode($index, $file);
			}
			$fileList = NULL;
			LocaleAPI::getFilesRec("$baseDir/$module/templates", $fileList);
			foreach($fileList as $index => $file) {
				$mst .= LocaleAPI::reportCallsTpl($index, $file);
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

	private static function getFilesRec($folder, &$fileList) {
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php') $fileList[substr($item,0,-4)] = "$folder/$item";
			else if(substr($item,-3) == '.ss') $fileList[$item] = "$folder/$item";
			else if(is_dir("$folder/$item")) LocaleAPI::getFilesRec("$folder/$item", $fileList);
		}
	}
	
	/**
	 * Look for calls to the underscore function and build our MST 
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
	 * Look for calls to the underscore function and build our MST 
	 * Template version - no "class" argument
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

	static function setLocale($locale) {
		if ($locale) LocaleAPI::$currentlocale = $locale;
	}
	static function getLocale() {
		return LocaleAPI::$currentlocale;
	}
	
	/**
	 * Includes all available language files for a certain defined locale
	 */
	static function includeByLocale($locale) {
		$topLevel = scandir(Director::baseFolder());
		foreach($topLevel as $module) {
			if (file_exists($file = Director::getAbsFile("$module/lang/$locale.php"))) { 
				include_once($file);
			}
		}
	}
	
	/**
	 * Given a class name (a "locale namespace"), will search for its module and, if available,
	 * will load the resources for the currently defined locale.
	 * If not available, the original english resource will be loaded instead (to avoid blanks)
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
		}//die($class);
		if (file_exists($file = Director::getAbsFile("{$module[1]}/lang/". LocaleAPI::getLocale() . '.php'))) {
			include_once($file);
		} else if (LocaleAPI::getLocale() != 'en') {
			LocaleAPI::setLocale('en');
			LocaleAPI::includeByClass($class);
		} else {
			user_error("Locale file $file should exist", E_USER_WARNING);
		}
	}
}

?>