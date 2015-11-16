<?php

/**
 * This tracks the current scope for an SSViewer instance. It has three goals:
 *   - Handle entering & leaving sub-scopes in loops and withs
 *   - Track Up and Top
 *   - (As a side effect) Inject data that needs to be available globally (used to live in ViewableData)
 * 
 * In order to handle up, rather than tracking it using a tree, which would involve constructing new objects
 * for each step, we use indexes into the itemStack (which already has to exist).
 * 
 * Each item has three indexes associated with it
 * 
 *   - Pop. Which item should become the scope once the current scope is popped out of
 *   - Up. Which item is up from this item
 *   - Current. Which item is the first time this object has appeared in the stack
 * 
 * We also keep the index of the current starting point for lookups. A lookup is a sequence of obj calls -
 * when in a loop or with tag the end result becomes the new scope, but for injections, we throw away the lookup
 * and revert back to the original scope once we've got the value we're after
 *
 * @package framework
 * @subpackage view
 */
class SSViewer_Scope {
	
	// The stack of previous "global" items
	// And array of item, itemIterator, itemIteratorTotal, pop_index, up_index, current_index
	private $itemStack = array(); 
	
	// The current "global" item (the one any lookup starts from)
	protected $item; 

	// If we're looping over the current "global" item, here's the iterator that tracks with item we're up to
	protected $itemIterator; 

	//Total number of items in the iterator
	protected $itemIteratorTotal;
	
	// A pointer into the item stack for which item should be scope on the next pop call
	private $popIndex;

	// A pointer into the item stack for which item is "up" from this one
	private $upIndex = null;

	// A pointer into the item stack for which item is this one (or null if not in stack yet)
	private $currentIndex = null;
	
	private $localIndex;

	public function __construct($item, $inheritedScope = null) {
		$this->item = $item;
		$this->localIndex = 0;
		$this->localStack = array();
		if ($inheritedScope instanceof SSViewer_Scope) {
			$this->itemIterator = $inheritedScope->itemIterator;
			$this->itemIteratorTotal = $inheritedScope->itemIteratorTotal;
			$this->itemStack[] = array($this->item, $this->itemIterator, $this->itemIteratorTotal, null, null, 0);
		} else {
			$this->itemStack[] = array($this->item, null, 0, null, null, 0);
		}
	}
	
	public function getItem(){
		return $this->itemIterator ? $this->itemIterator->current() : $this->item;
	}

	/** Called at the start of every lookup chain by SSTemplateParser to indicate a new lookup from local scope */
	public function locally() {
		list($this->item, $this->itemIterator, $this->itemIteratorTotal, $this->popIndex, $this->upIndex,
			$this->currentIndex) = $this->itemStack[$this->localIndex];

		// Remember any  un-completed (resetLocalScope hasn't been called) lookup chain. Even if there isn't an
		// un-completed chain we need to store an empty item, as resetLocalScope doesn't know the difference later
		$this->localStack[] = array_splice($this->itemStack, $this->localIndex+1);

		return $this;
	}

	public function resetLocalScope(){
		$previousLocalState = $this->localStack ? array_pop($this->localStack) : null;

		array_splice($this->itemStack, $this->localIndex+1, count($this->itemStack), $previousLocalState);

		list($this->item, $this->itemIterator, $this->itemIteratorTotal, $this->popIndex, $this->upIndex,
			$this->currentIndex) = end($this->itemStack);
	}

	public function getObj($name, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		return $on->obj($name, $arguments, $forceReturnedObject, $cache, $cacheName);
	}

	public function obj($name, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		switch ($name) {
			case 'Up':
				if ($this->upIndex === null) {
					user_error('Up called when we\'re already at the top of the scope', E_USER_ERROR);
				}

				list($this->item, $this->itemIterator, $this->itemIteratorTotal, $unused2, $this->upIndex,
					$this->currentIndex) = $this->itemStack[$this->upIndex];
				break;
			
			case 'Top':
				list($this->item, $this->itemIterator, $this->itemIteratorTotal, $unused2, $this->upIndex,
					$this->currentIndex) = $this->itemStack[0];
				break;
			
			default:
				$this->item = $this->getObj($name, $arguments, $forceReturnedObject, $cache, $cacheName);
				$this->itemIterator = null;
				$this->upIndex = $this->currentIndex ? $this->currentIndex : count($this->itemStack)-1;
				$this->currentIndex = count($this->itemStack);
				break;
		}

		$this->itemStack[] = array($this->item, $this->itemIterator, $this->itemIteratorTotal, null,
			$this->upIndex, $this->currentIndex);
		return $this;
	}

	/**
	 * Gets the current object and resets the scope.
	 *
	 * @return object
	 */
	public function self() {
		$result = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		$this->resetLocalScope();

		return $result;
	}

	public function pushScope(){
		$newLocalIndex = count($this->itemStack)-1;
		
		$this->popIndex = $this->itemStack[$newLocalIndex][3] = $this->localIndex;
		$this->localIndex = $newLocalIndex;
		
		// We normally keep any previous itemIterator around, so local $Up calls reference the right element. But
		// once we enter a new global scope, we need to make sure we use a new one
		$this->itemIterator = $this->itemStack[$newLocalIndex][1] = null;
		
		return $this;
	}

