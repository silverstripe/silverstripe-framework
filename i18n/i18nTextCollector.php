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
 * e.g. framework/lang/en.yml.
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

	/**
	 * Default (master) locale
	 *
	 * @var string
	 */
	protected $defaultLocale;

	/**
	 * The directory base on which the collector should act.
	 * Usually the webroot set through {@link Director::baseFolder()}.
	 *
	 * @todo Fully support changing of basePath through {@link SSViewer} and {@link ManifestBuilder}
	 *
	 * @var string
	 */
	public $basePath;

	/**
	 * Save path
	 *
	 * @var string
	 */
	public $baseSavePath;

	/**
	 * @var i18nTextCollector_Writer
	 */
	protected $writer;

	/**
	 * List of file extensions to parse
	 *
	 * @var array
	 */
	protected $fileExtensions = array('php', 'ss');

	/**
	 * @param $locale
	 */
	public function __construct($locale = null) {
		$this->defaultLocale = $locale
			? $locale
			: i18n::get_lang_from_locale(i18n::default_locale());
		$this->basePath = Director::baseFolder();
		$this->baseSavePath = Director::baseFolder();

		parent::__construct();
	}

	/**
	 * Assign a writer
	 *
	 * @param i18nTextCollector_Writer $writer
	 */
	public function setWriter($writer) {
		$this->writer = $writer;
	}

	/**
	 * Gets the currently assigned writer, or the default if none is specified.
	 *
	 * @return i18nTextCollector_Writer
	 */
	public function getWriter() {
		if(!$this->writer) {
			$this->setWriter(Injector::inst()->get('i18nTextCollector_Writer'));
		}
		return $this->writer;
	}

	/**
	 * This is the main method to build the master string tables with the
	 * original strings. It will search for existent modules that use the
	 * i18n feature, parse the _t() calls and write the resultant files
	 * in the lang folder of each module.
	 *
	 * @uses DataObject->collectI18nStatics()
	 *
	 * @param array $restrictToModules
	 * @param bool $mergeWithExisting Merge new master strings with existing
	 * ones already defined in language files, rather than replacing them.
	 * This can be useful for long-term maintenance of translations across
	 * releases, because it allows "translation backports" to older releases
	 * without removing strings these older releases still rely on.
	 */
	public function run($restrictToModules = null, $mergeWithExisting = false) {
		$entitiesByModule = $this->collect($restrictToModules, $mergeWithExisting);
		if(empty($entitiesByModule)) {
			return;
		}

		// Write each module language file
		foreach($entitiesByModule as $module => $entities) {
			// Skip empty translations
			if(empty($entities)) {
				continue;
			}

			// Clean sorting prior to writing
			ksort($entities);
			$path = $this->baseSavePath . '/' . $module;
			$this->getWriter()->write($entities, $this->defaultLocale, $path);
		}
	}

	/**
	 * Gets the list of modules in this installer
	 *
	 * @param string $directory Path to look in
	 * @return array List of modules as paths relative to base
	 */
	protected function getModules($directory) {
		// Include self as head module
		$modules = array();

		// Get all standard modules
		foreach(glob($directory."/*", GLOB_ONLYDIR) as $path) {
			// Check for _config
			if(!is_file("$path/_config.php") && !is_dir("$path/_config")) {
				continue;
			}
			$modules[] = basename($path);
		}

		// Get all themes
		foreach(glob($directory."/themes/*", GLOB_ONLYDIR) as $path) {
			// Check for templates
			if(is_dir("$path/templates")) {
				$modules[] = 'themes/'.basename($path);
			}
		}

		return $modules;
	}

	/**
	 * Extract all strings from modules and return these grouped by module name
	 *
	 * @param array $restrictToModules
	 * @param bool $mergeWithExisting
	 * @return array
	 */
	public function collect($restrictToModules = array(), $mergeWithExisting = false) {
		$entitiesByModule = $this->getEntitiesByModule();

		// Resolve conflicts between duplicate keys across modules
		$entitiesByModule = $this->resolveDuplicateConflicts($entitiesByModule);

		// Optionally merge with existing master strings
		if($mergeWithExisting) {
			$entitiesByModule = $this->mergeWithExisting($entitiesByModule);
		}

		// Restrict modules we update to just the specified ones (if any passed)
		if(!empty($restrictToModules)) {
			foreach (array_diff(array_keys($entitiesByModule), $restrictToModules) as $module) {
				unset($entitiesByModule[$module]);
			}
		}
		return $entitiesByModule;
	}

	/**
	 * Resolve conflicts between duplicate keys across modules
	 *
	 * @param array $entitiesByModule List of all modules with keys
	 * @return array Filtered listo of modules with duplicate keys unassigned
	 */
	protected function resolveDuplicateConflicts($entitiesByModule) {
		// Find all keys that exist across multiple modules
		$conflicts = $this->getConflicts($entitiesByModule);
		foreach($conflicts as $conflict) {
			// Determine if we can narrow down the ownership
			$bestModule = $this->getBestModuleForKey($entitiesByModule, $conflict);
			if(!$bestModule) {
				continue;
			}

			// Remove foreign duplicates
			foreach($entitiesByModule as $module => $entities) {
				if($module !== $bestModule) {
					unset($entitiesByModule[$module][$conflict]);
				}
			}
		}
		return $entitiesByModule;
	}

	/**
	 * Find all keys in the entity list that are duplicated across modules
	 *
	 * @param array $entitiesByModule
	 * @return array List of keys
	 */
	protected function getConflicts($entitiesByModule) {
		$modules = array_keys($entitiesByModule);
		$allConflicts = array();
		// bubble-compare each group of modules
		for($i = 0; $i < count($modules) - 1; $i++) {
			$left = array_keys($entitiesByModule[$modules[$i]]);
			for($j = $i+1; $j < count($modules); $j++) {
				$right = array_keys($entitiesByModule[$modules[$j]]);
				$conflicts = array_intersect($left, $right);
				$allConflicts = array_merge($allConflicts, $conflicts);
			}
		}
		return array_unique($allConflicts);
	}

	/**
	 * Determine the best module to be given ownership over this key
	 *
	 * @param array $entitiesByModule
	 * @param string $key
	 * @return string Best module, if found
	 */
	protected function getBestModuleForKey($entitiesByModule, $key) {
		// Check classes
		$class = current(explode('.', $key));
		$owner = i18n::get_owner_module($class);
		if($owner) {
			return $owner;
		}

		// @todo - How to determine ownership of templates? Templates can
		// exist in multiple locations with the same name.

		// Display notice if not found
		Debug::message(
			"Duplicate key {$key} detected in multiple modules with no obvious owner",
			false
		);

		// Fall back to framework then cms modules
		foreach(array('framework', 'cms') as $module) {
			if(isset($entitiesByModule[$module][$key])) {
				return $module;
			}
		}

		// Do nothing
		return null;
	}

	/**
	 * Merge all entities with existing strings
	 *
	 * @param array $entitiesByModule
	 * @return array
	 */
	protected function mergeWithExisting($entitiesByModule) {
		// TODO Support all defined source formats through i18n::get_translators().
		//      Currently not possible because adapter instances can't be fully reset through the Zend API,
		//      meaning master strings accumulate across modules
		foreach($entitiesByModule as $module => $entities) {
			$adapter = Injector::inst()->create('i18nRailsYamlAdapter');
			$fileName = $adapter->getFilenameForLocale($this->defaultLocale);
			$masterFile = "{$this->basePath}/{$module}/lang/{$fileName}";
			if(!file_exists($masterFile)) {
				continue;
			}

			$adapter->addTranslation(array(
				'content' => $masterFile,
				'locale' => $this->defaultLocale
			));
			$entitiesByModule[$module] = array_merge(
				array_map(
					// Transform each master string from scalar value to array of strings
					function($v) {return array($v);},
					$adapter->getMessages($this->defaultLocale)
				),
				$entities
			);
		}
		return $entitiesByModule;
	}

	/**
	 * Collect all entities grouped by module
	 *
	 * @return array
	 */
	protected function getEntitiesByModule() {
		// A master string tables array (one mst per module)
		$entitiesByModule = array();
		$modules = $this->getModules($this->basePath);
		foreach($modules as $module) {
			// we store the master string tables
			$processedEntities = $this->processModule($module);
			if(isset($entitiesByModule[$module])) {
				$entitiesByModule[$module] = array_merge_recursive($entitiesByModule[$module], $processedEntities);
			} else {
				$entitiesByModule[$module] = $processedEntities;
			}

			// extract all entities for "foreign" modules (fourth argument)
			// @see CMSMenu::provideI18nEntities for an example usage
			foreach($entitiesByModule[$module] as $fullName => $spec) {
				if(!empty($spec[2]) && $spec[2] !== $module) {
					$othermodule = $spec[2];
					if(!isset($entitiesByModule[$othermodule])) {
						$entitiesByModule[$othermodule] = array();
					}
					unset($spec[2]);
					$entitiesByModule[$othermodule][$fullName] = $spec;
					unset($entitiesByModule[$module][$fullName]);
				}
			}
		}
		return $entitiesByModule;
	}


	public function write($module, $entities) {
		$this->getWriter()->write($entities, $this->defaultLocale, $this->baseSavePath . '/' . $module);
		return $this;
	}

	/**
	 * Builds a master string table from php and .ss template files for the module passed as the $module param
	 * @see collectFromCode() and collectFromTemplate()
	 *
	 * @param string $module A module's name or just 'themes/<themename>'
	 * @return array An array of entities found in the files that comprise the module
	 */
	protected function processModule($module) {
		$entities = array();

		// Search for calls in code files if these exists
		$fileList = $this->getFileListForModule($module);
		foreach($fileList as $filePath) {
			$extension = pathinfo($filePath, PATHINFO_EXTENSION);
			$content = file_get_contents($filePath);
			// Filter based on extension
			if($extension === 'php') {
				$entities = array_merge(
					$entities,
					$this->collectFromCode($content, $module),
					$this->collectFromEntityProviders($filePath, $module)
				);
			} elseif($extension === 'ss') {
				// templates use their filename as a namespace
				$namespace = basename($filePath);
				$entities = array_merge(
					$entities,
					$this->collectFromTemplate($content, $module, $namespace)
				);
			}
		}

		// sort for easier lookup and comparison with translated files
		ksort($entities);

		return $entities;
	}

	/**
	 * Retrieves the list of files for this module
	 *
	 * @param type $module
	 * @return array List of files to parse
	 */
	protected function getFileListForModule($module) {
		$modulePath = "{$this->basePath}/{$module}";

		// Search all .ss files in themes
		if(stripos($module, 'themes/') === 0) {
			return $this->getFilesRecursive($modulePath, null, 'ss');
		}

		// If Framework or non-standard module structure, so we'll scan all subfolders
		if($module === FRAMEWORK_DIR || !is_dir("{$modulePath}/code")) {
			return $this->getFilesRecursive($modulePath);
		}

		// Get code files
		$files = $this->getFilesRecursive("{$modulePath}/code", null, 'php');

		// Search for templates in this module
		if(is_dir("{$modulePath}/templates")) {
			$templateFiles = $this->getFilesRecursive("{$modulePath}/templates", null, 'ss');
		} else {
			$templateFiles = $this->getFilesRecursive($modulePath, null, 'ss');
		}

		return array_merge($files, $templateFiles);
	}

	/**
	 * Extracts translatables from .php files.
	 *
	 * @param string $content The text content of a parsed template-file
	 * @param string $module Module's name or 'themes'. Could also be a namespace
	 * Generated by templates includes. E.g. 'UploadField.ss'
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
			} elseif($inTransFn && ($token == ')' || $finalTokenDueToArray || $token == '[')) {
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
	 */
	public function collectFromTemplate($content, $fileName, $module, &$parsedFiles = array()) {
		// use parser to extract <%t style translatable entities
		$entities = i18nTextCollector_Parser::GetTranslatables($content);

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
	 * Allows classes which implement i18nEntityProvider to provide
	 * additional translation strings.
	 *
	 * Not all classes can be instanciated without mandatory arguments,
	 * so entity collection doesn't work for all SilverStripe classes currently
	 *
	 * @uses i18nEntityProvider
	 * @param string $filePath
	 * @param string $module
	 * @return array
	 */
	public function collectFromEntityProviders($filePath, $module = null) {
		$entities = array();
		$classes = ClassInfo::classes_for_file($filePath);
		foreach($classes as $class) {
			// Skip non-implementing classes
			if(!class_exists($class) || !in_array('i18nEntityProvider', class_implements($class))) {
				continue;
			}

			// Skip abstract classes
			$reflectionClass = new ReflectionClass($class);
			if($reflectionClass->isAbstract()) {
				continue;
			}

			$obj = singleton($class);
			$entities = array_merge($entities, (array)$obj->provideI18nEntities());
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
	 * @param string $type Optional, "php" or "ss" only
	 * @param string $folderExclude Regular expression matching folder names to exclude
	 * @return array $fileList An array of files
	 */
	protected function getFilesRecursive($folder, $fileList = array(), $type = null, $folderExclude = '/\/(tests)$/') {
		if(!$fileList) {
			$fileList = array();
		}
		// Skip ignored folders
		if(is_file("{$folder}/_manifest_exclude") || preg_match($folderExclude, $folder)) {
			return $fileList;
		}

		foreach(glob($folder.'/*') as $path) {
			// Recurse if directory
			if(is_dir($path)) {
				$fileList = array_merge(
					$fileList,
					$this->getFilesRecursive($path, $fileList, $type, $folderExclude)
				);
				continue;
			}

			// Check if this extension is included
			$extension = pathinfo($path, PATHINFO_EXTENSION);
			if(in_array($extension, $this->fileExtensions)
				&& (!$type || $type === $extension)
			) {
				$fileList[$path] = $path;
			}
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
 *
 * @package framework
 * @subpackage i18n
 */
interface i18nTextCollector_Writer {
	/**
	 * @param Array $entities Map of entity names (incl. namespace) to an numeric array, with at least one element,
	 *                        the original string, and an optional second element, the context.
	 * @param String $locale
	 * @param String $path The directory base on which the collector should create new lang folders and files.
	 *                     Usually the webroot set through {@link Director::baseFolder()}. Can be overwritten for
	 *                     testing or export purposes.
	 * @return Boolean success
	 */
	public function write($entities, $locale, $path);
}

/**
 * Legacy writer for 2.x style persistence.
 *
 * @package framework
 * @subpackage i18n
 */
class i18nTextCollector_Writer_Php implements i18nTextCollector_Writer {

	public function write($entities, $locale, $path) {
		$php = '';
		$eol = PHP_EOL;

		// Create folder for lang files
		$langFolder = $path . '/lang';
		if(!file_exists($langFolder)) {
			Filesystem::makeFolder($langFolder);
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
				throw new LogicException(
					'i18nTextCollector->writeMasterStringFile(): Invalid PHP language file. Error: ' . $e->toString());
			}

			fwrite($fh, "<"."?php{$eol}{$eol}global \$lang;{$eol}{$eol}" . $php . "{$eol}");
			fclose($fh);

		} else {
			throw new LogicException("Cannot write language file! Please check permissions of $langFolder/"
				. $locale . ".php");
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
			user_error(
				"i18nTextCollector::langArrayCodeForEntitySpec(): Wrong entity format for $entityFullName with values "
				. var_export($entitySpec, true),
				E_USER_WARNING
			);
			return false;
		}

		$value = $entitySpec[0];
		$comment = (isset($entitySpec[1])) ? addcslashes($entitySpec[1],'\'') : null;

		$php .= '$lang[\'' . $locale . '\'][\'' . $namespace . '\'][\'' . $entity . '\'] = ';
		$php .= (count($entitySpec) == 1) ? var_export($entitySpec[0], true) : var_export($entitySpec, true);
		$php .= ";$eol";

		// Normalise linebreaks due to fix var_export output
		return Convert::nl2os($php, $eol);
	}
}

/**
 * Writes files compatible with {@link i18nRailsYamlAdapter}.
 *
 * @package framework
 * @subpackage i18n
 */
class i18nTextCollector_Writer_RailsYaml implements i18nTextCollector_Writer {

	public function write($entities, $locale, $path) {
		$content = '';

		// Create folder for lang files
		$langFolder = $path . '/lang';
		if(!file_exists($langFolder)) {
			Filesystem::makeFolder($langFolder);
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
		require_once 'thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib'
			. '/sfYamlDumper.php';

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
		$oldVersion = sfYaml::getSpecVersion();
		sfYaml::setSpecVersion('1.1');
		$yamlHandler = new sfYaml();
		// TODO Dumper can't handle YAML comments, so the context information is currently discarded
		$result = $yamlHandler->dump(array($locale => $entitiesNested), 99);
		sfYaml::setSpecVersion($oldVersion);
		return $result;
	}
}

/**
 * Parser that scans through a template and extracts the parameters to the _t and <%t calls
 *
 * @package framework
 * @subpackage i18n
 */
class i18nTextCollector_Parser extends SSTemplateParser {

	private static $entities = array();

	private static $currentEntity = array();

	public function __construct($string) {
		$this->string = $string;
		$this->pos = 0;
		$this->depth = 0;
		$this->regexps = array();
	}

	public function Translate__construct(&$res) {
		self::$currentEntity = array(null,null,null); //start with empty array
	}

	public function Translate_Entity(&$res, $sub) {
		self::$currentEntity[0] = $sub['text']; //entity
	}

	public function Translate_Default(&$res, $sub) {
		self::$currentEntity[1] = $sub['String']['text']; //value
	}

	public function Translate_Context(&$res, $sub) {
		self::$currentEntity[2] = $sub['String']['text']; //comment
	}

	public function Translate__finalise(&$res) {
		// set the entity name and the value (default), as well as the context (comment)
		// priority is no longer used, so that is blank
		self::$entities[self::$currentEntity[0]] = array(self::$currentEntity[1],null,self::$currentEntity[2]);
	}

	/**
	 * Parses a template and returns any translatable entities
	 */
	public static function GetTranslatables($template) {
		self::$entities = array();

		// Run the parser and throw away the result
		$parser = new i18nTextCollector_Parser($template);
		if(substr($template, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
			$parser->pos = 3;
		}
		$parser->match_TopTemplate();

		return self::$entities;
	}
}
