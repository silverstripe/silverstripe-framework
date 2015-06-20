<?php
/**
 * A utility class which builds a manifest of all classes, interfaces and some
 * additional items present in a directory, and caches it.
 *
 * It finds the following information:
 *   - Class and interface names and paths.
 *   - All direct and indirect descendants of a class.
 *   - All implementors of an interface.
 *   - All module configuration files.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ClassManifest {

	const CONF_FILE = '_config.php';
	const CONF_DIR = '_config';

	protected $base;
	protected $tests;
	protected $cache;
	protected $cacheKey;

	protected $classes      = array();
	protected $roots        = array();
	protected $children     = array();
	protected $descendants  = array();
	protected $interfaces   = array();
	protected $implementors = array();
	protected $configs      = array();
	protected $configDirs   = array();

	/**
	 * @return TokenisedRegularExpression
	 */
	public static function get_class_parser() {
		return new TokenisedRegularExpression(array(
			0 => T_CLASS,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'can_jump_to' => array(7, 14), 'save_to' => 'className'),
			3 => T_WHITESPACE,
			4 => T_EXTENDS,
			5 => T_WHITESPACE,
			6 => array(T_STRING, 'save_to' => 'extends[]', 'can_jump_to' => 14),
			7 => T_WHITESPACE,
			8 => T_IMPLEMENTS,
			9 => T_WHITESPACE,
			10 => array(T_STRING, 'can_jump_to' => 14, 'save_to' => 'interfaces[]'),
			11 => array(T_WHITESPACE, 'optional' => true),
			12 => array(',', 'can_jump_to' => 10, 'save_to' => 'interfaces[]'),
			13 => array(T_WHITESPACE, 'can_jump_to' => 10),
			14 => array(T_WHITESPACE, 'optional' => true),
			15 => '{',
		));
	}

	/**
	 * @return TokenisedRegularExpression
	 */
	public static function get_namespaced_class_parser() {
		return new TokenisedRegularExpression(array(
			0 => T_CLASS,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'can_jump_to' => array(8, 16), 'save_to' => 'className'),
			3 => T_WHITESPACE,
			4 => T_EXTENDS,
			5 => T_WHITESPACE,
			6 => array(T_NS_SEPARATOR, 'save_to' => 'extends[]', 'optional' => true),
			7 => array(T_STRING, 'save_to' => 'extends[]', 'can_jump_to' => array(6, 16)),
			8 => T_WHITESPACE,
			9 => T_IMPLEMENTS,
			10 => T_WHITESPACE,
			11 => array(T_NS_SEPARATOR, 'save_to' => 'interfaces[]', 'optional' => true),
			12 => array(T_STRING, 'can_jump_to' => array(11, 16), 'save_to' => 'interfaces[]'),
			13 => array(T_WHITESPACE, 'optional' => true),
			14 => array(',', 'can_jump_to' => 11, 'save_to' => 'interfaces[]'),
			15 => array(T_WHITESPACE, 'can_jump_to' => 11),
			16 => array(T_WHITESPACE, 'optional' => true),
			17 => '{',
		));
	}

	/**
	 * @return TokenisedRegularExpression
	 */
	public static function get_namespace_parser() {
		return new TokenisedRegularExpression(array(
			0 => T_NAMESPACE,
			1 => T_WHITESPACE,
			2 => array(T_NS_SEPARATOR, 'save_to' => 'namespaceName[]', 'optional' => true),
			3 => array(T_STRING, 'save_to' => 'namespaceName[]', 'can_jump_to' => 2),
			4 => array(T_WHITESPACE, 'optional' => true),
			5 => ';',
		));
	}

	/**
	 * @return TokenisedRegularExpression
	 */
	public static function get_interface_parser() {
		return new TokenisedRegularExpression(array(
			0 => T_INTERFACE,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'save_to' => 'interfaceName')
		));
	}

	/**
	 * Create a {@link TokenisedRegularExpression} that extracts the namespaces imported with the 'use' keyword
	 *
	 * This searches symbols for a `use` followed by 1 or more namespaces which are optionally aliased using the `as`
	 * keyword. The relevant matching tokens are added one-by-one into an array (using `save_to` param).
	 *
	 * eg: use Namespace\ClassName as Alias, OtherNamespace\ClassName;
	 *
	 * @return TokenisedRegularExpression
	 */
	public static function get_imported_namespace_parser() {
		return new TokenisedRegularExpression(array(
			0 => T_USE,
			1 => T_WHITESPACE,
			2 => array(T_NS_SEPARATOR, 'save_to' => 'importString[]', 'optional' => true),
			3 => array(T_STRING, 'save_to' => 'importString[]', 'can_jump_to' => array(2, 8)),
			4 => array(T_WHITESPACE, 'save_to' => 'importString[]'),
			5 => array(T_AS, 'save_to' => 'importString[]'),
			6 => array(T_WHITESPACE, 'save_to' => 'importString[]'),
			7 => array(T_STRING, 'save_to' => 'importString[]'),
			8 => array(T_WHITESPACE, 'optional' => true),
			9 => array(',', 'save_to' => 'importString[]', 'optional' => true, 'can_jump_to' => 2),
			10 => array(T_WHITESPACE, 'optional' => true, 'can_jump_to' => 2),
			11 => ';',
		));
	}

	/**
	 * Constructs and initialises a new class manifest, either loading the data
	 * from the cache or re-scanning for classes.
	 *
	 * @param string $base The manifest base path.
	 * @param bool   $includeTests Include the contents of "tests" directories.
	 * @param bool   $forceRegen Force the manifest to be regenerated.
	 * @param bool   $cache If the manifest is regenerated, cache it.
	 */
	public function __construct($base, $includeTests = false, $forceRegen = false, $cache = true) {
		$this->base  = $base;
		$this->tests = $includeTests;

		$cacheClass = defined('SS_MANIFESTCACHE') ? SS_MANIFESTCACHE : 'ManifestCache_File';

		$this->cache = new $cacheClass('classmanifest'.($includeTests ? '_tests' : ''));
		$this->cacheKey = 'manifest';

		if (!$forceRegen && $data = $this->cache->load($this->cacheKey)) {
			$this->classes      = $data['classes'];
			$this->descendants  = $data['descendants'];
			$this->interfaces   = $data['interfaces'];
			$this->implementors = $data['implementors'];
			$this->configs      = $data['configs'];
			$this->configDirs   = $data['configDirs'];
		} else {
			$this->regenerate($cache);
		}
	}

	/**
	 * Returns the file path to a class or interface if it exists in the
	 * manifest.
	 *
	 * @param  string $name
	 * @return string|null
	 */
	public function getItemPath($name) {
		$name = strtolower($name);

		if (isset($this->classes[$name])) {
			return $this->classes[$name];
		} elseif (isset($this->interfaces[$name])) {
			return $this->interfaces[$name];
		}
	}

	/**
	 * Returns a map of lowercased class names to file paths.
	 *
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * Returns a lowercase array of all the class names in the manifest.
	 *
	 * @return array
	 */
	public function getClassNames() {
		return array_keys($this->classes);
	}

	/**
	 * Returns an array of all the descendant data.
	 *
	 * @return array
	 */
	public function getDescendants() {
		return $this->descendants;
	}

	/**
	 * Returns an array containing all the descendants (direct and indirect)
	 * of a class.
	 *
	 * @param  string|object $class
	 * @return array
	 */
	public function getDescendantsOf($class) {
		if (is_object($class)) {
			$class = get_class($class);
		}

		$lClass = strtolower($class);

		if (array_key_exists($lClass, $this->descendants)) {
			return $this->descendants[$lClass];
		} else {
			return array();
		}
	}

	/**
	 * Returns a map of lowercased interface names to file locations.
	 *
	 * @return array
	 */
	public function getInterfaces() {
		return $this->interfaces;
	}

	/**
	 * Returns a map of lowercased interface names to the classes the implement
	 * them.
	 *
	 * @return array
	 */
	public function getImplementors() {
		return $this->implementors;
	}

	/**
	 * Returns an array containing the class names that implement a certain
	 * interface.
	 *
	 * @param  string $interface
	 * @return array
	 */
	public function getImplementorsOf($interface) {
		$interface = strtolower($interface);

		if (array_key_exists($interface, $this->implementors)) {
			return $this->implementors[$interface];
		} else {
			return array();
		}
	}

	/**
	 * Returns an array of paths to module config files.
	 *
	 * @return array
	 */
	public function getConfigs() {
		return $this->configs;
	}

	/**
	 * Returns an array of module names mapped to their paths.
	 *
	 * "Modules" in SilverStripe are simply directories with a _config.php
	 * file.
	 *
	 * @return array
	 */
	public function getModules() {
		$modules = array();

		if($this->configs) {
			foreach($this->configs as $configPath) {
				$modules[basename(dirname($configPath))] = dirname($configPath);
			}
		}

		if($this->configDirs) {
			foreach($this->configDirs as $configDir) {
				$path = preg_replace('/\/_config$/', '', dirname($configDir));
				$modules[basename($path)] = $path;
			}
		}

		return $modules;
	}

	/**
	 * Used to set up files that we want to exclude from parsing for performance reasons.
	 */
	protected function setDefaults()
	{
		$this->classes['sstemplateparser'] = FRAMEWORK_PATH.'/view/SSTemplateParser.php';
		$this->classes['sstemplateparseexception'] = FRAMEWORK_PATH.'/view/SSTemplateParser.php';
	}

	/**
	 * Completely regenerates the manifest file.
	 *
	 * @param bool $cache Cache the result.
	 */
	public function regenerate($cache = true) {
		$reset = array(
			'classes', 'roots', 'children', 'descendants', 'interfaces',
			'implementors', 'configs', 'configDirs'
		);

		// Reset the manifest so stale info doesn't cause errors.
		foreach ($reset as $reset) {
			$this->$reset = array();
		}

		$this->setDefaults();

		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/^(_config.php|[^_].*\.php)$/',
			'ignore_files'  => array('index.php', 'main.php', 'cli-script.php', 'SSTemplateParser.php'),
			'ignore_tests'  => !$this->tests,
			'file_callback' => array($this, 'handleFile'),
			'dir_callback' => array($this, 'handleDir')
		));
		$finder->find($this->base);

		foreach ($this->roots as $root) {
			$this->coalesceDescendants($root);
		}

		if ($cache) {
			$data = array(
				'classes'      => $this->classes,
				'descendants'  => $this->descendants,
				'interfaces'   => $this->interfaces,
				'implementors' => $this->implementors,
				'configs'      => $this->configs,
				'configDirs'   => $this->configDirs
			);
			$this->cache->save($data, $this->cacheKey);
		}
	}

	public function handleDir($basename, $pathname, $depth) {
		if ($basename == self::CONF_DIR) {
			$this->configDirs[] = $pathname;
		}
	}

	/**
	 * Find a the full namespaced declaration of a class (or interface) from a list of candidate imports
	 *
	 * This is typically used to determine the full class name in classes that have imported namesapced symbols (having
	 * used the `use` keyword)
	 *
	 * NB: remember the '\\' is an escaped backslash and is interpreted as a single \
	 *
	 * @param string $class The class (or interface) name to find in the candidate imports
	 * @param string $namespace The namespace that was declared for the classes definition (if there was one)
	 * @param array $imports The list of imported symbols (Classes or Interfaces) to test against
	 *
	 * @return string The fully namespaced class name
	 */
	protected function findClassOrInterfaceFromCandidateImports($class, $namespace = '', $imports = array()) {

		//normalise the namespace
		$namespace = rtrim($namespace, '\\');

		//by default we'll use the $class as our candidate
		$candidateClass = $class;

		if (!$class) {
			return $candidateClass;
		}
		//if the class starts with a \ then it is explicitly in the global namespace and we don't need to do
		// anything else
		if (substr($class, 0, 1) == '\\') {
			$candidateClass = substr($class, 1);
			return $candidateClass;
		}
		//if there's a namespace, starting assumption is the class is defined in that namespace
		if ($namespace) {
			$candidateClass = $namespace . '\\' . $class;
		}

		if (empty($imports)) {
			return $candidateClass;
		}

		//normalised class name (PHP is case insensitive for symbols/namespaces
		$lClass = strtolower($class);

		//go through all the imports and see if the class exists within one of them
		foreach ($imports as $alias => $import) {
			//normalise import
			$import = trim($import, '\\');

			//if there is no string key, then there was no declared alias - we'll use the main declaration
			if (is_int($alias)) {
				$alias = strtolower($import);
			} else {
				$alias = strtolower($alias);
			}

			//exact match? Then it's a class in the global namespace that was imported OR it's an alias of
			// another namespace
			// or if it ends with the \ClassName then it's the class we are looking for
			if ($lClass == $alias
				|| substr_compare(
					$alias,
					'\\' . $lClass,
					strlen($alias) - strlen($lClass) - 1,
					// -1 because the $lClass length is 1 longer due to \
					strlen($alias)
				) === 0
			) {
				$candidateClass = $import;
				break;
			}
		}
		return $candidateClass;
	}

	/**
	 * Return an array of array($alias => $import) from tokenizer's tokens of a PHP file
	 *
	 * NB: If there is no alias we don't set a key to the array
	 *
	 * @param array $tokens The parsed tokens from tokenizer's parsing of a PHP file
	 *
	 * @return array The array of imports as (optional) $alias => $import
	 */
	protected function getImportsFromTokens($tokens) {
		//parse out the imports
		$imports = self::get_imported_namespace_parser()->findAll($tokens);

		//if there are any imports, clean them up
		// imports come to us as array('importString' => array([array of matching tokens]))
		// we need to join this nested array into a string and split out the alias and the import
		if (!empty($imports)) {
			$cleanImports = array();
			foreach ($imports as $import) {
				if (!empty($import['importString'])) {
					//join the array up into a string
					$importString = implode('', $import['importString']);
					//split at , to get each import declaration
					$importSet = explode(',', $importString);
					foreach ($importSet as $importDeclaration) {
						//split at ' as ' (any case) to see if we are aliasing the namespace
						$importDeclaration = preg_split('/\s+as\s+/i', $importDeclaration);
						//shift off the fully namespaced import
						$qualifiedImport = array_shift($importDeclaration);
						//if there are still items in the array, it's the alias
						if (!empty($importDeclaration)) {
							$cleanImports[array_shift($importDeclaration)] = $qualifiedImport;
						}
						else {
							$cleanImports[] = $qualifiedImport;
						}
					}
				}
			}
			$imports = $cleanImports;
		}
		return $imports;
	}

	public function handleFile($basename, $pathname, $depth) {
		if ($basename == self::CONF_FILE) {
			$this->configs[] = $pathname;
			return;
		}

		$classes    = null;
		$interfaces = null;
		$namespace = null;
		$imports = null;

		// The results of individual file parses are cached, since only a few
		// files will have changed and TokenisedRegularExpression is quite
		// slow. A combination of the file name and file contents hash are used,
		// since just using the datetime lead to problems with upgrading.
		$file = file_get_contents($pathname);
		$key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $basename) . '_' . md5($file);

		if ($data = $this->cache->load($key)) {
			$valid = (
				isset($data['classes']) && is_array($data['classes'])
				&& isset($data['interfaces']) && is_array($data['interfaces'])
				&& isset($data['namespace']) && is_string($data['namespace'])
				&& isset($data['imports']) && is_array($data['imports'])
			);

			if ($valid) {
				$classes = $data['classes'];
				$interfaces = $data['interfaces'];
				$namespace = $data['namespace'];
				$imports = $data['imports'];
			}
		}

		if (!$classes) {
			$tokens     = token_get_all($file);

			$classes = self::get_namespaced_class_parser()->findAll($tokens);

			$namespace = self::get_namespace_parser()->findAll($tokens);

			if($namespace) {
				$namespace = implode('', $namespace[0]['namespaceName']);
			} else {
				$namespace = '';
			}

			$imports = $this->getImportsFromTokens($tokens);

			$interfaces = self::get_interface_parser()->findAll($tokens);

			$cache = array(
				'classes' => $classes,
				'interfaces' => $interfaces,
				'namespace' => $namespace,
				'imports' => $imports
			);
			$this->cache->save($cache, $key);
		}

		foreach ($classes as $class) {
			$name = $class['className'];
			if ($namespace) {
				$namespace = rtrim($namespace, '\\');
				$name = $namespace . '\\' . $name;
			}
			$extends = isset($class['extends']) ? implode('', $class['extends']) : null;
			$implements = isset($class['interfaces']) ? $class['interfaces'] : null;

			if ($extends) {
				$extends = $this->findClassOrInterfaceFromCandidateImports($extends, $namespace, $imports);
			}

			if (!empty($implements)) {
				//join all the tokens
				$implements = implode('', $implements);
				//split at comma
				$implements = explode(',', $implements);
				//normalise interfaces
				foreach ($implements as &$interface) {
					$interface = $this->findClassOrInterfaceFromCandidateImports($interface, $namespace, $imports);
				}
				//release the var name
				unset($interface);
			}

			$lowercaseName = strtolower($name);
			if (array_key_exists($lowercaseName, $this->classes)) {
				throw new Exception(sprintf(
					'There are two files containing the "%s" class: "%s" and "%s"',
					$name, $this->classes[$lowercaseName], $pathname
				));
			}

			$this->classes[$lowercaseName] = $pathname;

			if ($extends) {
				$extends = strtolower($extends);

				if (!isset($this->children[$extends])) {
					$this->children[$extends] = array($name);
				} else {
					$this->children[$extends][] = $name;
				}
			} else {
				$this->roots[] = $name;
			}

			if ($implements) {
				foreach ($implements as $interface) {
					$interface = strtolower($interface);

					if (!isset($this->implementors[$interface])) {
						$this->implementors[$interface] = array($name);
					} else {
						$this->implementors[$interface][] = $name;
					}
				}
			}
		}

		$interfaceBase = '';
		if ($namespace) {
			$interfaceBase = $namespace . '\\';
		}
		foreach ($interfaces as $interface) {
			$this->interfaces[strtolower($interfaceBase . $interface['interfaceName'])] = $pathname;
		}
	}

	/**
	 * Recursively coalesces direct child information into full descendant
	 * information.
	 *
	 * @param  string $class
	 * @return array
	 */
	protected function coalesceDescendants($class) {
		$result = array();
		$lClass = strtolower($class);

		if (array_key_exists($lClass, $this->children)) {
			$this->descendants[$lClass] = array();

			foreach ($this->children[$lClass] as $class) {
				$this->descendants[$lClass] = array_merge(
					$this->descendants[$lClass],
					array($class),
					$this->coalesceDescendants($class)
				);
			}

			return $this->descendants[$lClass];
		} else {
			return array();
		}
	}

}
