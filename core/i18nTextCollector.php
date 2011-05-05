<?php
/**
 * SilverStripe-variant of the "gettext" tool:
 * Parses the string content of all PHP-files and SilverStripe templates
 * for ocurrences of the _t() translation method. Also uses the {@link i18nEntityProvider}
 * interface to get dynamically defined entities by executing the 
 * {@link provideI18nEntities()} method on all implementors of this interface.
 * 
 * Collects all found entities (and their natural language text for the default locale)
 * into language-files for each module in an array notation. Creates or overwrites these files,
 * e.g. sapphire/lang/en_US.php.
 * 
 * The collector needs to be run whenever you make new translatable
 * entities available. Please don't alter the arrays in language tables manually.
 * 
 * Usage through URL: http://localhost/dev/tasks/i18nTextCollectorTask
 * Usage through URL (module-specific): http://localhost/dev/tasks/i18nTextCollectorTask/?module=mymodule
 * Usage on CLI: sake dev/tasks/i18nTextCollectorTask
 * Usage on CLI (module-specific): sake dev/tasks/i18nTextCollectorTask module=mymodule
 *
 * Requires PHP 5.1+ due to class_implements() limitations
 * 
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * @author Ingo Schommer <FIRSTNAME@silverstripe.com>
 * @package sapphire
 * @subpackage i18n
 * @uses i18nEntityProvider
 * @uses i18n
 */
class i18nTextCollector extends Object {
	
	protected $defaultLocale;
	
	/**
	 * @var string $basePath The directory base on which the collector should act.
	 * Usually the webroot set through {@link Director::baseFolder()}.
	 * @todo Fully support changing of basePath through {@link SSViewer} and {@link ManifestBuilder}
	 */
	public $basePath;
	
	/**
	 * @var string $baseSavePath The directory base on which the collector should create new lang folders and files.
	 * Usually the webroot set through {@link Director::baseFolder()}.
	 * Can be overwritten for testing or export purposes.
	 * @todo Fully support changing of baseSavePath through {@link SSViewer} and {@link ManifestBuilder}
	 */
	public $baseSavePath;
	
	/**
	 * @param $locale
	 */
	function __construct($locale = null) {
		$this->defaultLocale = ($locale) ? $locale : i18n::default_locale();
		$this->basePath = Director::baseFolder();
		$this->baseSavePath = Director::baseFolder();
		
		parent::__construct();
	}
	
	/**
	 * This is the main method to build the master string tables with the original strings.
	 * It will search for existent modules that use the i18n feature, parse the _t() calls
	 * and write the resultant files in the lang folder of each module.
	 * 
	 * @uses DataObject->collectI18nStatics()
	 * 
	 * @param array $restrictToModules
	 */	
	public function run($restrictToModules = null) {
		//Debug::message("Collecting text...", false);
		
		$modules = array();
		$themeFolders = array();
		
		// A master string tables array (one mst per module)
		$entitiesByModule = array();
		
		//Search for and process existent modules, or use the passed one instead
		if($restrictToModules && count($restrictToModules)) {
			foreach($restrictToModules as $restrictToModule) {
				$modules[] = basename($restrictToModule);
			}
		} else {
			$modules = scandir($this->basePath);
		}
		
		foreach($modules as $index => $module){
			if($module != 'themes') continue;
			else {
				$themes = scandir($this->basePath."/themes");
				if(count($themes)){
					foreach($themes as $theme) {
						if(is_dir($this->basePath."/themes/".$theme) && substr($theme,0,1) != '.' && is_dir($this->basePath."/themes/".$theme."/templates")){
							$themeFolders[] = 'themes/'.$theme;
						}
					}
				}
				$themesInd = $index;
			}
		}
		
		if(isset($themesInd)) {
			unset($modules[$themesInd]);
		}
		
		$modules = array_merge($modules, $themeFolders);

		foreach($modules as $module) {
			// Only search for calls in folder with a _config.php file (which means they are modules, including themes folder)  
			$isValidModuleFolder = (
				is_dir("$this->basePath/$module") 
				&& is_file("$this->basePath/$module/_config.php") 
				&& substr($module,0,1) != '.'
			) || (
				substr($module,0,7) == 'themes/'
				&& is_dir("$this->basePath/$module")
			);
			
			if(!$isValidModuleFolder) continue;
			
			// we store the master string tables 
			$processedEntities = $this->processModule($module);

			if(isset($entitiesByModule[$module])) {
				$entitiesByModule[$module] = array_merge_recursive($entitiesByModule[$module], $processedEntities);
			} else {
				$entitiesByModule[$module] = $processedEntities;
			}
			
			// extract all entities for "foreign" modules (fourth argument)
			foreach($entitiesByModule[$module] as $fullName => $spec) {
				if(isset($spec[3]) && $spec[3] && $spec[3] != $module) {
					$othermodule = $spec[3];
					if(!isset($entitiesByModule[$othermodule])) $entitiesByModule[$othermodule] = array();
					unset($spec[3]);
					$entitiesByModule[$othermodule][$fullName] = $spec;
					unset($entitiesByModule[$module][$fullName]);
				}
			}			
		}

		// Write the generated master string tables
		$this->writeMasterStringFile($entitiesByModule);
		
		//Debug::message("Done!", false);
	}
	