	public function popScope(){
		$this->localIndex = $this->popIndex;
		$this->resetLocalScope();
		
		return $this;
	}
	
	public function next(){
		if (!$this->item) return false;
		
		if (!$this->itemIterator) {
			if (is_array($this->item)) $this->itemIterator = new ArrayIterator($this->item);
			else $this->itemIterator = $this->item->getIterator();
			
			$this->itemStack[$this->localIndex][1] = $this->itemIterator;
			$this->itemIteratorTotal = iterator_count($this->itemIterator); //count the total number of items
			$this->itemStack[$this->localIndex][2] = $this->itemIteratorTotal;
			$this->itemIterator->rewind();
		}
		else {
			$this->itemIterator->next();
		}
		
		$this->resetLocalScope();

		if (!$this->itemIterator->valid()) return false;
		return $this->itemIterator->key();
	}
	
	public function __call($name, $arguments) {
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		$retval = $on ? call_user_func_array(array($on, $name), $arguments) : null;
		
		$this->resetLocalScope();
		return $retval;
	}
}

/**
 * Defines an extra set of basic methods that can be used in templates
 * that are not defined on sub-classes of {@link ViewableData}.
 *
 * @package framework
 * @subpackage view
 */
class SSViewer_BasicIteratorSupport implements TemplateIteratorProvider {

	protected $iteratorPos;
	protected $iteratorTotalItems;

	public static function get_template_iterator_variables() {
		return array(
			'First',
			'Last',
			'FirstLast',
			'Middle',
			'MiddleString',
			'Even',
			'Odd',
			'EvenOdd',
			'Pos',
			'FromEnd',
			'TotalItems',
			'Modulus',
			'MultipleOf',
		);
	}

	/**
	 * Set the current iterator properties - where we are on the iterator.
	 *
	 * @param int $pos position in iterator
	 * @param int $totalItems total number of items
	 */
	public function iteratorProperties($pos, $totalItems) {
		$this->iteratorPos        = $pos;
		$this->iteratorTotalItems = $totalItems;
	}

	/**
	 * Returns true if this object is the first in a set.
	 *
	 * @return bool
	 */
	public function First() {
		return $this->iteratorPos == 0;
	}

	/**
	 * Returns true if this object is the last in a set.
	 *
	 * @return bool
	 */
	public function Last() {
		return $this->iteratorPos == $this->iteratorTotalItems - 1;
	}

	/**
	 * Returns 'first' or 'last' if this is the first or last object in the set.
	 *
	 * @return string|null
	 */
	public function FirstLast() {
		if($this->First() && $this->Last()) return 'first last';
		if($this->First()) return 'first';
		if($this->Last())  return 'last';
	}

	/**
	 * Return true if this object is between the first & last objects.
	 *
	 * @return bool
	 */
	public function Middle() {
		return !$this->First() && !$this->Last();
	}

	/**
	 * Return 'middle' if this object is between the first & last objects.
	 *
	 * @return string|null
	 */
	public function MiddleString() {
		if($this->Middle()) return 'middle';
	}

	/**
	 * Return true if this object is an even item in the set.
	 * The count starts from $startIndex, which defaults to 1.
	 *
	 * @param int $startIndex Number to start count from.
	 * @return bool
	 */
	public function Even($startIndex = 1) {
		return !$this->Odd($startIndex);
	}

	/**
	 * Return true if this is an odd item in the set.
	 *
	 * @param int $startIndex Number to start count from.
	 * @return bool
	 */
	public function Odd($startIndex = 1) {
		return (bool) (($this->iteratorPos+$startIndex) % 2);
	}

	/**
	 * Return 'even' or 'odd' if this object is in an even or odd position in the set respectively.
	 *
	 * @param int $startIndex Number to start count from.
	 * @return string
	 */
	public function EvenOdd($startIndex = 1) {
		return ($this->Even($startIndex)) ? 'even' : 'odd';
	}

	/**
	 * Return the numerical position of this object in the container set. The count starts at $startIndex.
	 * The default is the give the position using a 1-based index.
	 *
	 * @param int $startIndex Number to start count from.
	 * @return int
	 */
	public function Pos($startIndex = 1) {
		return $this->iteratorPos + $startIndex;
	}

	/**
	 * Return the position of this item from the last item in the list. The position of the final
	 * item is $endIndex, which defaults to 1.
	 *
	 * @param integer $endIndex Value of the last item
	 * @return int
	 */
	public function FromEnd($endIndex = 1) {
		return $this->iteratorTotalItems - $this->iteratorPos + $endIndex - 1;
	}

	/**
	 * Return the total number of "sibling" items in the dataset.
	 *
	 * @return int
	 */
	public function TotalItems() {
		return $this->iteratorTotalItems;
	}

	/**
	 * Returns the modulus of the numerical position of the item in the data set.
	 * The count starts from $startIndex, which defaults to 1.
	 * @param int $Mod The number to perform Mod operation to.
	 * @param int $startIndex Number to start count from.
	 * @return int
	 */
	public function Modulus($mod, $startIndex = 1) {
		return ($this->iteratorPos + $startIndex) % $mod;
	}

