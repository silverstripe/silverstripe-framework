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
 */
class SSViewer_Scope {
	
	// The stack of previous "global" items
	// And array of item, itemIterator, pop_index, up_index, current_index
	private $itemStack = array(); 
	
	private $item; // The current "global" item (the one any lookup starts from)
	protected $itemIterator; // If we're looping over the current "global" item, here's the iterator that tracks with item we're up to
	protected $itemIteratorTotal;   //Total number of items in the iterator
	
	private $popIndex; // A pointer into the item stack for which item should be scope on the next pop call
	private $upIndex; // A pointer into the item stack for which item is "up" from this one
	private $currentIndex; // A pointer into the item stack for which item is this one (or null if not in stack yet)
	
	private $localIndex;


	function __construct($item){
		$this->item = $item;
		$this->localIndex=0;
		$this->itemStack[] = array($this->item, null, null, null, 0);
	}
	
	function getItem(){
		return $this->itemIterator ? $this->itemIterator->current() : $this->item;
	}
	
	function resetLocalScope(){
		list($this->item, $this->itemIterator, $this->popIndex, $this->upIndex, $this->currentIndex) = $this->itemStack[$this->localIndex];
		array_splice($this->itemStack, $this->localIndex+1);
	}
	
	function obj($name){
		
		switch ($name) {
			case 'Up':
				list($this->item, $this->itemIterator, $unused2, $this->upIndex, $this->currentIndex) = $this->itemStack[$this->upIndex];
				break;
			
			case 'Top':
				list($this->item, $this->itemIterator, $unused2, $this->upIndex, $this->currentIndex) = $this->itemStack[0];
				break;
			
			default:
				$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
				
				$arguments = func_get_args();
				$this->item = call_user_func_array(array($on, 'obj'), $arguments);
				
				$this->itemIterator = null;
				$this->upIndex = $this->currentIndex ? $this->currentIndex : count($this->itemStack)-1;
				$this->currentIndex = count($this->itemStack);
				break;
		}
		
		$this->itemStack[] = array($this->item, $this->itemIterator, null, $this->upIndex, $this->currentIndex);
		return $this;
	}
	
	function pushScope(){
		$newLocalIndex = count($this->itemStack)-1;
		
		$this->popIndex = $this->itemStack[$newLocalIndex][2] = $this->localIndex;
		$this->localIndex = $newLocalIndex;
		
		// We normally keep any previous itemIterator around, so local $Up calls reference the right element. But
		// once we enter a new global scope, we need to make sure we use a new one
		$this->itemIterator = $this->itemStack[$newLocalIndex][1] = null;
		
		return $this;
	}

	function popScope(){
		$this->localIndex = $this->popIndex;
		$this->resetLocalScope();
		
		return $this;
	}
	
	function next(){
		if (!$this->item) return false;
		
		if (!$this->itemIterator) {
			if (is_array($this->item)) $this->itemIterator = new ArrayIterator($this->item);
			else $this->itemIterator = $this->item->getIterator();
			
			$this->itemStack[$this->localIndex][1] = $this->itemIterator;
			$this->itemIteratorTotal = iterator_count($this->itemIterator); //count the total number of items
			$this->itemIterator->rewind();
		}
		else {
			$this->itemIterator->next();
		}
		
		$this->resetLocalScope();

		if (!$this->itemIterator->valid()) return false;
		return $this->itemIterator->key();
	}
	
	function __call($name, $arguments) {
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;
		$retval = call_user_func_array(array($on, $name), $arguments);
		
		$this->resetLocalScope();
		return $retval;
	}
}

class SSViewer_BasicIteratorSupport implements TemplateIteratorProvider {

	protected $iteratorPos;
	protected $iteratorTotalItems;

