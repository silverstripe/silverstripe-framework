<?php
/**
 * A utility class which builds a manifest of the statics defined in all classes, along with their
 * access levels and values
 *
 * We use this to make the statics that the Config system uses as default values be truely immutable.
 *
 * It has the side effect of allowing Config to avoid private-level access restrictions, so we can
 * optionally catch attempts to modify the config statics (otherwise the modification will appear
 * to work, but won't actually have any effect - the equvilent of failing silently)
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest {

	protected $base;
	protected $tests;

	protected $cache;
	protected $key;

	protected $index;
	protected $statics;

	static protected $initial_classes = array(
		'Object', 'ViewableData', 'Injector', 'Director'
	);

	/**
	 * Constructs and initialises a new config static manifest, either loading the data
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

		$this->cache = new $cacheClass('staticmanifest'.($includeTests ? '_tests' : ''));
		$this->key = sha1($base);

		if(!$forceRegen) {
			$this->index = $this->cache->load($this->key);
		}

		if($this->index) {
			$this->statics = $this->index['$statics'];
		}
		else {
			$this->regenerate($cache);
		}
	}

	public function get($class, $name, $default) {
		if (!isset($this->statics[$class])) {
			if (isset($this->index[$class])) {
				$info = $this->index[$class];

				if (isset($info['key']) && $details = $this->cache->load($this->key.'_'.$info['key'])) {
					$this->statics += $details;
				}

				if (!isset($this->statics[$class])) {
					$this->handleFile(null, $info['path'], null);
				}
			}
			else {
				$this->statics[$class] = false;
			}
		}

		if (isset($this->statics[$class][$name])) {
			$static = $this->statics[$class][$name];

			if ($static['access'] != T_PRIVATE) {
				Deprecation::notice('3.2.0', "Config static $class::\$$name must be marked as private",
					Deprecation::SCOPE_GLOBAL);
				// Don't warn more than once per static
				$this->statics[$class][$name]['access'] = T_PRIVATE;
			}

			return $static['value'];
		}

		return $default;
	}

	/**
	 * Completely regenerates the manifest file.
	 */
	public function regenerate($cache = true) {
		$this->index = array('$statics' => array());
		$this->statics = array();

		$finder = new ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/^([^_].*\.php)$/',
			'ignore_files'  => array('index.php', 'main.php', 'cli-script.php', 'SSTemplateParser.php'),
			'ignore_tests'  => !$this->tests,
			'file_callback' => array($this, 'handleFile')
		));

		$finder->find($this->base);

		if($cache) {
			$keysets = array();

			foreach ($this->statics as $class => $details) {
				if (in_array($class, self::$initial_classes)) {
					$this->index['$statics'][$class] = $details;
				}
				else {
					$key = sha1($class);
					$this->index[$class]['key'] = $key;

					$keysets[$key][$class] = $details;
				}
			}

			foreach ($keysets as $key => $details) {
				$this->cache->save($details, $this->key.'_'.$key);
			}

			$this->cache->save($this->index, $this->key);
		}
	}

	public function handleFile($basename, $pathname, $depth) {
		$parser = new SS_ConfigStaticManifest_Parser($pathname);
		$parser->parse();

		$this->index = array_merge($this->index, $parser->getInfo());
		$this->statics = array_merge($this->statics, $parser->getStatics());
	}

	public function getStatics() {
		return $this->statics;
	}
}