	/**
	 * Returns true or false depending on if the pos of the iterator is a multiple of a specific number.
	 * So, <% if MultipleOf(3) %> would return true on indexes: 3,6,9,12,15, etc. 
	 * The count starts from $offset, which defaults to 1.
	 * @param int $factor The multiple of which to return
	 * @param int $offset Number to start count from.
	 * @return bool
	 */
	public function MultipleOf($factor, $offset = 1) {
		return (bool) ($this->Modulus($factor, $offset) == 0);
	}



}
/**
 * This extends SSViewer_Scope to mix in data on top of what the item provides. This can be "global"
 * data that is scope-independant (like BaseURL), or type-specific data that is layered on top cross-cut like
 * (like $FirstLast etc).
 * 
 * It's separate from SSViewer_Scope to keep that fairly complex code as clean as possible.
 *
 * @package framework
 * @subpackage view
 */
class SSViewer_DataPresenter extends SSViewer_Scope {
	
	private static $globalProperties = null;
	private static $iteratorProperties = null;

	/**
	 * Overlay variables. Take precedence over anything from the current scope
	 * @var array|null
	 */
	protected $overlay;

	/**
	 * Underlay variables. Concede precedence to overlay variables or anything from the current scope
	 * @var array|null
	 */
	protected $underlay;

	public function __construct($item, $overlay = null, $underlay = null, $inheritedScope = null) {
		parent::__construct($item, $inheritedScope);

		// Build up global property providers array only once per request
		if (self::$globalProperties === null) {
			self::$globalProperties = array();
			// Get all the exposed variables from all classes that implement the TemplateGlobalProvider interface
			$this->createCallableArray(self::$globalProperties, "TemplateGlobalProvider",
				"get_template_global_variables");
		}

		// Build up iterator property providers array only once per request
		if (self::$iteratorProperties === null) {
			self::$iteratorProperties = array();
			// Get all the exposed variables from all classes that implement the TemplateIteratorProvider interface
			// //call non-statically
			$this->createCallableArray(self::$iteratorProperties, "TemplateIteratorProvider", 
				"get_template_iterator_variables", true);
		}

		$this->overlay = $overlay ? $overlay : array();
		$this->underlay = $underlay ? $underlay : array();
	}

	protected function createCallableArray(&$extraArray, $interfaceToQuery, $variableMethod, $createObject = false) {
		$implementers = ClassInfo::implementorsOf($interfaceToQuery);
		if($implementers) foreach($implementers as $implementer) {

			// Create a new instance of the object for method calls
			if ($createObject) $implementer = new $implementer();

			// Get the exposed variables
			$exposedVariables = call_user_func(array($implementer, $variableMethod));

			foreach($exposedVariables as $varName => $details) {
				if (!is_array($details)) $details = array('method' => $details, 
					'casting' => Config::inst()->get('ViewableData', 'default_cast', Config::FIRST_SET));

				// If just a value (and not a key => value pair), use it for both key and value
				if (is_numeric($varName)) $varName = $details['method'];

				// Add in a reference to the implementing class (might be a string class name or an instance)
				$details['implementer'] = $implementer;

				// And a callable array
				if (isset($details['method'])) $details['callable'] = array($implementer, $details['method']);

				// Save with both uppercase & lowercase first letter, so either works
				$lcFirst = strtolower($varName[0]) . substr($varName,1);
				$extraArray[$lcFirst] = $details;
				$extraArray[ucfirst($varName)] = $details;
			}
		}
	}

	/**
	 * Get the injected value
	 * 
	 * @param string $property Name of property
	 * @param array $params
	 * @param bool $cast If true, an object is always returned even if not an object.
	 * @return array Result array with the keys 'value' for raw value, or 'obj' if contained in an object
	 * @throws InvalidArgumentException
	 */
	public function getInjectedValue($property, $params, $cast = true) {
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;

		// Find the source of the value
		$source = null;

		// Check for a presenter-specific override
		if (array_key_exists($property, $this->overlay)) {
			$source = array('value' => $this->overlay[$property]);
		}
		// Check if the method to-be-called exists on the target object - if so, don't check any further
		// injection locations
		else if (isset($on->$property) || method_exists($on, $property)) {
			$source = null;
		}
		// Check for a presenter-specific override
		else if (array_key_exists($property, $this->underlay)) {
			$source = array('value' => $this->underlay[$property]);
		}
		// Then for iterator-specific overrides
		else if (array_key_exists($property, self::$iteratorProperties)) {
			$source = self::$iteratorProperties[$property];
			if ($this->itemIterator) {
				// Set the current iterator position and total (the object instance is the first item in
				// the callable array)
				$source['implementer']->iteratorProperties($this->itemIterator->key(), $this->itemIteratorTotal);
			} else {
				// If we don't actually have an iterator at the moment, act like a list of length 1
				$source['implementer']->iteratorProperties(0, 1);
			}
		}
		// And finally for global overrides
		else if (array_key_exists($property, self::$globalProperties)) {
			$source = self::$globalProperties[$property];  //get the method call
		}

		if ($source) {
			$res = array();

			// Look up the value - either from a callable, or from a directly provided value
			if (isset($source['callable'])) $res['value'] = call_user_func_array($source['callable'], $params);
			elseif (isset($source['value'])) $res['value'] = $source['value'];
			else throw new InvalidArgumentException("Injected property $property does't have a value or callable " .
				"value source provided");

			// If we want to provide a casted object, look up what type object to use
			if ($cast) {
				// If the handler returns an object, then we don't need to cast.
				if(is_object($res['value'])) {
					$res['obj'] = $res['value'];
				} else {
					// Get the object to cast as
					$casting = isset($source['casting']) ? $source['casting'] : null;

					// If not provided, use default
					if (!$casting) $casting = Config::inst()->get('ViewableData', 'default_cast', Config::FIRST_SET);

					$obj = new $casting($property);
					$obj->setValue($res['value']);

					$res['obj'] = $obj;
				}
			}

			return $res;
		}

	}