	public static function getExposedVariables() {
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
 */
class SSViewer_DataPresenter extends SSViewer_Scope {
	
	private static $extras = array();
	private static $iteratorSupport = array();

	function __construct($item){
		parent::__construct($item);

		if (count(self::$iteratorSupport) == 0) {   //build up extras array only once per request
			$this->createCallableArray(self::$iteratorSupport, "TemplateIteratorProvider", true);   //call non-statically
		}

		if (count(self::$extras) == 0) {   //build up extras array only once per request
			//get all the exposed variables from all classes that implement the TemplateGlobalProvider interface
			$this->createCallableArray(self::$extras, "TemplateGlobalProvider");
		}
	}

	protected function createCallableArray(&$extraArray, $interfaceToQuery, $createObject = false) {
		$implementers = ClassInfo::implementorsOf($interfaceToQuery);
		if ($implementers && count($implementers) > 0) {
			foreach($implementers as $implementer) {
				if ($createObject) $implementer = new $implementer();   //create a new instance of the object for method calls
				$exposedVariables = $implementer::getExposedVariables();    //get the exposed variables

				foreach($exposedVariables as $varName => $methodName) {
					if (!$varName || is_numeric($varName)) $varName = $methodName;  //array has just a single value, use it for both key and value

					//e.g. "array(Director, absoluteBaseURL)" means call "Director::absoluteBaseURL()"
					$extraArray[$varName] = array($implementer, $methodName);
					$firstCharacter = substr($varName, 0, 1);

					if ((strtoupper($firstCharacter) === $firstCharacter)) {    //is uppercase, so save the lowercase version, too
						$extraArray[lcfirst($varName)] = array($implementer, $methodName);    //callable array
					} else {    //is lowercase, save a version so it also works uppercase
						$extraArray[ucfirst($varName)] = array($implementer, $methodName);
					}
				}
			}
		}
	}
	
	function __call($name, $arguments) {
		//TODO: make local functions take priority over global functions
		
		$property = $arguments[0];  //the name of the function being called
		if ($arguments[1]) $params = $arguments[1]; //the function parameters in an array
		else $params = array();

		//call a "global" function
		if (array_key_exists($property, self::$extras) || array_key_exists($property, self::$iteratorSupport)) {
			$this->resetLocalScope();   //if we are inside a chain (e.g. $A.B.C.Up.E) break out to the beginning of it

			//special case for the iterator, which need current index and total number of items
			if (array_key_exists($property, self::$iteratorSupport)) {
				//set the current iterator position and total (the object instance is the first item in the callable array)
				self::$iteratorSupport[$property][0]->iteratorProperties($this->itemIterator->key(), $this->itemIteratorTotal);
				$value = self::$iteratorSupport[$property];
			} else {    //normal case of extras call
				$value = self::$extras[$property];  //get the method call
			}

			//only call callable functions
			if (is_callable($value)) {
				//$value = call_user_func_array($value, array_slice($arguments, 1));
				$value = call_user_func_array($value, $params);
			}

			switch ($name) {
				case 'hasValue':
					return (bool)$value;
				default:    //XML_val
					return $value;
			}
		}

		$callResult = parent::__call($name, $arguments);
		return $callResult;
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
 * If you put ?flush=all on your URL, it will force the template to be recompiled.  
 *
 * @see http://doc.silverstripe.org/themes
 * @see http://doc.silverstripe.org/themes:developing

 * 
 * @package sapphire
 * @subpackage view
 */
class SSViewer {
	
	/**
	 * @var boolean $source_file_comments
	 */
	protected static $source_file_comments = false;
	
	/**
	 * Set whether HTML comments indicating the source .SS file used to render this page should be
	 * included in the output.  This is enabled by default
	 *
	 * @param boolean $val
	 */
	static function set_source_file_comments($val) {
		self::$source_file_comments = $val;
	}
	
	/**
	 * @return boolean
	 */
	static function get_source_file_comments() {
		return self::$source_file_comments;
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
	 * @var string
	 */
	protected static $current_theme = null;
	
	/**
	 * @var string
	 */
	protected static $current_custom_theme = null;
	
	/**
	 * Create a template from a string instead of a .ss file
	 * 
	 * @return SSViewer
	 */
	static function fromString($content) {
		return new SSViewer_FromString($content);
	}
	
	/**
	 * @param string $theme The "base theme" name (without underscores). 
	 */
	static function set_theme($theme) {
		self::$current_theme = $theme;
		//Static publishing needs to have a theme set, otherwise it defaults to the content controller theme
		if(!is_null($theme))
			self::$current_custom_theme=$theme;
	}
	
	/**
	 * @return string 
	 */
	static function current_theme() {
		return self::$current_theme;
	}
	
	/**
	 * Returns the path to the theme folder
	 *
	 * @return String
	 */
	static function get_theme_folder() {
		return self::current_theme() ? THEMES_DIR . "/" . self::current_theme() : project();
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
				if ($subthemes || !strpos($item, '_')) {
					$themes[$item] = $item;
				}
			}
		}

		return $themes;
	}

	/**
	 * @return string
	 */
	static function current_custom_theme(){
		return self::$current_custom_theme;
	}
	
	/**
	 * @param string|array $templateList If passed as a string with .ss extension, used as the "main" template.
	 *  If passed as an array, it can be used for template inheritance (first found template "wins").
	 *  Usually the array values are PHP class names, which directly correlate to template names.
	 *  <code>
	 *  array('MySpecificPage', 'MyPage', 'Page')
	 *  </code>
	 */
	public function __construct($templateList) {
		// flush template manifest cache if requested
		if (isset($_GET['flush']) && $_GET['flush'] == 'all') {
			if(Director::isDev() || Director::is_cli() || Permission::check('ADMIN')) {
				self::flush_template_cache();
			} else {
				return Security::permissionFailure(null, 'Please log in as an administrator to flush the template cache.');
			}
		}
		
		if(substr((string) $templateList,-3) == '.ss') {
			$this->chosenTemplates['main'] = $templateList;
		} else {
			$this->chosenTemplates = SS_TemplateLoader::instance()->findTemplates(
				$templateList, self::current_theme()
			);
		}

		if(!$this->chosenTemplates) user_error("None of these templates can be found in theme '"
			. self::current_theme() . "': ". implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);
	}
	
	/**
	 * Returns true if at least one of the listed templates exists
	 */
	public static function hasTemplate($templates) {
		$manifest = SS_TemplateLoader::instance()->getManifest();

		foreach ((array) $templates as $template) {
			if ($manifest->getTemplate($template)) return true;
		}

		return false;
	}
	
	/**
	 * Set a global rendering option.
	 * The following options are available:
	 *  - rewriteHashlinks: If true (the default), <a href="#..."> will be rewritten to contain the 
	 *    current URL.  This lets it play nicely with our <base> tag.
	 *  - If rewriteHashlinks = 'php' then, a piece of PHP script will be inserted before the hash 
	 *    links: "<?php echo $_SERVER['REQUEST_URI']; ?>".  This is useful if you're generating a 
	 *    page that will be saved to a .php file and may be accessed from different URLs.
	 */
	public static function setOption($optionName, $optionVal) {
		SSViewer::$options[$optionName] = $optionVal;
	}
	protected static $options = array(
		'rewriteHashlinks' => true,
	);
    
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
		self::$options['rewriteHashlinks'] = false;
		return $this;
	}
	
	public function exists() {
		return $this->chosenTemplates;
	}

	/**
	 * @param string $identifier A template name without '.ss' extension or path
	 * @param string $type The template type, either "main", "Includes" or "Layout"
	 * @return string Full system path to a template file
	 */
	public static function getTemplateFileByType($identifier, $type) {
		$loader = SS_TemplateLoader::instance();
		$found  = $loader->findTemplates("$type/$identifier", self::current_theme());

		if ($found) {
			return $found['main'];
		}
	}

	/**
	 * @ignore
	 */
	static private $flushed = false;
	
	/**
	 * Clears all parsed template files in the cache folder.
	 *
	 * Can only be called once per request (there may be multiple SSViewer instances).
	 */
	static function flush_template_cache() {
		if (!self::$flushed) {
			$dir = dir(TEMP_FOLDER);
			while (false !== ($file = $dir->read())) {
				if (strstr($file, '.cache')) { unlink(TEMP_FOLDER.'/'.$file); }
			}
			self::$flushed = true;
		}
	}
	
	/**
	 * The process() method handles the "meat" of the template processing.
	 * It takes care of caching the output (via {@link SS_Cache}),
	 * as well as replacing the special "$Content" and "$Layout"
	 * placeholders with their respective subtemplates.
	 * The method injects extra HTML in the header via {@link Requirements::includeInHTML()}.
	 * 
	 * Note: You can call this method indirectly by {@link ViewableData->renderWith()}.
	 * 
	 * @param ViewableData $item
	 * @param SS_Cache $cache Optional cache backend
	 * @return String Parsed template output.
	 */
	public function process($item, $cache = null) {
		SSViewer::$topLevel[] = $item;
		
		if (!$cache) $cache = SS_Cache::factory('cacheblock');
		
		if(isset($this->chosenTemplates['main'])) {
			$template = $this->chosenTemplates['main'];
		} else {
			$template = $this->chosenTemplates[ reset($dummy = array_keys($this->chosenTemplates)) ];
		}
		
		if(isset($_GET['debug_profile'])) Profiler::mark("SSViewer::process", " for $template");
		$cacheFile = TEMP_FOLDER . "/.cache" . str_replace(array('\\','/',':'), '.', Director::makeRelative(realpath($template)));

		$lastEdited = filemtime($template);

		if(!file_exists($cacheFile) || filemtime($cacheFile) < $lastEdited || isset($_GET['flush'])) {
			if(isset($_GET['debug_profile'])) Profiler::mark("SSViewer::process - compile", " for $template");
			
			$content = file_get_contents($template);
			$content = SSViewer::parseTemplateContent($content, $template);
			
			$fh = fopen($cacheFile,'w');
			fwrite($fh, $content);
			fclose($fh);

			if(isset($_GET['debug_profile'])) Profiler::unmark("SSViewer::process - compile", " for $template");
		}
	
		
		if(isset($_GET['showtemplate']) && !Director::isLive()) {
			$lines = file($cacheFile);
			echo "<h2>Template: $cacheFile</h2>";
			echo "<pre>";
			foreach($lines as $num => $line) {
				echo str_pad($num+1,5) . htmlentities($line, ENT_COMPAT, 'UTF-8');
			}
			echo "</pre>";
		}
		
		// Makes the rendered sub-templates available on the parent item,
		// through $Content and $Layout placeholders.
		foreach(array('Content', 'Layout') as $subtemplate) {
			if(isset($this->chosenTemplates[$subtemplate])) {
				$subtemplateViewer = new SSViewer($this->chosenTemplates[$subtemplate]);
				$item = $item->customise(array(
					$subtemplate => $subtemplateViewer->process($item, $cache)
				));
			}
		}
		
		$scope = new SSViewer_DataPresenter($item, array('I18NNamespace' => basename($template)));
		$val = "";
		
		include($cacheFile);

		$output = Requirements::includeInHTML($template, $val);
		
		array_pop(SSViewer::$topLevel);

		if(isset($_GET['debug_profile'])) Profiler::unmark("SSViewer::process", " for $template");
		
		// If we have our crazy base tag, then fix # links referencing the current page.
		if($this->rewriteHashlinks && self::$options['rewriteHashlinks']) {
			if(strpos($output, '<base') !== false) {
				if(SSViewer::$options['rewriteHashlinks'] === 'php') { 
					$thisURLRelativeToBase = "<?php echo \$_SERVER['REQUEST_URI']; ?>"; 
				} else { 
					$thisURLRelativeToBase = Director::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI'])); 
				}
				$output = preg_replace('/(<a[^>]+href *= *)"#/i', '\\1"' . $thisURLRelativeToBase . '#', $output);
			}
		}

		return $output;
	}

	/**
	 * Execute the given template, passing it the given data.
	 * Used by the <% include %> template tag to process templates.
	 */
	static function execute_template($template, $data) {
		$v = new SSViewer($template);
		return $v->process($data);
	}

	static function parseTemplateContent($content, $template="") {			
		return SSTemplateParser::compileString($content, $template, Director::isDev() && self::$source_file_comments);
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
	static function get_base_tag($contentGeneratedSoFar) {
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
 * @package sapphire
 * @subpackage view
 */
class SSViewer_FromString extends SSViewer {
	protected $content;
	
	public function __construct($content) {
		$this->content = $content;
	}
	
	public function process($item, $cache = null) {
		$template = SSViewer::parseTemplateContent($this->content, "string sha1=".sha1($this->content));

		$tmpFile = tempnam(TEMP_FOLDER,"");
		$fh = fopen($tmpFile, 'w');
		fwrite($fh, $template);
		fclose($fh);

		if(isset($_GET['showtemplate']) && $_GET['showtemplate']) {
			$lines = file($tmpFile);
			echo "<h2>Template: $tmpFile</h2>";
			echo "<pre>";
			foreach($lines as $num => $line) {
				echo str_pad($num+1,5) . htmlentities($line, ENT_COMPAT, 'UTF-8');
			}
			echo "</pre>";
		}

		$scope = new SSViewer_DataPresenter($item);
		$val = "";
		$valStack = array();
		
		$cache = SS_Cache::factory('cacheblock');
		
		include($tmpFile);
		unlink($tmpFile);
		

		return $val;
	}
}
