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

		$this->cache = SS_Cache::factory('SS_ClassManifest', 'Core', array(
			'automatic_serialization' => true,
			'lifetime' => null
		));
		$this->cacheKey = $this->tests ? 'manifest_tests' : 'manifest';

		if (!$forceRegen && $data = $this->cache->load($this->cacheKey)) {
			$this->classes      = $data['classes'];
			$this->descendants  = $data['descendants'];
			$this->interfaces   = $data['interfaces'];
			$this->implementors = $data['implementors'];
			$this->configs      = $data['configs'];
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
	 * "Modules" in SilverStripe are simply directories with a _config.php file.
	 *
	 * @return array
	 */
	public function getModules() {
		$modules = array();
		foreach($this->configs as $configPath) {
			$modules[basename(dirname($configPath))] = dirname($configPath);
		}
		return $modules;
	}

	/**
	 * Completely regenerates the manifest file.
	 *
	 * @param bool $cache Cache the result.
	 */
	public function regenerate($cache = true) {
		$reset = array(
			'classes', 'roots', 'children', 'descendants', 'interfaces',
			'implementors', 'configs'
		);

		// Reset the manifest so stale info doesn't cause errors.
		foreach ($reset as $reset) {
			$this->$reset = array();
		}

		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/^(_config.php|[^_].*\.php)$/',
			'ignore_files'  => array('index.php', 'main.php', 'cli-script.php'),
			'ignore_tests'  => !$this->tests,
			'file_callback' => array($this, 'handleFile')
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
				'configs'      => $this->configs
			);
			$this->cache->save($data, $this->cacheKey);
		}
	}

	public function handleFile($basename, $pathname, $depth) {
		if ($basename == self::CONF_FILE) {
			$this->configs[] = $pathname;
			return;
		}

		$classes    = null;
		$interfaces = null;
		$namespace = null;

		// The results of individual file parses are cached, since only a few
		// files will have changed and TokenisedRegularExpression is quite
		// slow. A combination of the file name and file contents hash are used,
		// since just using the datetime lead to problems with upgrading.
		$file = file_get_contents($pathname);
		$key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $basename) . '_' . md5($file);

		if ($data = $this->cache->load($key)) {
			$valid = (
				isset($data['classes']) && isset($data['interfaces']) && isset($data['namespace'])
				&& is_array($data['classes']) && is_array($data['interfaces']) && is_string($data['namespace'])
			);

			if ($valid) {
				$classes    = $data['classes'];
				$interfaces = $data['interfaces'];
				$namespace = $data['namespace'];
			}
		}

		if (!$classes) {
			$tokens     = token_get_all($file);
			
			$classes = self::get_namespaced_class_parser()->findAll($tokens);
			$namespace = self::get_namespace_parser()->findAll($tokens);
			if($namespace) {
				$namespace = implode('', $namespace[0]['namespaceName']) . '\\';
			} else {
				$namespace = '';
			}

			$interfaces = self::get_interface_parser()->findAll($tokens);

			$cache = array('classes' => $classes, 'interfaces' => $interfaces, 'namespace' => $namespace);
			$this->cache->save($cache, $key, array('fileparse'));
		}

		foreach ($classes as $class) {
			$name       = $namespace . $class['className'];
			$extends    = isset($class['extends']) ? implode('', $class['extends']) : null;
			$implements = isset($class['interfaces']) ? $class['interfaces'] : null;

			if($extends && $extends[0] != '\\') {
				$extends = $namespace . $extends;
			} elseif($extends) {
				$extends = substr($extends, 1);
			}

			if (array_key_exists($name, $this->classes)) {
				throw new Exception(sprintf(
					'There are two files containing the "%s" class: "%s" and "%s"',
					$name, $this->classes[$name], $pathname
				));
			}

			$this->classes[strtolower($name)] = $pathname;

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
				$interface = $namespace;
				for($i = 0; $i < count($implements); ++$i) {
					if($implements[$i] == ',') {
						$interface = $namespace;
						continue;
					}
					if($implements[$i] == '\\' && $interface == $namespace) {
						$interface = '';
					} else {
						$interface .= $implements[$i];
					}
					if($i == count($implements)-1 || $implements[$i+1] == ',') {
						$interface = strtolower($interface);

						if (!isset($this->implementors[$interface])) {
							$this->implementors[$interface] = array($name);
						} else {
							$this->implementors[$interface][] = $name;
						}
					}
				}
			}
		}

		foreach ($interfaces as $interface) {
			$this->interfaces[strtolower($namespace . $interface['interfaceName'])] = $pathname;
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