	public function getObj($name, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		$result = $this->getInjectedValue($name, (array)$arguments);
		if($result) return $result['obj'];
		else return parent::getObj($name, $arguments, $forceReturnedObject, $cache, $cacheName);
	}

	public function __call($name, $arguments) {
		//extract the method name and parameters
		$property = $arguments[0];  //the name of the public function being called

		//the public function parameters in an array
		if (isset($arguments[1]) && $arguments[1] != null) $params = $arguments[1]; 
		else $params = array();

		$val = $this->getInjectedValue($property, $params);
		if ($val) {
			$obj = $val['obj'];
			if ($name === 'hasValue') {
				$res = $obj instanceof Object
					? $obj->exists()
					: (bool)$obj;
			} else {
				// XML_val
				$res = $obj->forTemplate();
			}
			$this->resetLocalScope();
			return $res;
		} else {
			return parent::__call($name, $arguments);
		}
	}
}

/**
 * Parses a template file with an *.ss file extension.
 * 
 * In addition to a full template in the templates/ folder, a template in 
 * templates/Content or templates/Layout will be rendered into $Content and
 * $Layout, respectively.
 * 
 * A single template can be parsed by multiple nested {@link SSViewer} instances
 * through $Layout/$Content placeholders, as well as <% include MyTemplateFile %> template commands.
 * 
 * <b>Themes</b>
 * 
 * See http://doc.silverstripe.org/themes and http://doc.silverstripe.org/themes:developing
 * 
 * <b>Caching</b>
 *
 * Compiled templates are cached via {@link SS_Cache}, usually on the filesystem.  
 * If you put ?flush=1 on your URL, it will force the template to be recompiled.
 *
 * @see http://doc.silverstripe.org/themes
 * @see http://doc.silverstripe.org/themes:developing
 * 
 * @package framework
 * @subpackage view
 */
class SSViewer implements Flushable {
	
	/**
	 * @config
	 * @var boolean $source_file_comments
	 */
	private static $source_file_comments = false;

	/**
	 * @ignore
	 */
	private static $template_cache_flushed = false;

	/**
	 * @ignore
	 */
	private static $cacheblock_cache_flushed = false;

	/**
	 * Set whether HTML comments indicating the source .SS file used to render this page should be
	 * included in the output.  This is enabled by default
	 *
	 * @deprecated 4.0 Use the "SSViewer.source_file_comments" config setting instead
	 * @param boolean $val
	 */
	public static function set_source_file_comments($val) {
		Deprecation::notice('4.0', 'Use the "SSViewer.source_file_comments" config setting instead');
		Config::inst()->update('SSViewer', 'source_file_comments', $val);
	}
	
	/**
	 * @deprecated 4.0 Use the "SSViewer.source_file_comments" config setting instead
	 * @return boolean
	 */
	public static function get_source_file_comments() {
		Deprecation::notice('4.0', 'Use the "SSViewer.source_file_comments" config setting instead');
		return Config::inst()->get('SSViewer', 'source_file_comments');
	}
	
	/**
	 * @var array $chosenTemplates Associative array for the different
	 * template containers: "main" and "Layout". Values are absolute file paths to *.ss files.
	 */
	private $chosenTemplates = array();
	
	/**
	 * @var boolean
	 */
	protected $rewriteHashlinks = true;
	
	/**
	 * @config
	 * @var string The used "theme", which usually consists of templates, images and stylesheets.
	 * Only used when {@link $theme_enabled} is set to TRUE.
	 */
	private static $theme = null;

	/**
	 * @config
	 * @var boolean Use the theme. Set to FALSE in order to disable themes,
	 * which can be useful for scenarios where theme overrides are temporarily undesired,
	 * such as an administrative interface separate from the website theme. 
	 * It retains the theme settings to be re-enabled, for example when a website content
	 * needs to be rendered from within this administrative interface.
	 */
	private static $theme_enabled = true;

	/**
	 * @var boolean
	 */
	protected $includeRequirements = true;

	/**
	 * @var TemplateParser
	 */
	protected $parser;

	/*
	 * Default prepended cache key for partial caching
	 * 
	 * @var string
	 * @config
	 */
	private static $global_key = '$CurrentReadingMode, $CurrentUser.ID';

