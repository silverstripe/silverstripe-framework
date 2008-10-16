<?php
/**
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * @package sapphire
 * @subpackage misc
 */
class i18nTextCollector extends Object {
	
	protected $defaultLocale;
	
	/**
	 * @param $locale
	 */
	function __construct($locale = null) {
		$this->defaultLocale = ($locale) ? $locale : i18n::default_locale();
		
		parent::__construct();
	}
	
	/**
	 * This is the main method to build the master string tables with the original strings.
	 * It will search for existent modules that use the i18n feature, parse the _t() calls
	 * and write the resultant files in the lang folder of each module.
	 * 
	 * @uses DataObject->collectI18nStatics()
	 */	
	public function run($module = null) {
		if(Director::is_cli()) {
			echo "Collecting text...\n";
		} else {
			echo "Collecting text...<br /><br />";
		}
		
		//Calculate base directory
		$baseDir = Director::baseFolder();

		// A master string tables array (one mst per module)
		$mst = array();
		
		// A list of included templates dependencies
		$includedtpl = array();

		//Search for and process existent modules, or use the passed one instead
		if (!isset($module)) {
			$topLevel = scandir($baseDir);
			foreach($topLevel as $module) {
				// we store the master string tables 
				$processed = $this->processModule($baseDir, $module, $includedtpl);
				if ($processed) $mst[$module] = $processed;
			}
		} else {
			$module = basename($module);
			$processed = $this->processModule($baseDir, $module, $includedtpl);
			if ($processed) $mst[$module] = $processed;
		}
		
		// Write the generated master string tables
		$this->writeMasterStringFile($baseDir, $mst, $includedtpl);
		
		echo "Done!\n";
	}
	
	/**
	 * Build the module's master string table
	 *
	 * @param string $baseDir Silverstripe's base directory
	 * @param string $module Module's name
	 * @return string Generated master string table
	 */
	protected function processModule($baseDir, $module) {	
    	
    	// Only search for calls in folder with a _config.php file (which means they are modules)  
		if(
			is_dir("$baseDir/$module") 
			&& is_file("$baseDir/$module/_config.php") 
			&& substr($module,0,1) != '.'
		) {  
			Debug::message("Processing Module '{$module}'", false);

			$mst = '';
			// Search for calls in code files if these exists
			if(is_dir("$baseDir/$module/code")) {
				$fileList = $this->getFilesRecursive("$baseDir/$module/code");
				foreach($fileList as $file) {
					if(substr($file,-3) == '.php') $mst .= $this->collectFromCode($file);
				}
			} else if('sapphire' == $module) {
				// sapphire doesn't have the usual module structure, so we'll scan all subfolders
				$fileList = $this->getFilesRecursive("$baseDir/$module");
				foreach($fileList as $file) {
					// exclude ss-templates, they're scanned separately
					if(substr($file,-3) == '.php') $mst .= $this->collectFromCode($file);
				}
			}
			
			// Search for calls in template files if these exists
			if(is_dir("$baseDir/$module/templates")) {
				$includedtpl[$module] = array();
				$fileList = $this->getFilesRecursive("$baseDir/$module/templates");
				foreach($fileList as $index => $file) {
					$mst .= $this->collectFromTemplates($index, $file, $includedtpl[$module]);
				}
			}
			
			return $mst;
			
		} else return false;
	}

	/**
	 * Write the master string table of every processed module
	 *
	 * @param string $baseDir Silverstripe's base directory
	 * @param array $allmst Module's master string tables
	 * @param array $includedtpl Templates included by other templates
	 */
	protected function writeMasterStringFile($baseDir, $allmst, $includedtpl) {
		// Evaluate the constructed mst
		foreach($allmst as $mst) eval($mst);

		// Resolve template dependencies
		foreach($includedtpl as $tplmodule => $includers) {
			// Variable initialization
			$stringsCode = '';
			$moduleCode = '';
			$modulestoinclude = array();
			
			foreach($includers as $includertpl => $allincluded) 
				foreach($allincluded as $included)
					// we will only add code if the included template has localizable strings
					if(isset($lang[$this->defaultLocale]["$included.ss"])) {
						$module = i18n::get_owner_module("$included.ss");
						
						/* if the module of the included template is not the same as the includer's one
						 * we will need to load the first one in order to have these included strings in memory
						 */
						if ($module != $tplmodule) $modulestoinclude[$module] = $included;
						
						// Give the includer name to the included strings in order to be used from the includer template
						$stringsCode .= "\$lang['" . $this->defaultLocale . "']['$includertpl'] = " .
								"array_merge(\$lang['" . $this->defaultLocale . "']['$includertpl'], \$lang['" . $this->defaultLocale . "']['$included.ss']);\n";
					}
			
			// Include a template for every needed module (the module language file will then be autoloaded)
			foreach($modulestoinclude as $tpltoinclude) $moduleCode .= "self::include_by_class('$tpltoinclude.ss');\n";
			
			// Add the extra code to the existing module mst
			if ($stringsCode) $allmst[$tplmodule] .= "\n$moduleCode$stringsCode";
		}
		
		// Write each module language file
		foreach($allmst as $module => $mst) {
			// Create folder for lang files
			$langFolder = $baseDir . '/' . $module . '/lang';
			if(!file_exists($baseDir. '/' . $module . '/lang')) {
				mkdir($langFolder, Filesystem::$folder_create_mask);
				touch($baseDir. '/' . $module . '/lang/_manifest_exclude');
			}
			
			// Open the English file and write the Master String Table
			if($fh = fopen($langFolder . '/' . $this->defaultLocale . '.php', "w")) {
				fwrite($fh, "<?php\n\nglobal \$lang;\n\n" . $mst . "\n?>");			
				fclose($fh);
				if(Director::is_cli()) {
					echo "Created file: $langFolder/" . $this->defaultLocale . ".php\n";
				} else {
					echo "Created file: $langFolder/" . $this->defaultLocale . ".php<br />";
				}
	
			} else {
				user_error("Cannot write language file! Please check permissions of $langFolder/" . $this->defaultLocale . ".php", E_USER_ERROR);
			}
		}

	}
	