	/**
	 * Build the module's master string table
	 *
	 * @param string $module Module's name or 'themes'
	 */
	protected function processModule($module) {	
		$entitiesArr = array();

		//Debug::message("Processing Module '{$module}'", false);

		// Search for calls in code files if these exists
		if(is_dir("$this->basePath/$module/code")) {
			$fileList = $this->getFilesRecursive("$this->basePath/$module/code");
		} else if($module == 'sapphire' || substr($module, 0, 7) == 'themes/') {
			// sapphire doesn't have the usual module structure, so we'll scan all subfolders
			$fileList = $this->getFilesRecursive("$this->basePath/$module");
		}
		foreach($fileList as $filePath) {
			// exclude ss-templates, they're scanned separately
			if(substr($filePath,-3) == 'php') {
				$content = file_get_contents($filePath);
				$entitiesArr = array_merge($entitiesArr,(array)$this->collectFromCode($content, $module));
				$entitiesArr = array_merge($entitiesArr, (array)$this->collectFromEntityProviders($filePath, $module));
			}
		}
		
		// Search for calls in template files if these exists
		if(is_dir("$this->basePath/$module/templates")) {
			$fileList = $this->getFilesRecursive("$this->basePath/$module/templates");
			foreach($fileList as $index => $filePath) {
				$content = file_get_contents($filePath);
				// templates use their filename as a namespace
				$namespace = basename($filePath);
				$entitiesArr = array_merge($entitiesArr, (array)$this->collectFromTemplate($content, $module, $namespace));
			}
		}

		// sort for easier lookup and comparison with translated files
		ksort($entitiesArr);

		return $entitiesArr;
	}
	
	public function collectFromCode($content, $module) {
		$entitiesArr = array();

		$newRegexRule = '/_t\s*\(\s*(.+?)\s*\)\s*;\s*/is';

		$matchesArray = array();    //array for the matches to go into
		if (preg_match_all($newRegexRule, $content, $matchesArray) > 0) {   //we have at least one match

			//take all the matched _t entities
			foreach($matchesArray[1] as $match) {
				//replace all commas with backticks (unique character to explode on later)
				$replacedMatch = preg_replace('/("|\'|_LOW|_MEDIUM|_HIGH)\s*,\s*([\'"]|"|\'|array|PR)/','$1`$2',$match);  //keep array text

				//$replacedMatch = trim($replacedMatch," \"'\n");  //remove starting and ending quotes
				$replacedMatch = trim($replacedMatch," \n");  //remove starting and ending spaces and newlines

				$parts = explode('`',$replacedMatch);   //cut up the _t call

				$partsWOQuotes = array();
				foreach($parts as $part) {
					$part = trim($part,"\n");  //remove spaces and newlines from part

					$firstChar = substr($part,0,1);
					if ($firstChar == "'" || $firstChar == '"') {
						//remove wrapping quotes
						$part = substr($part,1,-1);

						//remove inner concatenation
						$part = preg_replace("/$firstChar\\s*\\.\\s*$firstChar/",'',$part);
					}

					$partsWOQuotes[] = $part;  //remove starting and ending quotes from inner parts
				}
				
				if ($parts && count($partsWOQuotes) > 0) {

					$entitiesArr = array_merge($entitiesArr, (array)$this->entitySpecFromNewParts($partsWOQuotes));
				}
			}
		}

		ksort($entitiesArr);

		return $entitiesArr;
	}

