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
 * e.g. framework/lang/en_US.php.
 * 
 * The collector needs to be run whenever you make new translatable
 * entities available. Please don't alter the arrays in language tables manually.
 * 
 * Usage through URL: http://localhost/dev/tasks/i18nTextCollectorTask
 * Usage through URL (module-specific): http://localhost/dev/tasks/i18nTextCollectorTask/?module=mymodule
 * Usage on CLI: sake dev/tasks/i18nTextCollectorTask
 * Usage on CLI (module-specific): sake dev/tasks/i18nTextCollectorTask module=mymodule
 * 
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * @author Ingo Schommer <FIRSTNAME@silverstripe.com>
 * @package framework
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

	public $baseSavePath;

	/**
	 * @var i18nTextCollector_Writer
	 */
	protected $writer;
	
	/**
	 * @param $locale
	 */
	function __construct($locale = null) {
		$this->defaultLocale = ($locale) ? $locale : i18n::get_lang_from_locale(i18n::default_locale());
		$this->basePath = Director::baseFolder();
		$this->baseSavePath = Director::baseFolder();
		
		parent::__construct();
	}

	public function setWriter($writer) {
		$this->writer = $writer;
	}

	public function getWriter() {
		if(!$this->writer) $this->writer = new i18nTextCollector_Writer_RailsYaml();
		return $this->writer;
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
		
		$modules = scandir($this->basePath);
		$themeFolders = array();
		
		// A master string tables array (one mst per module)
		$entitiesByModule = array();
		
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
				if(isset($spec[2]) && $spec[2] && $spec[2] != $module) {
					$othermodule = $spec[2];
					if(!isset($entitiesByModule[$othermodule])) $entitiesByModule[$othermodule] = array();
					unset($spec[2]);
					$entitiesByModule[$othermodule][$fullName] = $spec;
					unset($entitiesByModule[$module][$fullName]);
				}
			}			
		}

		// Restrict modules we update to just the specified ones (if any passed)
		if($restrictToModules && count($restrictToModules)) {
			foreach (array_diff(array_keys($entitiesByModule), $restrictToModules) as $module) {
				unset($entitiesByModule[$module]);
			}
		}

		// Write each module language file
		if($entitiesByModule) foreach($entitiesByModule as $module => $entities) {
			$this->getWriter()->write($entities, $this->defaultLocale, $this->baseSavePath . '/' . $module);
		}
	}
	
	/**
	 * Builds a master string table from php and .ss template files for the module passed as the $module param
	 * @see collectFromCode() and collectFromTemplate()
	 *
	 * @param string $module A module's name or just 'themes'
	 * @return array $entities An array of entities found in the files that comprise the module
	 * @todo Why the type juggling for $this->collectFromBlah()? They always return arrays.
	 */
	protected function processModule($module) {	
		$entities = array();

		// Search for calls in code files if these exists
		$fileList = array();
		if(is_dir("$this->basePath/$module/code")) {
			$fileList = $this->getFilesRecursive("$this->basePath/$module/code");
		} else if($module == FRAMEWORK_DIR || substr($module, 0, 7) == 'themes/') {
			// framework doesn't have the usual module structure, so we'll scan all subfolders
			$fileList = $this->getFilesRecursive("$this->basePath/$module");
		}
		foreach($fileList as $filePath) {
			// exclude ss-templates, they're scanned separately
			if(substr($filePath,-3) == 'php') {
				$content = file_get_contents($filePath);
				$entities = array_merge($entities,(array)$this->collectFromCode($content, $module));
				$entities = array_merge($entities, (array)$this->collectFromEntityProviders($filePath, $module));
			}
		}
		
		// Search for calls in template files if these exists
		if(is_dir("$this->basePath/$module/")) {
			$dummy = array();
			$fileList = $this->getFilesRecursive("$this->basePath/$module/", $dummy, 'ss');
			foreach($fileList as $index => $filePath) {
				$content = file_get_contents($filePath);
				// templates use their filename as a namespace
				$namespace = basename($filePath);
				$entities = array_merge($entities, (array)$this->collectFromTemplate($content, $module, $namespace));
			}
		}

		// sort for easier lookup and comparison with translated files
		ksort($entities);

		return $entities;
	}

	/**
	 * Extracts translatables from .php files.
	 * 
	 * @param string $content The text content of a parsed template-file
	 * @param string $module Module's name or 'themes'
	 * @return array $entities An array of entities representing the extracted translation function calls in code
	 */		
	public function collectFromCode($content, $module) {
		$entities = array();

		$tokens = token_get_all("<?php\n" . $content);
		$inTransFn = false;
		$inConcat = false;
		$finalTokenDueToArray = false;
		$currentEntity = array();
		foreach($tokens as $token) {
			if(is_array($token)) {
				list($id, $text) = $token;

				if($inTransFn && $id == T_ARRAY) {
					//raw 'array' token found in _t function, stop processing the tokens for this _t now
					$finalTokenDueToArray = true;
				}

				if($id == T_STRING && $text == '_t') {
					// start definition
					$inTransFn = true;
				} elseif($inTransFn && $id == T_VARIABLE) {
					// Dynamic definition from provideEntities - skip
					$inTransFn = false;
					$inConcat = false;
					$currentEntity = array();
				} elseif($inTransFn && $id == T_CONSTANT_ENCAPSED_STRING) {
					// Fixed quoting escapes, and remove leading/trailing quotes
					if(preg_match('/^\'/', $text)) {
						$text = str_replace("\'", "'", $text);
						$text = preg_replace('/^\'/', '', $text);
						$text = preg_replace('/\'$/', '', $text);
					} else {
						$text = str_replace('\"', '"', $text);
						$text = preg_replace('/^"/', '', $text);
						$text = preg_replace('/"$/', '', $text);
					}

					if($inConcat) {
						$currentEntity[count($currentEntity)-1] .= $text;
					} else {
						$currentEntity[] = $text;
					}
				}
			} elseif($inTransFn && $token == '.') {
				$inConcat = true;
			} elseif($inTransFn && $token == ',') {
				$inConcat = false;
			} elseif($inTransFn && ($token == ')' || $finalTokenDueToArray)) {
				// finalize definition
				$inTransFn = false;
				$inConcat = false;
				$entity = array_shift($currentEntity);
				$entities[$entity] = $currentEntity;
				$currentEntity = array();
				$finalTokenDueToArray = false;
			}
		}

		foreach($entities as $entity => $spec) {
			// call without master language definition
			if(!$spec) {
				unset($entities[$entity]);
				continue;
			}

			unset($entities[$entity]);
			$entities[$this->normalizeEntity($entity, $module)] = $spec;
		}
		ksort($entities);

		return $entities;
	}

	/**
	 * Extracts translatables from .ss templates (Self referencing)
	 * 
	 * @param string $content The text content of a parsed template-file
	 * @param string $module Module's name or 'themes'
	 * @param string $fileName The name of a template file when method is used in self-referencing mode
	 * @return array $entities An array of entities representing the extracted template function calls
	 * 
	 * @todo Why the type juggling for $this->collectFromTemplate()? It always returns an array.
	 */	
	public function collectFromTemplate($content, $fileName, $module) {
		$entities = array();
		
		// Search for included templates
		preg_match_all('/<' . '% include +([A-Za-z0-9_]+) +%' . '>/', $content, $regs, PREG_SET_ORDER);
		foreach($regs as $reg) {
			$includeName = $reg[1];
			$includeFileName = "{$includeName}.ss";
			$filePath = SSViewer::getTemplateFileByType($includeName, 'Includes');
			if(!$filePath) $filePath = SSViewer::getTemplateFileByType($includeName, 'main');
			if($filePath) {
				$includeContent = file_get_contents($filePath);
				$entities = array_merge($entities,(array)$this->collectFromTemplate($includeContent, $module, $includeFileName));
			}
			// @todo Will get massively confused if you include the includer -> infinite loop
		}

		// use parser to extract <%t style translatable entities
		$translatables = i18nTextCollector_Parser::GetTranslatables($content);
		$entities = array_merge($entities,(array)$translatables);

		// use the old method of getting _t() style translatable entities
		// Collect in actual template
		if(preg_match_all('/(_t\([^\)]*?\))/ms', $content, $matches)) {
			foreach($matches[1] as $match) {
				$entities = array_merge($entities, $this->collectFromCode($match, $module));
			}
		}

		foreach($entities as $entity => $spec) {
			unset($entities[$entity]);
			$entities[$this->normalizeEntity($entity, $module)] = $spec;
		}
		ksort($entities);

		return $entities;
	}
	
	/**
	 * @uses i18nEntityProvider
	 */
	function collectFromEntityProviders($filePath, $module = null) {
		$entities = array();

		// HACK Ugly workaround to avoid "Cannot redeclare class PHPUnit_Framework_TestResult" error
		// when running text collector with PHPUnit 3.4. There really shouldn't be any dependencies
		// here, but the class reflection enforces autloading of seemingly unrelated classes.
		// The main problem here is the CMSMenu class, which iterates through test classes,
		// which in turn trigger autoloading of PHPUnit.
		$phpunitwrapper = PhpUnitWrapper::inst();
		$phpunitwrapper->init();
		
		$classes = ClassInfo::classes_for_file($filePath);
		if($classes) foreach($classes as $class) {
			// Not all classes can be instanciated without mandatory arguments,
			// so entity collection doesn't work for all SilverStripe classes currently
			// Requires PHP 5.1+
			if(class_exists($class) && in_array('i18nEntityProvider', class_implements($class))) {
				$reflectionClass = new ReflectionClass($class);
				if($reflectionClass->isAbstract()) continue;

				$obj = singleton($class);
				$entities = array_merge($entities,(array)$obj->provideI18nEntities());
			}
		}
		
		ksort($entities);
		return $entities;
	}
	
	/**
	 * Normalizes enitities with namespaces.
	 * 
	 * @param string $fullName
	 * @param string $_namespace 
	 * @return string|boolean FALSE
	 */
	protected function normalizeEntity($fullName, $_namespace = null) {
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
		if($entity && strpos('$', $entity) !== FALSE) return false;
		
		return "{$namespace}.{$entity}";
	}
	
	
	
	/**
	 * Helper function that searches for potential files (templates and code) to be parsed
	 * 
	 * @param string $folder base directory to scan (will scan recursively)
	 * @param array $fileList Array to which potential files will be appended
	 * @param string $type Optional, "php" or "ss"
	 * @return array $fileList An array of files
	 */
	protected function getFilesRecursive($folder, &$fileList = null, $type = null) {
		if(!$fileList) $fileList = array();
		$items = scandir($folder);
		$isValidFolder = (
			!in_array('_manifest_exclude', $items)
			&& !preg_match('/\/tests$/', $folder)
		);

		if($items && $isValidFolder) foreach($items as $item) {
			if(substr($item,0,1) == '.') continue;
			if(substr($item,-4) == '.php' && (!$type || $type == 'php')) {
				$fileList[substr($item,0,-4)] = "$folder/$item";
			}
			else if(substr($item,-3) == '.ss' && (!$type || $type == 'ss')) {
				$fileList[$item] = "$folder/$item";
			}
			else if(is_dir("$folder/$item")) $this->getFilesRecursive("$folder/$item", $fileList, $type);
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
 * Allows serialization of entity definitions collected through {@link i18nTextCollector}
 * into a persistent format, usually on the filesystem.
 */
interface i18nTextCollector_Writer {
	/**
	 * @param Array $entities Map of entity names (incl. namespace) to an numeric array,
	 * with at least one element, the original string, and an optional second element, the context.
	 * @param String $locale
	 * @param String $path The directory base on which the collector should create new lang folders and files.
	 * Usually the webroot set through {@link Director::baseFolder()}. Can be overwritten for testing or export purposes.
	 * @return Boolean success
	 */
	function write($entities, $locale, $path);
}

/**
 * Legacy writer for 2.x style persistence.
 */
class i18nTextCollector_Writer_Php implements i18nTextCollector_Writer {

	public function write($entities, $locale, $path) {
		$php = '';
		$eol = PHP_EOL;
		
		// Create folder for lang files
		$langFolder = $path . '/lang';
		if(!file_exists($langFolder)) {
			Filesystem::makeFolder($langFolder, Filesystem::$folder_create_mask);
			touch($langFolder . '/_manifest_exclude');
		}

		// Open the English file and write the Master String Table
		$langFile = $langFolder . '/' . $locale . '.php';
		if($fh = fopen($langFile, "w")) {
			if($entities) foreach($entities as $fullName => $spec) {
				$php .= $this->langArrayCodeForEntitySpec($fullName, $spec, $locale);
			}
			// test for valid PHP syntax by eval'ing it
			try{
				eval($php);
			} catch(Exception $e) {
				throw new LogicException('i18nTextCollector->writeMasterStringFile(): Invalid PHP language file. Error: ' . $e->toString());
			}
			
			fwrite($fh, "<"."?php{$eol}{$eol}global \$lang;{$eol}{$eol}" . $php . "{$eol}");
			fclose($fh);
			
		} else {
			throw new LogicException("Cannot write language file! Please check permissions of $langFolder/" . $locale . ".php");
		}

		return true;
	}

	/**
	 * Input for langArrayCodeForEntitySpec() should be suitable for insertion
	 * into single-quoted strings, so needs to be escaped already.
	 * 
	 * @param string $entity The entity name, e.g. CMSMain.BUTTONSAVE
	 */
	public function langArrayCodeForEntitySpec($entityFullName, $entitySpec, $locale) {
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
		$comment = (isset($entitySpec[1])) ? addcslashes($entitySpec[1],'\'') : null;

		$php .= '$lang[\'' . $locale . '\'][\'' . $namespace . '\'][\'' . $entity . '\'] = ';
		$php .= (count($entitySpec) == 1) ? var_export($entitySpec[0], true) : var_export($entitySpec, true);
		$php .= ";$eol";
		
		return $php;
	}
}

/**
 * Writes files compatible with {@link i18nRailsYamlAdapter}.
 */
class i18nTextCollector_Writer_RailsYaml implements i18nTextCollector_Writer {

	public function write($entities, $locale, $path) {
		$content = '';

		// Create folder for lang files
		$langFolder = $path . '/lang';
		if(!file_exists($langFolder)) {
			Filesystem::makeFolder($langFolder, Filesystem::$folder_create_mask);
			touch($langFolder . '/_manifest_exclude');
		}

		// Open the English file and write the Master String Table
		$langFile = $langFolder . '/' . $locale . '.yml';
		if($fh = fopen($langFile, "w")) {
			fwrite($fh, $this->getYaml($entities,$locale));
			fclose($fh);
		} else {
			throw new LogicException("Cannot write language file! Please check permissions of $langFile");
		}

		return true;
	}

	public function getYaml($entities, $locale) {
		// Use the Zend copy of this script to prevent class conflicts when RailsYaml is included
		require_once 'thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/sfYamlDumper.php';

		// Unflatten array
		$entitiesNested = array();
		foreach($entities as $entity => $spec) {
			// Legacy support: Don't count *.ss as namespace
			$entity = preg_replace('/\.ss\./', '___ss.', $entity);
			$parts = explode('.', $entity);
			$currLevel = &$entitiesNested;
			while($part = array_shift($parts)) {
				$part = str_replace('___ss', '.ss', $part);
				if(!isset($currLevel[$part])) $currLevel[$part] = array();
				$currLevel = &$currLevel[$part];
			}
			$currLevel = $spec[0];
		}

		// Write YAML
		$yamlHandler = new sfYaml();
		// TODO Dumper can't handle YAML comments, so the context information is currently discarded
		return $yamlHandler->dump(array($locale => $entitiesNested), 99);
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