	/**
	 * Triggered early in the request when someone requests a flush.
	 */
	public static function flush() {
		self::flush_template_cache(true);
		self::flush_cacheblock_cache(true);
	}

	/**
	 * Create a template from a string instead of a .ss file
	 *
	 * @param string $content The template content
	 * @param bool|void $cacheTemplate Whether or not to cache the template from string
	 * @return SSViewer
	 */
	public static function fromString($content, $cacheTemplate = null) {
		$viewer = new SSViewer_FromString($content);
		if ($cacheTemplate !== null) {
			$viewer->setCacheTemplate($cacheTemplate);
		}
		return $viewer;
	}
	
	/**
	 * @deprecated 4.0 Use the "SSViewer.theme" config setting instead
	 * @param string $theme The "base theme" name (without underscores). 
	 */
	public static function set_theme($theme) {
		Deprecation::notice('4.0', 'Use the "SSViewer.theme" config setting instead');
		Config::inst()->update('SSViewer', 'theme', $theme);
	}
	
	/**
	 * @deprecated 4.0 Use the "SSViewer.theme" config setting instead
	 * @return string 
	 */
	public static function current_theme() {
		Deprecation::notice('4.0', 'Use the "SSViewer.theme" config setting instead');
		return Config::inst()->get('SSViewer', 'theme');
	}
	
	/**
	 * Returns the path to the theme folder
	 *
	 * @return string
	 */
	public static function get_theme_folder() {
		$theme = Config::inst()->get('SSViewer', 'theme');
		return $theme ? THEMES_DIR . "/" . $theme : project();
	}

	/**
	 * Returns an array of theme names present in a directory.
	 *
	 * @param  string $path
	 * @param  bool   $subthemes Include subthemes (default false).
	 * @return array
	 */
	public static function get_themes($path = null, $subthemes = false) {
		$path   = rtrim($path ? $path : THEMES_PATH, '/');
		$themes = array();

		if (!is_dir($path)) return $themes;

		foreach (scandir($path) as $item) {
			if ($item[0] != '.' && is_dir("$path/$item")) {
				if ($subthemes || strpos($item, '_') === false) {
					$themes[$item] = $item;
				}
			}
		}

		return $themes;
	}

	/**
	 * @deprecated since version 4.0
	 * @return string
	 */
	public static function current_custom_theme(){
		Deprecation::notice('4.0', 'Use the "SSViewer.theme" and "SSViewer.theme_enabled" config settings instead');
		return Config::inst()->get('SSViewer', 'theme_enabled') ? Config::inst()->get('SSViewer', 'theme') : null;
	}

	/**
	 * Traverses the given the given class context looking for templates with the relevant name.
	 *
	 * @param $className string - valid class name
	 * @param $suffix string
	 * @param $baseClass string
	 *
	 * @return array
	 */
	public static function get_templates_by_class($className, $suffix = '', $baseClass = null) {
		// Figure out the class name from the supplied context.
		if(!is_string($className) || !class_exists($className)) {
			throw new InvalidArgumentException('SSViewer::get_templates_by_class() expects a valid class name as ' . 
				'its first parameter.');
			return array();
		}
		$templates = array();
		$classes = array_reverse(ClassInfo::ancestry($className));
		foreach($classes as $class) {
			$template = $class . $suffix;
			if(SSViewer::hasTemplate($template)) $templates[] = $template;

			// If the class is "Page_Controller", look for Page.ss
			if(stripos($class,'_controller') !== false) {
				$template = str_ireplace('_controller','',$class) . $suffix;
				if(SSViewer::hasTemplate($template)) $templates[] = $template;
			}

			if($baseClass && $class == $baseClass) break;
		}
		return $templates;
	}
	
	/**
	 * @param string|array $templateList If passed as a string with .ss extension, used as the "main" template.
	 *  If passed as an array, it can be used for template inheritance (first found template "wins").
	 *  Usually the array values are PHP class names, which directly correlate to template names.
	 *  <code>
	 *  array('MySpecificPage', 'MyPage', 'Page')
	 *  </code>
	 */
	public function __construct($templateList, TemplateParser $parser = null) {
		$this->setParser($parser ?: Injector::inst()->get('SSTemplateParser'));

		if(!is_array($templateList) && substr((string) $templateList,-3) == '.ss') {
			$this->chosenTemplates['main'] = $templateList;
		} else {
			if(Config::inst()->get('SSViewer', 'theme_enabled')) {
				$theme = Config::inst()->get('SSViewer', 'theme');
			} else {
				$theme = null;
			}
			$this->chosenTemplates = SS_TemplateLoader::instance()->findTemplates(
				$templateList, $theme
			);
		}

		if(!$this->chosenTemplates) {
			$templateList = (is_array($templateList)) ? $templateList : array($templateList);

			$message = 'None of the following templates could be found';
			if(!$theme) {
				$message .= ' (no theme in use)';
			} else {
				$message .= ' in theme "' . $theme . '"';
			}

			user_error($message . ': ' . implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);
		}
	}