	/**
	 * Test if one string starts with another
	 */
	protected function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * Converts a parts array from explode function into an array of entities for the i18n text collector
	 * @return array
	 */
	protected function entitySpecFromNewParts($parts, $namespace = null) {
		// first thing in the parts array will always be the entity
		// split fullname into entity parts
		//set defaults
		$value = "";
		$prio = null;
		$comment = null;

		$entityParts = explode('.', $parts[0]);
		if(count($entityParts) > 1) {
			// templates don't have a custom namespace
			$entity = array_pop($entityParts);
			// namespace might contain dots, so we explode
			$namespace = implode('.',$entityParts);
		} else {
			$entity = array_pop($entityParts);
			$namespace = $namespace;
		}

		//find the array (if found, then we are dealing with the new _t syntax
		$newSyntax = false;
		$offset = 0;
		foreach($parts as $p) {
			if ($this->startsWith($p,'array')) {    //remove everything after (and including) the array
				$newSyntax = true;
				$parts = array_splice($parts,0,$offset);
				break;
			}
			$offset++;
		}

		//2nd part of array is always "string"
		if (isset($parts[1])) $value = $parts[1];


		//3rd part can either be priority or context, if old or now syntax is used
		if (isset($parts[2])) {
			if ($newSyntax) {
				$prio = 40; //default priority
				$comment = $parts[2];
			} else {
				if (stripos($parts[2], 'PR_LOW') !== false ||
				    stripos($parts[2], 'PR_MEDIUM') !== false ||
				    stripos($parts[2], 'PR_HIGH') !== false) {  //definitely old syntax
					$prio = $parts[2];
				} else {    //default to new syntax (3rd position is comment/context
					$prio = 40; //default priority
					$comment = $parts[2];
				}
			}
		}

		//if 4th position is set then this is old syntax and it is the context
		//it would be array in the new syntax and therefore should have already been spliced off
		if (isset($parts[3])) {
			$comment = $parts[3];
			$prio = $parts[2];  //3rd position is now definitely priority
		}

		return array(
			"{$namespace}.{$entity}" => array(
				$value,
				$prio,
				$comment
			)
		);
	}

	public function collectFromTemplate($content, $module, $fileName) {
		$entitiesArr = array();
		
		// Search for included templates
		preg_match_all('/<' . '% include +([A-Za-z0-9_]+) +%' . '>/', $content, $regs, PREG_SET_ORDER);
		foreach($regs as $reg) {
			$includeName = $reg[1];
			$includeFileName = "{$includeName}.ss";
			$filePath = SSViewer::getTemplateFileByType($includeName, 'Includes');
			if(!$filePath) $filePath = SSViewer::getTemplateFileByType($includeName, 'main');
			if($filePath) {
				$includeContent = file_get_contents($filePath);
				$entitiesArr = array_merge($entitiesArr,(array)$this->collectFromTemplate($includeContent, $module, $includeFileName));
			}
			// @todo Will get massively confused if you include the includer -> infinite loop
		}

		// use parser to extract <%t style translatable entities
		$translatables = i18nTextCollector_Parser::GetTranslatables($content);
		$entitiesArr = array_merge($entitiesArr,(array)$translatables);

		// use the old method of getting _t() style translatable entities
		// @todo respect template tags (< % _t() % > instead of _t())
		$regexRule = '_t[[:space:]]*\(' .
			'[[:space:]]*("[^"]*"|\\\'[^\']*\\\')[[:space:]]*,' . // namespace.entity
			'[[:space:]]*(("([^"]|\\\")*"|\'([^\']|\\\\\')*\')' .  // value
			'([[:space:]]*\\.[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))*)' . // concatenations
			'([[:space:]]*,[[:space:]]*[^,)]*)?([[:space:]]*,' . // priority (optional)
			'[[:space:]]*("([^"]|\\\")*"|\'([^\']|\\\\\')*\'))?[[:space:]]*' . // comment (optional)
		'\)';
		while(ereg($regexRule,$content,$regs)) {
			$entitiesArr = array_merge($entitiesArr,(array)$this->entitySpecFromRegexMatches($regs, $fileName));
			// remove parsed content to continue while() loop
			$content = str_replace($regs[0],"",$content);
		}
		
		ksort($entitiesArr);
		
		return $entitiesArr;
	}
	