/**
 * A parser that processes a PHP file, using PHP's built in parser to get a string of tokens,
 * then processing them to find the static class variables, their access levels & values
 *
 * We can't do this using TokenisedRegularExpression because we need to keep track of state
 * as we process the token list (when we enter and leave a namespace or class, when we see
 * an access level keyword, etc)
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest_Parser {

	protected $info = array();
	protected $statics = array();

	protected $path;
	protected $tokens;
	protected $length;
	protected $pos;

	function __construct($path) {
		$this->path = $path;
		$file = file_get_contents($path);

		$this->tokens = token_get_all($file);
		$this->length = count($this->tokens);
		$this->pos = 0;
	}

	function getInfo() {
		return $this->info;
	}

	function getStatics() {
		return $this->statics;
	}

	/**
	 * Get the next token to process, incrementing the pointer
	 *
	 * @param bool $ignoreWhitespace - if true will skip any whitespace tokens & only return non-whitespace ones
	 * @return null | int - Either the next token or null if there isn't one
	 */
	protected function next($ignoreWhitespace = true) {
		do {
			if($this->pos >= $this->length) return null;
			$next = $this->tokens[$this->pos++];
		}
		while($ignoreWhitespace && is_array($next) && $next[0] == T_WHITESPACE);

		return $next;
	}

	/**
	 * Parse the given file to find the static variables declared in it, along with their access & values
	 */
	function parse() {
		$depth = 0; $namespace = null; $class = null; $clsdepth = null; $access = 0;

		while($token = $this->next()) {
			$type = is_array($token) ? $token[0] : $token;

			if($type == T_CLASS) {
				$next = $this->next();
				if($next[0] != T_STRING) {
					user_error("Couldn\'t parse {$this->path} when building config static manifest", E_USER_ERROR);
				}

				$class = $next[1];
			}
			else if($type == T_NAMESPACE) {
				$namespace = '';
				while(true) {
					$next = $this->next();

					if($next == ';') {
						break;
					} elseif($next[0] == T_NS_SEPARATOR) {
						$namespace .= $next[1];
						$next = $this->next();
					}

					if($next[0] != T_STRING) {
						user_error("Couldn\'t parse {$this->path} when building config static manifest", E_USER_ERROR);
					}

					$namespace .= $next[1];
				}
			}
			else if($type == '{' || $type == T_CURLY_OPEN || $type == T_DOLLAR_OPEN_CURLY_BRACES){
				$depth += 1;
				if($class && !$clsdepth) $clsdepth = $depth;
			}
			else if($type == '}') {
				$depth -= 1;
				if($depth < $clsdepth) $class = $clsdepth = null;
				if($depth < 0) user_error("Hmm - depth calc wrong, hit negatives, see: ".$this->path, E_USER_ERROR);
			}
			else if($type == T_PUBLIC || $type == T_PRIVATE || $type == T_PROTECTED) {
				$access = $type;
			}
			else if($type == T_STATIC && $class && $depth == $clsdepth) {
				$this->parseStatic($access, $namespace ? $namespace.'\\'.$class : $class);
				$access = 0;
			}
			else {
				$access = 0;
			}
		}
	}

	/**
	 * During parsing we've found a "static" keyword. Parse out the variable names and value
	 * assignments that follow.
	 *
	 * Seperated out from parse partially so that we can recurse if there are multiple statics
	 * being declared in a comma seperated list
	 */
	function parseStatic($access, $class) {
		$variable = null;
		$value = '';

		while($token = $this->next()) {
			$type = is_array($token) ? $token[0] : $token;

			if($type == T_PUBLIC || $type == T_PRIVATE || $type == T_PROTECTED) {
				$access = $type;
			}
			else if($type == T_FUNCTION) {
				return;
			}
			else if($type == T_VARIABLE) {
				$variable = substr($token[1], 1); // Cut off initial "$"
			}
			else if($type == ';' || $type == ',' || $type == '=') {
				break;
			}
			else if($type == T_COMMENT || $type == T_DOC_COMMENT) {
				// NOP
			}
			else {
				user_error('Unexpected token when building static manifest: '.print_r($token, true), E_USER_ERROR);
			}
		}

		if($token == '=') {
			$depth = 0;

			while($token = $this->next(false)){
				$type = is_array($token) ? $token[0] : $token;

				// Track array nesting depth
				if($type == T_ARRAY || $type == '[') {
					$depth += 1;
				} elseif($type == ')' || $type == ']') {
					$depth -= 1;
				}

				// Parse out the assignment side of a static declaration,
				// ending on either a ';' or a ',' outside an array
				if($type == T_WHITESPACE) {
					$value .= ' ';
				}
				else if($type == ';' || ($type == ',' && !$depth)) {
					break;
				}
				// Statics can reference class constants with self:: (and that won't work in eval)
				else if($type == T_STRING && $token[1] == 'self') {
					$value .= $class;
				}
				else {
					$value .= is_array($token) ? $token[1] : $token;
				}
			}
		}

		if (!isset($this->info[$class])) {
			$this->info[$class] = array(
				'path' => $this->path,
				'mtime' => filemtime($this->path),
			);
		}

		if(!isset($this->statics[$class])) {
			$this->statics[$class] = array();
		}

		$value = trim($value);
		if ($value) {
			$value = eval('static $temp = '.$value.";\n".'return $temp'.";\n");
		}
		else {
			$value = null;
		}

		$this->statics[$class][$variable] = array(
			'access' => $access,
			'value' => $value
		);

		if($token == ',') $this->parseStatic($access, $class);
	}
}