	/**
	 * Set the template parser that will be used in template generation
	 * @param \TemplateParser $parser
	 */
	public function setParser(TemplateParser $parser)
	{
		$this->parser = $parser;
	}

	/**
	 * Returns the parser that is set for template generation
	 * @return \TemplateParser
	 */
	public function getParser()
	{
		return $this->parser;
	}

	/**
	 * Returns true if at least one of the listed templates exists.
	 *
	 * @param array $templates
	 *
	 * @return boolean
	 */
	public static function hasTemplate($templates) {
		$manifest = SS_TemplateLoader::instance()->getManifest();

		if(Config::inst()->get('SSViewer', 'theme_enabled')) {
			$theme = Config::inst()->get('SSViewer', 'theme');
		} else {
			$theme = null;
		}

		foreach ((array) $templates as $template) {
			if ($manifest->getCandidateTemplate($template, $theme)) return true;
		}

		return false;
	}
	
	/**
	 * Set a global rendering option.
	 *
	 * The following options are available:
	 *  - rewriteHashlinks: If true (the default), <a href="#..."> will be rewritten to contain the 
	 *    current URL.  This lets it play nicely with our <base> tag.
	 *  - If rewriteHashlinks = 'php' then, a piece of PHP script will be inserted before the hash 
	 *    links: "<?php echo $_SERVER['REQUEST_URI']; ?>".  This is useful if you're generating a 
	 *    page that will be saved to a .php file and may be accessed from different URLs.
	 *
	 * @deprecated 4.0 Use the "SSViewer.rewrite_hash_links" config setting instead
	 * @param string $optionName
	 * @param mixed $optionVal
	 */
	public static function setOption($optionName, $optionVal) {
		if($optionName == 'rewriteHashlinks') {
			Deprecation::notice('4.0', 'Use the "SSViewer.rewrite_hash_links" config setting instead');
			Config::inst()->update('SSViewer', 'rewrite_hash_links', $optionVal);
		} else {
			Deprecation::notice('4.0', 'Use the "SSViewer.' . $optionName . '" config setting instead');
			Config::inst()->update('SSViewer', $optionName, $optionVal);
		}
	}
	
	/**
 	 * @deprecated 4.0 Use the "SSViewer.rewrite_hash_links" config setting instead
 	 * @param string
 	 * @return mixed
	 */
	public static function getOption($optionName) {
		if($optionName == 'rewriteHashlinks') {
			Deprecation::notice('4.0', 'Use the "SSViewer.rewrite_hash_links" config setting instead');
			return Config::inst()->get('SSViewer', 'rewrite_hash_links');
		} else {
			Deprecation::notice('4.0', 'Use the "SSViewer.' . $optionName . '" config setting instead');
			return Config::inst()->get('SSViewer', $optionName);
		}
	}

	/**
	 * @config
	 * @var boolean
	 */
	private static $rewrite_hash_links = true;

	protected static $topLevel = array();

	public static function topLevel() {
		if(SSViewer::$topLevel) {
			return SSViewer::$topLevel[sizeof(SSViewer::$topLevel)-1];
		}
	}
	
	/**
	 * Call this to disable rewriting of <a href="#xxx"> links.  This is useful in Ajax applications.
	 * It returns the SSViewer objects, so that you can call new SSViewer("X")->dontRewriteHashlinks()->process();
	 */
	public function dontRewriteHashlinks() {
		$this->rewriteHashlinks = false;
		Config::inst()->update('SSViewer', 'rewrite_hash_links', false);
		return $this;
	}
	
	public function exists() {
		return $this->chosenTemplates;
	}

	/**
	 * @param string $identifier A template name without '.ss' extension or path
	 * @param string $type The template type, either "main", "Includes" or "Layout"
	 *
	 * @return string Full system path to a template file
	 */
	public static function getTemplateFileByType($identifier, $type) {
		$loader = SS_TemplateLoader::instance();
		if(Config::inst()->get('SSViewer', 'theme_enabled')) {
			$theme = Config::inst()->get('SSViewer', 'theme');
		} else {
			$theme = null;
		}
		$found  = $loader->findTemplates("$type/$identifier", $theme);

		if (isset($found['main'])) {
			return $found['main'];
		}
		else if (!empty($found)) {
			$founds = array_values($found);
			return $founds[0];
		}
	}
	
	/**
	 * Clears all parsed template files in the cache folder.
	 *
	 * Can only be called once per request (there may be multiple SSViewer instances).
	 *
	 * @param bool $force Set this to true to force a re-flush. If left to false, flushing
	 * may only be performed once a request.
	 */
	public static function flush_template_cache($force = false) {
		if (!self::$template_cache_flushed || $force) {
			$dir = dir(TEMP_FOLDER);
			while (false !== ($file = $dir->read())) {
				if (strstr($file, '.cache')) unlink(TEMP_FOLDER . '/' . $file);
			}
			self::$template_cache_flushed = true;
		}
	}