	/**
	 * @uses i18nEntityProvider
	 */
	function collectFromEntityProviders($filePath) {
		$entitiesArr = array();
		
		$classes = ClassInfo::classes_for_file($filePath);
		if($classes) foreach($classes as $class) {
			// Not all classes can be instanciated without mandatory arguments,
			// so entity collection doesn't work for all SilverStripe classes currently
			// Requires PHP 5.1+
			if(class_exists($class) && in_array('i18nEntityProvider', class_implements($class))) {
				$reflectionClass = new ReflectionClass($class);
				if($reflectionClass->isAbstract()) continue;

				$obj = singleton($class);
				$entitiesArr = array_merge($entitiesArr,(array)$obj->provideI18nEntities());
			}
		}
		
		ksort($entitiesArr);
		
		return $entitiesArr;
	}
	
	/**
	 * @todo Fix regexes so the deletion of quotes, commas and newlines from wrong matches isn't necessary
	 */
	protected function entitySpecFromRegexMatches($regs, $_namespace = null) {
		// remove wrapping quotes
		$fullName = substr($regs[1],1,-1);
		
		// split fullname into entity parts
		$entityParts = explode('.', $fullName);
		if(count($entityParts) > 1) {
			// templates don't have a custom namespace
			$entity = array_pop($entityParts);
			// namespace might contain dots, so we explode
			$namespace = implode('.',$entityParts); 
		} else {
			$entity = array_pop($entityParts);
			$namespace = $_namespace;
		}
		
		// If a dollar sign is used in the entity name,
		// we can't resolve without running the method,
		// and skip the processing. This is mostly used for
		// dynamically translating static properties, e.g. looping
		// through $db, which are detected by {@link collectFromEntityProviders}.
		if(strpos('$', $entity) !== FALSE) return false;
		
		// remove wrapping quotes
		$value = ($regs[2]) ? substr($regs[2],1,-1) : null;

		$value = ereg_replace("([^\\])['\"][[:space:]]*\.[[:space:]]*['\"]",'\\1',$value);

		// only escape quotes when wrapped in double quotes, to make them safe for insertion
		// into single-quoted PHP code. If they're wrapped in single quotes, the string should
		// be properly escaped already
		if(substr($regs[2],0,1) == '"') {
			// Double quotes don't need escaping
			$value = str_replace('\\"','"', $value);
			// But single quotes do
			$value = str_replace("'","\\'", $value);
		}

		
		// remove starting comma and any newlines
		$eol = PHP_EOL;
		$prio = ($regs[10]) ? trim(preg_replace("/$eol/", '', substr($regs[10],1))) : null;
		
		// remove wrapping quotes
		$comment = ($regs[12]) ? substr($regs[12],1,-1) : null;

		return array(
			"{$namespace}.{$entity}" => array(
				$value,
				$prio,
				$comment
			)
		);
	}
	
	/**
	 * Input for langArrayCodeForEntitySpec() should be suitable for insertion
	 * into single-quoted strings, so needs to be escaped already.
	 * 
	 * @param string $entity The entity name, e.g. CMSMain.BUTTONSAVE
	 */
	public function langArrayCodeForEntitySpec($entityFullName, $entitySpec) {
		$php = '';
		$eol = PHP_EOL;
		
		$entityParts = explode('.', $entityFullName);
		if(count($entityParts) > 1) {
			// templates don't have a custom namespace
			$entity = array_pop($entityParts);
			// namespace might contain dots, so we implode back
			$namespace = implode('.',$entityParts); 
		} else {
			user_error("i18nTextCollector::langArrayCodeForEntitySpec(): Wrong entity format for $entityFullName with values" . var_export($entitySpec, true), E_USER_WARNING);
			return false;
		}
		
		$value = $entitySpec[0];
		$prio = (isset($entitySpec[1])) ? addcslashes($entitySpec[1],'\'') : null;
		$comment = (isset($entitySpec[2])) ? addcslashes($entitySpec[2],'\'') : null;
		
		$php .= '$lang[\'' . $this->defaultLocale . '\'][\'' . $namespace . '\'][\'' . $entity . '\'] = ';
		if ($prio) {
			$php .= "array($eol\t'" . $value . "',$eol\t" . $prio;
			if ($comment) {
				$php .= ",$eol\t'" . $comment . '\''; 
			}
			$php .= "$eol);";
		} else {
			$php .= '\'' . $value . '\';';
		}
		$php .= "$eol";
		
		return $php;
	}
	