	/**
	 * Helper function that searches for potential files to be parsed
	 * 
	 * @param string $folder base directory to scan (will scan recursively)
	 * @param array $fileList Array where potential files will be added to
	 */
	protected function getFilesRecursive($folder, &$fileList = null) {
		if(!$fileList) $fileList = array();
		$items = scandir($folder);
		if($items) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php') $fileList[substr($item,0,-4)] = "$folder/$item";
			else if(substr($item,-3) == '.ss') $fileList[$item] = "$folder/$item";
			else if(is_dir("$folder/$item")) $this->getFilesRecursive("$folder/$item", $fileList);
		}
		return $fileList;
	}
	
	/**
	 * Look for calls to the underscore function in php files and build our MST 
	 * 
	 * @param string $file Path to the file to be parsed
	 * @return string Built Master String Table from this file
	 */
	protected function collectFromCode($file) {
		$callMap = array();
		$content = file_get_contents($file);
		$mst = '';
		while (ereg('_t[[:space:]]*\([[:space:]]*("[^"]*"|\\\'[^\']*\\\')[[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\')([[:space:]]*,[[:space:]]*[^,)]*)?([[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))?[[:space:]]*\)', $content, $regs)) {
			$entityParts = explode('.',substr($regs[1],1,-1));
			$entity = array_pop($entityParts);
			$class = implode('.',$entityParts);
			
			if (isset($callMap["$class--$entity"])) 
				echo "Warning! Redeclaring entity $entity in file $file (previously declared in {$callMap["$class--$entity"]})<br>";

			if (substr($regs[2],0,1) == '"') $regs[2] = addcslashes($regs[2],'\'');
			$mst .= '$lang[\'' . $this->defaultLocale . '\'][\'' . $class . '\'][\'' . $entity . '\'] = ';
			if ($regs[5]) {
				$mst .= "array(\n\t'" . substr($regs[2],1,-1) . "',\n\t" . substr($regs[5],1);
				if ($regs[7]) {
					if (substr($regs[7],0,1) == '"') $regs[7] = addcslashes($regs[7],'\'');
					$mst .= ",\n\t'" . substr($regs[7],1,-1) . '\''; 
				}
				$mst .= "\n);";
			} else $mst .= '\'' . substr($regs[2],1,-1) . '\';';
			$mst .= "\n";
			$content = str_replace($regs[0],"",$content);

			$callMap["$class--$entity"] = $file;
		}
		
		return $mst;
	}

	/**
	 * Look for calls to the underscore function in template files and build our MST 
	 * Template version - no "class" argument
	 * 
	 * @param string $index Index used to namespace strings 
	 * @param string $file Path to the file to be parsed
	 * @param string $included List of explicitly included templates
	 * @return string Built Master String Table from this file
	 */
	protected function collectFromTemplates($index, $file, &$included) {
		$callMap = array();
		$content = file_get_contents($file);
		
		// Search for included templates
		preg_match_all('/<' . '% include +([A-Za-z0-9_]+) +%' . '>/', $content, $inc, PREG_SET_ORDER);
		foreach ($inc as $template) {
			if (!isset($included[$index])) $included[$index] = array();
			array_push($included[$index], $template[1]);
		}

		$mst = '';
		while (ereg('_t[[:space:]]*\([[:space:]]*("[^"]*"|\\\'[^\']*\\\')[[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\')([[:space:]]*,[[:space:]]*[^,)]*)?([[:space:]]*,[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))?[[:space:]]*\)',$content,$regs)) {

			$entityParts = explode('.',substr($regs[1],1,-1));
			$entity = array_pop($entityParts);

			// Entity redeclaration check
			if (isset($callMap["$index--$entity"])) 
				echo "Warning! Redeclaring entity $entity in file $file (previously declared in {$callMap["$index--$entity"]})<br>";

			if (substr($regs[2],0,1) == '"') $regs[2] = addcslashes($regs[2],'\'');
			$mst .= '$lang[\'' . $this->defaultLocale . '\'][\'' . $index . '\'][\'' . $entity . '\'] = ';
			if ($regs[5]) {
				$mst .= "array(\n\t'" . substr($regs[2],1,-1) . "',\n\t" . substr($regs[5],1);
				if ($regs[7]) {
					if (substr($regs[7],0,1) == '"') $regs[7] = addcslashes($regs[7],'\'\\');
					$mst .= ",\n\t'" . substr($regs[7],1,-1) . '\''; 
				}
				$mst .= "\n);";
			} else $mst .= '\'' . substr($regs[2],1,-1) . '\';';
			$mst .= "\n";
			$content = str_replace($regs[0],"",$content);

			$callMap["$index--$entity"] = $file;
		}
		
		return $mst;
	}
}
?>