	/**
	 * Clears all partial cache blocks.
	 *
	 * Can only be called once per request (there may be multiple SSViewer instances).
	 *
	 * @param bool $force Set this to true to force a re-flush. If left to false, flushing
	 * may only be performed once a request.
	 */
	public static function flush_cacheblock_cache($force = false) {
		if (!self::$cacheblock_cache_flushed || $force) {
			$cache = SS_Cache::factory('cacheblock');
			$backend = $cache->getBackend();
			
			if(
				$backend instanceof Zend_Cache_Backend_ExtendedInterface
				&& ($capabilities = $backend->getCapabilities())
				&& $capabilities['tags']
			) {
				$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, $cache->getTags());
			} else {
				$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
			}

			
			self::$cacheblock_cache_flushed = true;
		}
	}

	/**
	 * @var Zend_Cache_Core
	 */
	protected $partialCacheStore = null;

	/**
	 * Set the cache object to use when storing / retrieving partial cache blocks.
	 *
	 * @param Zend_Cache_Core $cache
	 */
	public function setPartialCacheStore($cache) {
		$this->partialCacheStore = $cache;
	}

	/**
	 * Get the cache object to use when storing / retrieving partial cache blocks.
	 *
	 * @return Zend_Cache_Core
	 */
	public function getPartialCacheStore() {
		return $this->partialCacheStore ? $this->partialCacheStore : SS_Cache::factory('cacheblock');
	}

	/**
	 * Flag whether to include the requirements in this response.
	 *
	 * @param boolean
	 */
	public function includeRequirements($incl = true) {
		$this->includeRequirements = $incl;
	}

	/**
	 * An internal utility function to set up variables in preparation for including a compiled
	 * template, then do the include
	 *
	 * Effectively this is the common code that both SSViewer#process and SSViewer_FromString#process call
	 *
	 * @param string $cacheFile - The path to the file that contains the template compiled to PHP
	 * @param Object $item - The item to use as the root scope for the template
	 * @param array|null $overlay - Any variables to layer on top of the scope
	 * @param array|null $underlay - Any variables to layer underneath the scope
	 * @param Object $inheritedScope - the current scope of a parent template including a sub-template
	 *
	 * @return string - The result of executing the template
	 */
	protected function includeGeneratedTemplate($cacheFile, $item, $overlay, $underlay, $inheritedScope = null) {
		if(isset($_GET['showtemplate']) && $_GET['showtemplate'] && Permission::check('ADMIN')) {
			$lines = file($cacheFile);
			echo "<h2>Template: $cacheFile</h2>";
			echo "<pre>";
			foreach($lines as $num => $line) {
				echo str_pad($num+1,5) . htmlentities($line, ENT_COMPAT, 'UTF-8');
			}
			echo "</pre>";
		}

		$cache = $this->getPartialCacheStore();
		$scope = new SSViewer_DataPresenter($item, $overlay, $underlay, $inheritedScope);
		$val = '';

		include($cacheFile);

		return $val;
	}

	/**
	 * The process() method handles the "meat" of the template processing.
	 *
	 * It takes care of caching the output (via {@link SS_Cache}), as well as 
	 * replacing the special "$Content" and "$Layout" placeholders with their 
	 * respective subtemplates.
	 *
	 * The method injects extra HTML in the header via {@link Requirements::includeInHTML()}.
	 * 
	 * Note: You can call this method indirectly by {@link ViewableData->renderWith()}.
	 * 
	 * @param ViewableData $item
	 * @param array|null $arguments - arguments to an included template
	 * @param Object $inheritedScope - the current scope of a parent template including a sub-template
	 *
	 * @return HTMLText Parsed template output.
	 */
	public function process($item, $arguments = null, $inheritedScope = null) {
		SSViewer::$topLevel[] = $item;

		if(isset($this->chosenTemplates['main'])) {
			$template = $this->chosenTemplates['main'];
		} else {
			$keys = array_keys($this->chosenTemplates);
			$key = reset($keys);
			$template = $this->chosenTemplates[$key];
		}
		
		$cacheFile = TEMP_FOLDER . "/.cache" 
			. str_replace(array('\\','/',':'), '.', Director::makeRelative(realpath($template)));
		$lastEdited = filemtime($template);

		if(!file_exists($cacheFile) || filemtime($cacheFile) < $lastEdited) {
			$content = file_get_contents($template);
			$content = $this->parseTemplateContent($content, $template);
			
			$fh = fopen($cacheFile,'w');
			fwrite($fh, $content);
			fclose($fh);
		}

		$underlay = array('I18NNamespace' => basename($template));

		// Makes the rendered sub-templates available on the parent item,
		// through $Content and $Layout placeholders.
		foreach(array('Content', 'Layout') as $subtemplate) {
			if(isset($this->chosenTemplates[$subtemplate])) {
				$subtemplateViewer = new SSViewer($this->chosenTemplates[$subtemplate], $this->parser);
				$subtemplateViewer->includeRequirements(false);
				$subtemplateViewer->setPartialCacheStore($this->getPartialCacheStore());

				$underlay[$subtemplate] = $subtemplateViewer->process($item, $arguments);
			}
		}

		$output = $this->includeGeneratedTemplate($cacheFile, $item, $arguments, $underlay, $inheritedScope);
		
		if($this->includeRequirements) {
			$output = Requirements::includeInHTML($template, $output);
		}
		
		array_pop(SSViewer::$topLevel);

		// If we have our crazy base tag, then fix # links referencing the current page.
		
		$rewrite = Config::inst()->get('SSViewer', 'rewrite_hash_links');
		if($this->rewriteHashlinks && $rewrite) {
			if(strpos($output, '<base') !== false) {
				if($rewrite === 'php') {
					$thisURLRelativeToBase = "<?php echo Convert::raw2att(preg_replace(\"/^(\\\\/)+/\", \"/\", \$_SERVER['REQUEST_URI'])); ?>";
				} else { 
					$thisURLRelativeToBase = Convert::raw2att(preg_replace("/^(\\/)+/", "/", $_SERVER['REQUEST_URI']));
				}

				$output = preg_replace('/(<a[^>]+href *= *)"#/i', '\\1"' . $thisURLRelativeToBase . '#', $output);
			}
		}

		return DBField::create_field('HTMLText', $output, null, array('shortcodes' => false));
	}

	/**
	 * Execute the given template, passing it the given data.
	 * Used by the <% include %> template tag to process templates.
	 * 
	 * @param string $template Template name
	 * @param mixed $data Data context
	 * @param array $arguments Additional arguments
	 * @return string Evaluated result
	 */
	public static function execute_template($template, $data, $arguments = null, $scope = null) {
		$v = new SSViewer($template);
		$v->includeRequirements(false);

		return $v->process($data, $arguments, $scope);
	}
	
	/**
	 * Execute the evaluated string, passing it the given data.
	 * Used by partial caching to evaluate custom cache keys expressed using
	 * template expressions
	 * 
	 * @param string $content Input string
	 * @param mixed $data Data context
	 * @param array $arguments Additional arguments
	 * @return string Evaluated result
	 */
	public static function execute_string($content, $data, $arguments = null) {
		$v = SSViewer::fromString($content);
		$v->includeRequirements(false);
		
		return $v->process($data, $arguments);
	}

	public function parseTemplateContent($content, $template="") {
		return $this->parser->compileString(
			$content,
			$template,
			Director::isDev() && Config::inst()->get('SSViewer', 'source_file_comments')
		);
	}

	/**
	 * Returns the filenames of the template that will be rendered.  It is a map that may contain
	 * 'Content' & 'Layout', and will have to contain 'main'
	 */
	public function templates() {
		return $this->chosenTemplates;
	}
	
	/**
	 * @param string $type "Layout" or "main"
	 * @param string $file Full system path to the template file
	 */
	public function setTemplateFile($type, $file) {
		$this->chosenTemplates[$type] = $file;
	}
	
	/**
	 * Return an appropriate base tag for the given template.
	 * It will be closed on an XHTML document, and unclosed on an HTML document.
	 * 
	 * @param $contentGeneratedSoFar The content of the template generated so far; it should contain
	 * the DOCTYPE declaration.
	 */
	public static function get_base_tag($contentGeneratedSoFar) {
		$base = Director::absoluteBaseURL();
		
		// Is the document XHTML?
		if(preg_match('/<!DOCTYPE[^>]+xhtml/i', $contentGeneratedSoFar)) {
			return "<base href=\"$base\" />";
		} else {
			return "<base href=\"$base\"><!--[if lte IE 6]></base><![endif]-->";
		}
	}
}