	/**
	 * Write the master string table of every processed module
	 */
	protected function writeMasterStringFile($entitiesByModule) {
		// Write each module language file
		if($entitiesByModule) foreach($entitiesByModule as $module => $entities) {
			$php = '';
			$eol = PHP_EOL;
			
			// Create folder for lang files
			$langFolder = $this->baseSavePath . '/' . $module . '/lang';
			if(!file_exists($langFolder)) {
				Filesystem::makeFolder($langFolder, Filesystem::$folder_create_mask);
				touch($langFolder . '/_manifest_exclude');
			}

			// Open the English file and write the Master String Table
			$langFile = $langFolder . '/' . $this->defaultLocale . '.php';
			if($fh = fopen($langFile, "w")) {
				if($entities) foreach($entities as $fullName => $spec) {
					$php .= $this->langArrayCodeForEntitySpec($fullName, $spec);
				}
				
				// test for valid PHP syntax by eval'ing it
				try{
					eval($php);
				} catch(Exception $e) {
					user_error('i18nTextCollector->writeMasterStringFile(): Invalid PHP language file. Error: ' . $e->toString(), E_USER_ERROR);
				}
				
				fwrite($fh, "<"."?php{$eol}{$eol}global \$lang;{$eol}{$eol}" . $php . "{$eol}");
				fclose($fh);
				
				//Debug::message("Created file: $langFolder/" . $this->defaultLocale . ".php", false);
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
		$isValidFolder = (
			!in_array('_manifest_exclude', $items)
			&& !preg_match('/\/tests$/', $folder)
		);

		if($items && $isValidFolder) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php') $fileList[substr($item,0,-4)] = "$folder/$item";
			else if(substr($item,-3) == '.ss') $fileList[$item] = "$folder/$item";
			else if(is_dir("$folder/$item")) $this->getFilesRecursive("$folder/$item", $fileList);
		}
		return $fileList;
	}
	
	public function getDefaultLocale() {
		return $this->defaultLocale;
	}
	
	public function setDefaultLocale($locale) {
		$this->defaultLocale = $locale;
	}
}
/**
 * Parser that scans through a template and extracts the parameters to the _t and <%t calls
 */
class i18nTextCollector_Parser extends SSTemplateParser {

	static $entities = array();
	static $currentEntity = array();

	function Translate__construct(&$res) {
		self::$currentEntity = array(null,null,null); //start with empty array
	}

	function Translate_Entity(&$res, $sub) {
		self::$currentEntity[0] = $sub['text']; //entity
	}

	function Translate_Default(&$res, $sub) {
		self::$currentEntity[1] = $sub['String']['text']; //value
	}

	function Translate_Context(&$res, $sub) {
		self::$currentEntity[2] = $sub['String']['text']; //comment
	}

	function Translate__finalise(&$res) {
		// set the entity name and the value (default), as well as the context (comment)
		// priority is no longer used, so that is blank
		self::$entities[self::$currentEntity[0]] = array(self::$currentEntity[1],null,self::$currentEntity[2]);
	}

	/**
	 * Parses a template and returns any translatable entities
	 */
	static function GetTranslatables($template) {
		self::$entities = array();

		// Run the parser and throw away the result
		$parser = new i18nTextCollector_Parser($template);
		if(substr($template, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) $parser->pos = 3;
		$parser->match_TopTemplate();

		return self::$entities;
	}
}
?>