<?php


/**
 * A utility class which builds a manifest of configuration items
 * 
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigManifest {

	/** @var string - The base path used when building the manifest */
	protected $base;

	/** @var string - A string to prepend to all cache keys to ensure all keys are unique to just this $base */
	protected $key;

	/** @var bool - Whether `test` directories should be searched when searching for configuration */
	protected $includeTests;

	/**
	  * All the values needed to be collected to determine the correct combination of fragements for
	  * the current environment.
	  * @var array
	  */
	protected $variantKeySpec = false;

	/**
	 * All the _config.php files. Need to be included every request & can't be cached. Not variant specific.
	 * @var array
	 */
	protected $phpConfigSources = array();

	/**
	 * All the _config/*.yml fragments pre-parsed and sorted in ascending include order. Not variant specific.
	 * @var array
	 */
	protected $yamlConfigFragments = array();

	/**
	 * The calculated config from _config/*.yml, sorted, filtered and merged. Variant specific.
	 * @var array
	 */
	public $yamlConfig = array();

	/**
	 * The variant key state as when yamlConfig was loaded
	 * @var string
	 */
	protected $yamlConfigVariantKey = null;

	/**
	 * @var [callback] A list of callbacks to be called whenever the content of yamlConfig changes
	 */
	protected $configChangeCallbacks = array();

	/**
	 * A side-effect of collecting the _config fragments is the calculation of all module directories, since
	 * the definition of a module is "a directory that contains either a _config.php file or a _config directory
	 * @var array
	 */
	public $modules = array();

	/** Adds a path as a module */
	public function addModule($path) {
		$module = basename($path);
		if (isset($this->modules[$module]) && $this->modules[$module] != $path) {
			user_error("Module ".$module." in two places - ".$path." and ".$this->modules[$module]);
		}
		$this->modules[$module] = $path;
	}

	/** Returns true if the passed module exists */
	public function moduleExists($module) {
		return array_key_exists($module, $this->modules);
	}

	/**
	 * Constructs and initialises a new configuration object, either loading
	 * from the cache or re-scanning for classes.
	 *
	 * @param string $base The project base path.
	 * @param bool   $forceRegen Force the manifest to be regenerated.
	 */
	public function __construct($base, $includeTests = false, $forceRegen = false ) {
		$this->base = $base;
		$this->key = sha1($base).'_';
		$this->includeTests = $includeTests;

		// Get the Zend Cache to load/store cache into
		$this->cache = $this->getCache();

		// Unless we're forcing regen, try loading from cache
		if (!$forceRegen) {
			// The PHP config sources are always needed
			$this->phpConfigSources = $this->cache->load($this->key.'php_config_sources');
			// Get the variant key spec
			$this->variantKeySpec = $this->cache->load($this->key.'variant_key_spec');
		}

		// If we don't have a variantKeySpec (because we're forcing regen, or it just wasn't in the cache), generate it
		if (false === $this->variantKeySpec) {
			$this->regenerate($includeTests);
		}

		// At this point $this->variantKeySpec will always contain something valid, so we can build the variant
		$this->buildYamlConfigVariant();
	}

	/**
	 * Provides a hook for mock unit tests despite no DI
	 * @return Zend_Cache_Frontend
	 */
	protected function getCache()
	{
		return SS_Cache::factory('SS_Configuration', 'Core', array(
			'automatic_serialization' => true,
			'lifetime' => null
		));
	}

	/**
	 * Register a callback to be called whenever the calculated merged config changes
	 *
	 * In some situations the merged config can change - for instance, code in _config.php can cause which Only
	 * and Except fragments match. Registering a callback with this function allows code to be called when
	 * this happens.
	 *
	 * @param callback $callback
	 */
	public function registerChangeCallback($callback) {
		$this->configChangeCallbacks[] = $callback;
	}

	/**
	 * Includes all of the php _config.php files found by this manifest. Called by SS_Config when adding this manifest
	 * @return void
	 */
	public function activateConfig() {
		foreach ($this->phpConfigSources as $config) {
			require_once $config;
		}

		if ($this->variantKey() != $this->yamlConfigVariantKey) $this->buildYamlConfigVariant();
	}

	/**
	 * Gets the (merged) config value for the given class and config property name
	 *
	 * @param string $class - The class to get the config property value for
	 * @param string $name - The config property to get the value for
	 * @param any $default - What to return if no value was contained in any YAML file for the passed $class and $name
	 * @return any - The merged set of all values contained in all the YAML configuration files for the passed
	 * $class and $name, or $default if there are none
	 */
	public function get($class, $name, $default=null) {
		if (isset($this->yamlConfig[$class][$name])) return $this->yamlConfig[$class][$name];
		else return $default;
	}

	/**
	 * Returns the string that uniquely identifies this variant. The variant is the combination of classes, modules,
	 * environment, environment variables and constants that selects which yaml fragments actually make it into the
	 * configuration because of "only"
	 * and "except" rules.
	 * 
	 * @return string
	 */
	public function variantKey() {
		$key = $this->variantKeySpec; // Copy to fill in actual values

		if (isset($key['environment'])) {
			$key['environment'] = Director::isDev() ? 'dev' : (Director::isTest() ? 'test' : 'live');
		}

		if (isset($key['envvars'])) foreach ($key['envvars'] as $variable => $foo) {
			$key['envvars'][$variable] = isset($_ENV[$variable]) ? $_ENV[$variable] : null;
		}

		if (isset($key['constants'])) foreach ($key['constants'] as $variable => $foo) {
			$key['constants'][$variable] = defined($variable) ? constant($variable) : null;
		}

		return sha1(serialize($key));
	}

	/**
	 * Completely regenerates the manifest file. Scans through finding all php _config.php and yaml _config/*.ya?ml
	 * files,parses the yaml files into fragments, sorts them and figures out what values need to be checked to pick
	 * the correct variant.
	 *
	 * Does _not_ build the actual variant
	 *
	 * @param bool $cache Cache the result.
	 */
	public function regenerate($includeTests = false, $cache = true) {
		$this->phpConfigSources = array();
		$this->yamlConfigFragments = array();
		$this->variantKeySpec = array();

		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/(^|[\/\\\\])_config.php$/',
			'ignore_tests'  => !$includeTests,
			'file_callback' => array($this, 'addSourceConfigFile')
		));
		$finder->find($this->base);

		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/\.ya?ml$/',
			'ignore_tests'  => !$includeTests,
			'file_callback' => array($this, 'addYAMLConfigFile')
		));
		$finder->find($this->base);

		$this->prefilterYamlFragments();
		$this->sortYamlFragments();
		$this->buildVariantKeySpec();

		if ($cache) {
			$this->cache->save($this->phpConfigSources, $this->key.'php_config_sources');
			$this->cache->save($this->yamlConfigFragments, $this->key.'yaml_config_fragments');
			$this->cache->save($this->variantKeySpec, $this->key.'variant_key_spec');
		}
	}

	/**
	 * Handle finding a php file. We just keep a record of all php files found, we don't include them
	 * at this stage
	 *
	 * Public so that ManifestFileFinder can call it. Not for general use.
	 */
	public function addSourceConfigFile($basename, $pathname, $depth) {
		$this->phpConfigSources[] = $pathname;
		// Add this module too
		$this->addModule(dirname($pathname));
	}

	/**
	 * Handle finding a yml file. Parse the file by spliting it into header/fragment pairs,
	 * and normalising some of the header values (especially: give anonymous name if none assigned,
	 * splt/complete before and after matchers)
	 *
	 * Public so that ManifestFileFinder can call it. Not for general use.
	 */
	public function addYAMLConfigFile($basename, $pathname, $depth) {
		if (!preg_match('{/([^/]+)/_config/}', $pathname, $match)) return;

		// Keep track of all the modules we've seen
		$this->addModule(dirname(dirname($pathname)));

		// Use the Zend copy of this script to prevent class conflicts when RailsYaml is included
		require_once 'thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/'
			. 'sfYamlParser.php';
		$parser = new sfYamlParser();

		// The base header
		$base = array(
			'module' => $match[1],
			'file' => basename(basename($basename, '.yml'), '.yaml')
		);
		
		// Make sure the linefeeds are all converted to \n, PCRE '$' will not match anything else.
		$fileContents = str_replace(array("\r\n", "\r"), "\n", file_get_contents($pathname));

		// YAML parsers really should handle this properly themselves, but neither spyc nor symfony-yaml do. So we
		// follow in their vein and just do what we need, not what the spec says
		$parts = preg_split('/^---$/m', $fileContents, -1, PREG_SPLIT_NO_EMPTY);

		// If only one document, it's a headerless fragment. So just add it with an anonymous name
		if (count($parts) == 1) {
			$this->yamlConfigFragments[] = $base + array(
				'name' => 'anonymous-1',
				'fragment' => $parser->parse($parts[0])
			);
		}
		// Otherwise it's a set of header/document pairs
		else {
			// If we got an odd number of parts the config file doesn't have a header for every document
			if (count($parts) % 2 != 0) {
				user_error("Configuration file '$pathname' does not have an equal number of headers and config blocks");
			}

			// Step through each pair
			for ($i = 0; $i < count($parts); $i+=2) {
				// Make all the first-level keys of the header lower case
				$header = array_change_key_case($parser->parse($parts[$i]), CASE_LOWER);

				// Assign a name if non assigned already
				if (!isset($header['name'])) $header['name'] = 'anonymous-'.(1+$i/2);

				// Parse & normalise the before and after if present
				foreach (array('before', 'after') as $order) {
					if (isset($header[$order])) {
						// First, splice into parts (multiple before or after parts are allowed, comma separated)
						if (is_array($header[$order])) $orderparts = $header[$order];
						else $orderparts = preg_split('/\s*,\s*/', $header[$order], -1, PREG_SPLIT_NO_EMPTY);

						// For each, parse out into module/file#name, and set any missing to "*"
						$header[$order] = array();
						foreach($orderparts as $part) {
							preg_match('! (?P<module>\*|[^\/#]+)? (\/ (?P<file>\*|\w+))? (\# (?P<fragment>\*|\w+))? !x',
								$part, $match);

							$header[$order][] = array(
								'module' => isset($match['module']) && $match['module'] ? $match['module'] : '*',
								'file' => isset($match['file']) && $match['file'] ? $match['file'] : '*',
								'name' => isset($match['fragment'])  && $match['fragment'] ? $match['fragment'] : '*'
							);
						}
					}
				}

				// And add to the fragments list
				$this->yamlConfigFragments[] = $base + $header + array(
					'fragment' => $parser->parse($parts[$i+1])
				);
			}
		}	
	}

	/**
	 * Sorts the YAML fragments so that the "before" and "after" rules are met.
	 * Throws an error if there's a loop
	 * 
	 * We can't use regular sorts here - we need a topological sort. Easiest
	 * way is with a DAG, so build up a DAG based on the before/after rules, then
	 * sort that.
	 * 
	 * @return void
	 */
	protected function sortYamlFragments() {
		$frags = $this->yamlConfigFragments;

		// Build a directed graph
		$dag = new SS_DAG($frags);

		foreach ($frags as $i => $frag) {
			foreach ($frags as $j => $otherfrag) {
				if ($i == $j) continue;

				$order = $this->relativeOrder($frag, $otherfrag);

				if ($order == 'before') $dag->addedge($i, $j);
				elseif ($order == 'after') $dag->addedge($j, $i);
			}
		}

		try {
			$this->yamlConfigFragments = $dag->sort();
		}
		catch (SS_DAG_CyclicException $e) {

			if (!Director::isLive() && isset($_REQUEST['debug'])) {
				$res = '<h1>Remaining config fragment graph</h1>';
				$res .= '<dl>';

				foreach ($e->dag as $node) {
					$res .= "<dt>{$node['from']['module']}/{$node['from']['file']}#{$node['from']['name']}"
						. " marked to come after</dt><dd><ul>";
					foreach ($node['to'] as $to) {
						$res .= "<li>{$to['module']}/{$to['file']}#{$to['name']}</li>";
					}
					$res .= "</ul></dd>";
				}

				$res .= '</dl>';
				echo $res;
			}

			user_error('Based on their before & after rules two fragments both need to be before/after each other',
				E_USER_ERROR);
		}

	}
	
	/**
	 * Return a string "after", "before" or "undefined" depending on whether the YAML fragment array element passed
	 * as $a should be positioned after, before, or either compared to the YAML fragment array element passed as $b
	 *  
	 * @param  $a Array - a YAML config fragment as loaded by addYAMLConfigFile
	 * @param  $b Array - a YAML config fragment as loaded by addYAMLConfigFile
	 * @return string "after", "before" or "undefined"
	 */
	protected function relativeOrder($a, $b) {
		$matches = array();

		// Do the same thing for after and before
		foreach (array('before', 'after') as $rulename) {
			$matches[$rulename] = array();

			// Figure out for each rule, which part matches
			if (isset($a[$rulename])) foreach ($a[$rulename] as $rule) {
				$match = array();

				foreach(array('module', 'file', 'name') as $part) {
					// If part is *, we match _unless_ the opposite rule has a non-* matcher than also matches $b
					if ($rule[$part] == '*') {
						$match[$part] = 'wild';
					}
					else {
						$match[$part] = ($rule[$part] == $b[$part]);
					}
				}

				$matches[$rulename][] = $match;
			}
		}

		// Figure out the specificness of each match. 1 an actual match, 0 for a wildcard match, remove if no match
		$matchlevel = array('before' => -1, 'after' => -1);

		foreach (array('before', 'after') as $rulename) {
			foreach ($matches[$rulename] as $i => $rule) {
				$level = 0;

				foreach ($rule as $part => $partmatches) {
					if ($partmatches === false) continue 2;
					if ($partmatches === true) $level += 1;
				}

				if ($matchlevel[$rulename] === false || $level > $matchlevel[$rulename]) {
					$matchlevel[$rulename] = $level;
				}
			}
		}

		if ($matchlevel['before'] === -1 && $matchlevel['after'] === -1) {
			return 'undefined';
		}
		else if ($matchlevel['before'] === $matchlevel['after']) {
			user_error('Config fragment requires itself to be both before _and_ after another fragment', E_USER_ERROR);
		}
		else {
			return ($matchlevel['before'] > $matchlevel['after']) ? 'before' : 'after';
		}
	}

	/**
	 * This function filters the loaded yaml fragments, removing any that can't ever have their "only" and "except"
	 * rules match.
	 *
	 * Some tests in "only" and "except" rules need to be checked per request, but some are manifest based -
	 * these are invariant over requests and only need checking on manifest rebuild. So we can prefilter these before
	 * saving yamlConfigFragments to speed up the process of checking the per-request variant/
	 */
	public function prefilterYamlFragments() {
		$matchingFragments = array();

		foreach ($this->yamlConfigFragments as $i => $fragment) {
			$matches = true;

			if (isset($fragment['only'])) {
				$matches = $matches && ($this->matchesPrefilterVariantRules($fragment['only']) !== false);
			}

			if (isset($fragment['except'])) {
				$matches = $matches && ($this->matchesPrefilterVariantRules($fragment['except']) !== true);
			}

			if ($matches) $matchingFragments[] = $fragment;
		}

		$this->yamlConfigFragments = $matchingFragments;
	}

	/**
	 * Returns false if the prefilterable parts of the rule aren't met, and true if they are
	 *
	 * @param  $rules array - A hash of rules as allowed in the only or except portion of a config fragment header
	 * @return bool - True if the rules are met, false if not. (Note that depending on whether we were passed an
	 *                only or an except rule,
	 * which values means accept or reject a fragment 
	 */
	public function matchesPrefilterVariantRules($rules) {
		$matches = "undefined"; // Needs to be truthy, but not true

		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
					$matches = $matches && ClassInfo::exists($v);
					break;

				case 'moduleexists':
					$matches = $matches && $this->moduleExists($v);
					break;
				
				default:
					// NOP
			}

			if ($matches === false) return $matches;
		}

		return $matches;
	}

	/**
	 * Builds the variant key spec - the list of values that need to be build to give a key that uniquely identifies
	 * this variant.
	 */
	public function buildVariantKeySpec() {
		$this->variantKeySpec = array();

		foreach ($this->yamlConfigFragments as $fragment) {
			if (isset($fragment['only'])) $this->addVariantKeySpecRules($fragment['only']);
			if (isset($fragment['except'])) $this->addVariantKeySpecRules($fragment['except']);
		}
	}

	/**
	 * Adds any variables referenced in the passed rules to the $this->variantKeySpec array
	 */
	public function addVariantKeySpecRules($rules) {
		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					// Classes and modules are a special case - we can pre-filter on config regenerate because we
					// already know if the class or module exists
					break;

				case 'environment':
					$this->variantKeySpec['environment'] = true;
					break;

				case 'envvarset':
					if (!isset($this->variantKeySpec['envvars'])) $this->variantKeySpec['envvars'] = array();
					$this->variantKeySpec['envvars'][$k] = $k;
					break;

				case 'constantdefined':
					if (!isset($this->variantKeySpec['constants'])) $this->variantKeySpec['constants'] = array();
					$this->variantKeySpec['constants'][$k] = $k;
					break;

				default:
					if (!isset($this->variantKeySpec['envvars'])) $this->variantKeySpec['envvars'] = array();
					if (!isset($this->variantKeySpec['constants'])) $this->variantKeySpec['constants'] = array();
					$this->variantKeySpec['envvars'][$k] = $this->variantKeySpec['constants'][$k] = $k;
			}
		}
	}

	/**
	 * Calculates which yaml config fragments are applicable in this variant, and merge those all together into
	 * the $this->yamlConfig propperty
	 *
	 * Checks cache and takes care of loading yamlConfigFragments if they aren't already present, but expects
	 * $variantKeySpec to already be set
	 */
	public function buildYamlConfigVariant($cache = true) {
		// Only try loading from cache if we don't have the fragments already loaded, as there's no way to know if a
		// given variant is stale compared to the complete set of fragments
		if (!$this->yamlConfigFragments) {
			// First try and just load the exact variant
			if ($this->yamlConfig = $this->cache->load($this->key.'yaml_config_'.$this->variantKey())) {
				$this->yamlConfigVariantKey = $this->variantKey();
				return;
			}
			// Otherwise try and load the fragments so we can build the variant
			else {
				$this->yamlConfigFragments = $this->cache->load($this->key.'yaml_config_fragments');
			}
		}

		// If we still don't have any fragments we have to build them
		if (!$this->yamlConfigFragments) {
			$this->regenerate($this->includeTests, $cache);
		}

		$this->yamlConfig = array();
		$this->yamlConfigVariantKey = $this->variantKey();

		foreach ($this->yamlConfigFragments as $i => $fragment) {
			$matches = true;

			if (isset($fragment['only'])) {
				$matches = $matches && ($this->matchesVariantRules($fragment['only']) !== false);
			}

			if (isset($fragment['except'])) {
				$matches = $matches && ($this->matchesVariantRules($fragment['except']) !== true);
			}

			if ($matches) $this->mergeInYamlFragment($this->yamlConfig, $fragment['fragment']);
		}

		if ($cache) {
			$this->cache->save($this->yamlConfig, $this->key.'yaml_config_'.$this->variantKey());
		}

		// Since yamlConfig has changed, call any callbacks that are interested
		foreach ($this->configChangeCallbacks as $callback) call_user_func($callback);
	}

	/**
 	 * Returns false if the non-prefilterable parts of the rule aren't met, and true if they are
 	 */
	public function matchesVariantRules($rules) {
		$matches = "undefined"; // Needs to be truthy, but not true

		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					break;

				case 'environment':
					switch (strtolower($v)) {
						case 'live':
							$matches = $matches && Director::isLive();
							break;
						case 'test':
							$matches = $matches && Director::isTest();
							break;
						case 'dev':
							$matches = $matches && Director::isDev();
							break;
						default:
							user_error('Unknown environment '.$v.' in config fragment', E_USER_ERROR);
					}
					break;

				case 'envvarset':
					$matches = $matches && isset($_ENV[$v]);
					break;

				case 'constantdefined':
					$matches = $matches && defined($v);
					break;

				default:
					$matches = $matches && (
						(isset($_ENV[$k]) && $_ENV[$k] == $v) ||
						(defined($k) && constant($k) == $v)
					);
					break;
			}

			if ($matches === false) return $matches;
		}

		return $matches;
	}

	/**
	 * Recursively merge a yaml fragment's configuration array into the primary merged configuration array.
	 * @param  $into
	 * @param  $fragment
	 * @return void
	 */
	public function mergeInYamlFragment(&$into, $fragment) {
		foreach ($fragment as $k => $v) {
			Config::merge_high_into_low($into[$k], $v);
		}
	}

}