/**
 * Special SSViewer that will process a template passed as a string, rather than a filename.
 * @package framework
 * @subpackage view
 */
class SSViewer_FromString extends SSViewer {

	/**
	 * The global template caching behaviour if no instance override is specified
	 * @config
	 * @var bool
	 */
	private static $cache_template = true;
	
	/**
	 * The template to use
	 * @var string
	 */
	protected $content;
	
	/**
	 * Indicates whether templates should be cached
	 * @var bool
	 */
	protected $cacheTemplate;
	
	public function __construct($content, TemplateParser $parser = null) {
		$this->setParser($parser ?: Injector::inst()->get('SSTemplateParser'));
		$this->content = $content;
	}

	public function process($item, $arguments = null, $scope = null) {
		$hash = sha1($this->content);
		$cacheFile = TEMP_FOLDER . "/.cache.$hash";

		if(!file_exists($cacheFile) || isset($_GET['flush'])) {
			$content = $this->parseTemplateContent($this->content, "string sha1=$hash");
			$fh = fopen($cacheFile,'w');
			fwrite($fh, $content);
			fclose($fh);
		}

		$val = $this->includeGeneratedTemplate($cacheFile, $item, $arguments, null, $scope);

		if ($this->cacheTemplate !== null) {
			$cacheTemplate = $this->cacheTemplate;
		} else {
			$cacheTemplate = Config::inst()->get('SSViewer_FromString', 'cache_template');
		}
		
		if (!$cacheTemplate) {
			unlink($cacheFile);
		}

		return $val;
	}
	
	/**
	 * @param boolean $cacheTemplate
	 */
	public function setCacheTemplate($cacheTemplate) {
		$this->cacheTemplate = (bool) $cacheTemplate;
	}
	
	/**
	 * @return boolean
	 */
	public function getCacheTemplate() {
		return $this->cacheTemplate;
	}
}
