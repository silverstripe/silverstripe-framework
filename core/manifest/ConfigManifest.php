<?php


/**
 * A utility class which builds a manifest of configuration items
 * 
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigManifest {

	/** @var array All the values needed to be collected to determine the correct combination of fragements for the current environment. */
	protected $variantKeySpec = array();

	/** @var array All the _config.php files. Need to be included every request & can't be cached. Not variant specific. */
	protected $phpConfigSources = array();

	/** @var array All the _config/*.yml fragments pre-parsed and sorted in ascending include order. Not variant specific. */
	protected $yamlConfigFragments = array();

	/** @var array The calculated config from _config/*.yml, sorted, filtered and merged. Variant specific. */
	public $yamlConfig = array();

	/** @var array A side-effect of collecting the _config fragments is the calculation of all module directories, since the definition
	 * of a module is "a directory that contains either a _config.php file or a _config directory */
	public $modules = array();

	/** Adds a path as a module */
	function addModule($path) {
		$module = basename($path);
		if (isset($this->modules[$module]) && $this->modules[$module] != $path) {
			user_error("Module ".$module." in two places - ".$path." and ".$this->modules[$module]);
		}
		$this->modules[$module] = $path;
	}

	/** Returns true if the passed module exists */
	function moduleExists($module) {
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

		// Get the Zend Cache to load/store cache into
		$this->cache = SS_Cache::factory('SS_Configuration', 'Core', array(
			'automatic_serialization' => true,
			'lifetime' => null
		));

		// Unless we're forcing regen, try loading from cache
		if (!$forceRegen) {
			// The PHP config sources are always needed
			$this->phpConfigSources = $this->cache->load('php_config_sources');
			// Get the variant key spec
			$this->variantKeySpec = $this->cache->load('variant_key_spec');
			// Try getting the pre-filtered & merged config for this variant
			if (!($this->yamlConfig = $this->cache->load('yaml_config_'.$this->variantKey()))) {
				// Otherwise, if we do have the yaml config fragments (and we should since we have a variant key spec) work out the config for this variant
				if ($this->yamlConfigFragments = $this->cache->load('yaml_config_fragments')) {
					$this->buildYamlConfigVariant();
				}
			}
		}

		// If we don't have a config yet, we need to do a full regen to get it
		if (!$this->yamlConfig) {
			$this->regenerate($includeTests);
			$this->buildYamlConfigVariant();
		}
	}

	/**
	 * Includes all of the php _config.php files found by this manifest. Called by SS_Config when adding this manifest
	 * @return void
	 */
	public function activateConfig() {
		foreach ($this->phpConfigSources as $config) {
			require_once $config;
		}
	}

	/**
	 * Returns the string that uniquely identifies this variant. The variant is the combination of classes, modules, environment,
	 * environment variables and constants that selects which yaml fragments actually make it into the configuration because of "only"
	 * and "except" rules.
	 * 
	 * @return string
	 */
	public function variantKey() {
		$key = $this->variantKeySpec; // Copy to fill in actual values

		if (isset($key['environment'])) $key['environment'] = Director::isDev() ? 'dev' : (Director::isTest() ? 'test' : 'live');

		if (isset($key['envvars'])) foreach ($key['envvars'] as $variable => $foo) {
			$key['envvars'][$variable] = isset($_ENV[$variable]) ? $_ENV[$variable] : null;
		}

		if (isset($key['constants'])) foreach ($key['constants'] as $variable => $foo) {
			$key['constants'][$variable] = defined($variable) ? constant($variable) : null;
		}

		return sha1(serialize($key));
	}

	/**
	 * Completely regenerates the manifest file. Scans through finding all php _config.php and yaml _config/*.ya?ml files,
	 * parses the yaml files into fragments, sorts them and figures out what values need to be checked to pick the
	 * correct variant.
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
			$this->cache->save($this->phpConfigSources, 'php_config_sources');
			$this->cache->save($this->yamlConfigFragments, 'yaml_config_fragments');
			$this->cache->save($this->variantKeySpec, 'variant_key_spec');
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
		require_once 'thirdparty/zend_translate_railsyaml/library/Translate/Adapter/thirdparty/sfYaml/lib/sfYamlParser.php';
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
			if (count($parts) % 2 != 0) user_error("Configuration file $basename does not have an equal number of headers and config blocks");

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
							preg_match('! (?P<module>\*|\w+)? (\/ (?P<file>\*|\w+))? (\# (?P<fragment>\*|\w+))? !x', $part, $match);

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
					$res .= "<dt>{$node['from']['module']}/{$node['from']['file']}#{$node['from']['name']} marked to come after</dt><dd><ul>";
					foreach ($node['to'] as $to) {
						$res .= "<li>{$to['module']}/{$to['file']}#{$to['name']}</li>";
					}
					$res .= "</ul></dd>";
				}

				$res .= '</dl>';
				echo $res;
			}

			user_error('Based on their before & after rules two fragments both need to be before/after each other', E_USER_ERROR);
		}

	}
	
	/**
	 * Return a string "after", "before" or "undefined" depending on whether the YAML fragment array element passed as $a should
	 * be positioned after, before, or either compared to the YAML fragment array element passed as $b
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

				if ($matchlevel[$rulename] === false || $level > $matchlevel[$rulename]) $matchlevel[$rulename] = $level;
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
	 * This function filters the loaded yaml fragments, removing any that can't ever have their "only" and "except" rules
	 * match
	 *
	 * Some tests in "only" and "except" rules need to be checked per request, but some are manifest based -
	 * these are invariant over requests and only need checking on manifest rebuild. So we can prefilter these before
	 * saving yamlConfigFragments to speed up the process of checking the per-request variant/
	 */
	function prefilterYamlFragments() {
		$matchingFragments = array();

		foreach ($this->yamlConfigFragments as $i => $fragment) {
			$failsonly = isset($fragment['only']) && !$this->matchesPrefilterVariantRules($fragment['only']);
			$matchesexcept = isset($fragment['except']) && $this->matchesPrefilterVariantRules($fragment['except']);

			if (!$failsonly && !$matchesexcept) $matchingFragments[] = $fragment;
		}

		$this->yamlConfigFragments = $matchingFragments;
	}

	/**
	 * Returns false if the prefilterable parts of the rule aren't met, and true if they are
	 *
	 * @param  $rules array - A hash of rules as allowed in the only or except portion of a config fragment header
	 * @return bool - True if the rules are met, false if not. (Note that depending on whether we were passed an only or an except rule,
	 * which values means accept or reject a fragment 
	 */
	function matchesPrefilterVariantRules($rules) {
		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
					if (!ClassInfo::exists($v)) return false;
					break;

				case 'moduleexists':
					if (!$this->moduleExists($v)) return false;
					break;
				
				default:
					// NOP
			}
		}

		return true;
	}

	/**
	 * Builds the variant key spec - the list of values that need to be build to give a key that uniquely identifies this variant.
	 */
	function buildVariantKeySpec() {
		$this->variantKeySpec = array();

		foreach ($this->yamlConfigFragments as $fragment) {
			if (isset($fragment['only'])) $this->addVariantKeySpecRules($fragment['only']);
			if (isset($fragment['except'])) $this->addVariantKeySpecRules($fragment['except']);
		}
	}

	/**
	 * Adds any variables referenced in the passed rules to the $this->variantKeySpec array
	 */
	function addVariantKeySpecRules($rules) {
		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					// Classes and modules are a special case - we can pre-filter on config regenerate because we already know
					// if the class or module exists
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
	 */
	function buildYamlConfigVariant($cache = true) {
		$this->yamlConfig = array();

		foreach ($this->yamlConfigFragments as $i => $fragment) {
			$failsonly = isset($fragment['only']) && !$this->matchesVariantRules($fragment['only']);
			$matchesexcept = isset($fragment['except']) && $this->matchesVariantRules($fragment['except']);

			if (!$failsonly && !$matchesexcept) $this->mergeInYamlFragment($this->yamlConfig, $fragment['fragment']);
		}

		if ($cache) {
			$this->cache->save($this->yamlConfig, 'yaml_config_'.$this->variantKey());
		}
	}

	/**
 	 * Returns false if the non-prefilterable parts of the rule aren't met, and true if they are
 	 */
	function matchesVariantRules($rules) {
		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					break;

				case 'environment':
					switch (strtolower($v)) {
						case 'live':
							if (!Director::isLive()) return false;
							break;
						case 'test':
							if (!Director::isTest()) return false;
							break;
						case 'dev':
							if (!Director::isDev()) return false;
							break;
						default:
							user_error('Unknown environment '.$v.' in config fragment', E_USER_ERROR);
					}
					break;

				case 'envvarset':
					if (isset($_ENV[$k])) break;
					return false;

				case 'constantdefined':
					if (defined($k)) break;
					return false;

				default:
					if (isset($_ENV[$k]) && $_ENV[$k] == $v) break;
					if (defined($k) && constant($k) == $v) break;
					return false;
			}
		}

		return true;
	}

	/**
	 * Recursively merge a yaml fragment's configuration array into the primary merged configuration array.
	 * @param  $into
	 * @param  $fragment
	 * @return void
	 */
	function mergeInYamlFragment(&$into, $fragment) {
		foreach ($fragment as $k => $v) {
			Config::merge_high_into_low($into[$k], $v);
		}
	}

}
