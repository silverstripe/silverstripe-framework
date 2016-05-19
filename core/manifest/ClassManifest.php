<?php

use SilverStripe\Manifest\NodeVisitor\SilverStripeNodeVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

/**
 * A utility class which builds a manifest of all classes, interfaces and some
 * additional items present 	in a directory, and caches it.
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
	 * @var \PhpParser\Parser
	 */
	private $parser;

	/**
	 * @var NodeTraverser
	 */
	private $traverser;

	/**
	 * @var SilverStripeNodeVisitor
	 */
	private $visitor;

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

	public function getParser() {
		if (!$this->parser) {
			$this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
		}
		return $this->parser;
	}

	public function getTraverser() {
		if (!$this->traverser) {
			$this->traverser = new NodeTraverser;
			$this->traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
			$this->traverser->addVisitor($this->getVisitor());
		}
		return $this->traverser;
	}

	public function getVisitor() {
		if (!$this->visitor) {
			$this->visitor = new SilverStripeNodeVisitor;
		}
		return $this->visitor;
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

	public function handleFile($basename, $pathname, $depth) {
		if ($basename == self::CONF_FILE) {
			$this->configs[] = $pathname;
			return;
		}

		$classes    = null;
		$interfaces = null;
		$traits = null;
		$valid = false;

		// The results of individual file parses are cached, since only a few
		// files will have changed and TokenisedRegularExpression is quite
		// slow. A combination of the file name and file contents hash are used,
		// since just using the datetime lead to problems with upgrading.
		$key  = preg_replace('/[^a-zA-Z0-9_]/', '_', $basename) . '_' . md5_file($pathname);

		if ($data = $this->cache->load($key)) {
			$valid = (
				isset($data['classes']) && is_array($data['classes'])
				&& isset($data['interfaces']) && is_array($data['interfaces'])
				&& isset($data['traits']) && is_array($data['traits'])
			);

			if ($valid) {
				$classes = $data['classes'];
				$interfaces = $data['interfaces'];
				$traits = $data['traits'];
			}
		}

		if (!$valid) {
			$stmts = $this->getParser()->parse(file_get_contents($pathname));
			$this->getTraverser()->traverse($stmts);

			$classes = $this->getVisitor()->getClasses();
			$interfaces = $this->getVisitor()->getInterfaces();
			$traits = $this->getVisitor()->getTraits();

			$cache = array(
				'classes' => $classes,
				'interfaces' => $interfaces,
				'traits' => $traits,
			);
			$this->cache->save($cache, $key);
		}

		foreach ($classes as $className => $classInfo) {
			$extends = isset($classInfo['extends']) ? $classInfo['extends'] : null;
			$implements = isset($classInfo['interfaces']) ? $classInfo['interfaces'] : null;

			$lowercaseName = strtolower($className);
			if (array_key_exists($lowercaseName, $this->classes)) {
				throw new Exception(sprintf(
					'There are two files containing the "%s" class: "%s" and "%s"',
					$className, $this->classes[$lowercaseName], $pathname
				));
			}

			$this->classes[$lowercaseName] = $pathname;

			if ($extends) {
				foreach ($extends as $ancestor) {
					$ancestor = strtolower($ancestor);

					if (!isset($this->children[$ancestor])) {
						$this->children[$ancestor] = array($className);
					}
					else {
						$this->children[$ancestor][] = $className;
					}
				}
			} else {
				$this->roots[] = $className;
			}

			if ($implements) {
				foreach ($implements as $interface) {
					$interface = strtolower($interface);

					if (!isset($this->implementors[$interface])) {
						$this->implementors[$interface] = array($className);
					} else {
						$this->implementors[$interface][] = $className;
					}
				}
			}
		}

		foreach ($interfaces as $interfaceName => $interfaceInfo) {
			$this->interfaces[strtolower($interfaceName)] = $pathname;
		}
		foreach ($traits as $traitName => $traitInfo) {
			$this->traits[strtolower($traitName)] = $pathname;
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
