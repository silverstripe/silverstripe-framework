<?php
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
 * <b>Manifest File and Structure</b>
 * 
 * Works with the global $_TEMPLATE_MANIFEST which is compiled by {@link ManifestBuilder->getTemplateManifest()}.
 * This associative array lists all template filepaths by "identifier", meaning the name
 * of the template without its path or extension.
 * 
 * Example:
 * <code>
 * array(
 *  'LeftAndMain' => 
 *  array (
 * 	'main' => '/my/system/path/cms/templates/LeftAndMain.ss',
 *  ),
 * 'CMSMain_left' => 
 *   array (
 *     'Includes' => '/my/system/path/cms/templates/Includes/CMSMain_left.ss',
 *   ),
 * 'Page' => 
 *   array (
 *     'themes' => 
 *     array (
 *       'blackcandy' => 
 *       array (
 *         'Layout' => '/my/system/path/themes/blackcandy/templates/Layout/Page.ss',
 *         'main' => '/my/system/path/themes/blackcandy/templates/Page.ss',
 *       ),
 *       'blue' => 
 *       array (
 *         'Layout' => '/my/system/path/themes/mysite/templates/Layout/Page.ss',
 *         'main' => '/my/system/path/themes/mysite/templates/Page.ss',
 *       ),
 *     ),
 *   ),
 *   // ...
 * )
 * </code>
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
		global $_TEMPLATE_MANIFEST;

		// flush template manifest cache if requested
		if (isset($_GET['flush']) && $_GET['flush'] == 'all') {
			if(Director::isDev() || Director::is_cli() || Permission::check('ADMIN')) {
				self::flush_template_cache();
			} else {
				return Security::permissionFailure(null, 'Please log in as an administrator to flush the template cache.');
			}
		}
		
		if(is_string($templateList) && substr((string) $templateList,-3) == '.ss') {
			$this->chosenTemplates['main'] = $templateList;
		} else {
			if(!is_array($templateList)) $templateList = array($templateList);
			
			if(isset($_GET['debug_request'])) Debug::message("Selecting templates from the following list: " . implode(", ", $templateList));

			foreach($templateList as $template) {
				// if passed as a partial directory (e.g. "Layout/Page"), split into folder and template components
				if(strpos($template,'/') !== false) list($templateFolder, $template) = explode('/', $template, 2);
				else $templateFolder = null;

				// Use the theme template if available
				if(self::current_theme() && isset($_TEMPLATE_MANIFEST[$template]['themes'][self::current_theme()])) {
					$this->chosenTemplates = array_merge(
						$_TEMPLATE_MANIFEST[$template]['themes'][self::current_theme()], 
						$this->chosenTemplates
					);
					
					if(isset($_GET['debug_request'])) Debug::message("Found template '$template' from main theme '" . self::current_theme() . "': " . var_export($_TEMPLATE_MANIFEST[$template]['themes'][self::current_theme()], true));
				}
				
				// Fall back to unthemed base templates
				if(isset($_TEMPLATE_MANIFEST[$template]) && (array_keys($_TEMPLATE_MANIFEST[$template]) != array('themes'))) {
					$this->chosenTemplates = array_merge(
						$_TEMPLATE_MANIFEST[$template], 
						$this->chosenTemplates
					);
					
					if(isset($_GET['debug_request'])) Debug::message("Found template '$template' from main template archive, containing the following items: " . var_export($_TEMPLATE_MANIFEST[$template], true));
					
					unset($this->chosenTemplates['themes']);
				}

				if($templateFolder) {
					$this->chosenTemplates['main'] = $this->chosenTemplates[$templateFolder];
					unset($this->chosenTemplates[$templateFolder]);
				}
			}

			if(isset($_GET['debug_request'])) Debug::message("Final template selections made: " . var_export($this->chosenTemplates, true));

		}

		if(!$this->chosenTemplates) user_error("None of these templates can be found in theme '"
			. self::current_theme() . "': ". implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);
			
	}
	
	/**
	 * Returns true if at least one of the listed templates exists
	 */
	static function hasTemplate($templateList) {
		if(!is_array($templateList)) $templateList = array($templateList);
	
		global $_TEMPLATE_MANIFEST;
		foreach($templateList as $template) {
			if(strpos($template,'/') !== false) list($templateFolder, $template) = explode('/', $template, 2);
			if(isset($_TEMPLATE_MANIFEST[$template])) return true;
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
	
	/**
	 * @param String
	 * @return Mixed
	 */
	static function getOption($optionName) {
		return SSViewer::$options[$optionName];
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
	 * Searches for a template name in the current theme:
	 * - themes/mytheme/templates
	 * - themes/mytheme/templates/Includes
	 * Falls back to unthemed template files.
	 * 
	 * Caution: Doesn't search in any /Layout folders.
	 * 
	 * @param string $identifier A template name without '.ss' extension or path.
	 * @return string Full system path to a template file
	 */
	public static function getTemplateFile($identifier) {
		global $_TEMPLATE_MANIFEST;
		
		$includeTemplateFile = self::getTemplateFileByType($identifier, 'Includes');
		if($includeTemplateFile) return $includeTemplateFile;
		
		$mainTemplateFile = self::getTemplateFileByType($identifier, 'main');
		if($mainTemplateFile) return $mainTemplateFile;
		
		return false;
	}
	
	/**
	 * @param string $identifier A template name without '.ss' extension or path
	 * @param string $type The template type, either "main", "Includes" or "Layout"
	 * @return string Full system path to a template file
	 */
	public static function getTemplateFileByType($identifier, $type) {
		global $_TEMPLATE_MANIFEST;
		if(self::current_theme() && isset($_TEMPLATE_MANIFEST[$identifier]['themes'][self::current_theme()][$type])) {
			return $_TEMPLATE_MANIFEST[$identifier]['themes'][self::current_theme()][$type];
		} else if(isset($_TEMPLATE_MANIFEST[$identifier][$type])){
			return $_TEMPLATE_MANIFEST[$identifier][$type];
		} else {
			return false;
		}
	}
	
	/**
	 * Used by <% include Identifier %> statements to get the full
	 * unparsed content of a template file.
	 * 
	 * @uses getTemplateFile()
	 * @param string $identifier A template name without '.ss' extension or path.
	 * @return string content of template
	 */
	public static function getTemplateContent($identifier) {
		if(!SSViewer::getTemplateFile($identifier)) {
			return null;
		}
		
		$content = file_get_contents(SSViewer::getTemplateFile($identifier));

		// $content = "<!-- getTemplateContent() :: identifier: $identifier -->". $content; 
		// Adds an i18n namespace to all _t(...) calls without an existing one
		// to avoid confusion when using the include in different contexts.
		// Entities without a namespace are deprecated, but widely used.
		$content = ereg_replace('<' . '% +_t\((\'([^\.\']*)\'|"([^\."]*)")(([^)]|\)[^ ]|\) +[^% ])*)\) +%' . '>', '<?= _t(\''. $identifier . '.ss' . '.\\2\\3\'\\4) ?>', $content);

		// Remove UTF-8 byte order mark
		// This is only necessary if you don't have zend-multibyte enabled.
		if(substr($content, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
			$content = substr($content, 3);
		}

		return $content;
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
				echo str_pad($num+1,5) . htmlentities($line);
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
		
		$itemStack = array();
		$val = "";
		$valStack = array();
		
		include($cacheFile);

		$output = $val;		
		$output = Requirements::includeInHTML($template, $output);
		
		array_pop(SSViewer::$topLevel);

		if(isset($_GET['debug_profile'])) Profiler::unmark("SSViewer::process", " for $template");
		
		// If we have our crazy base tag, then fix # links referencing the current page.
		if($this->rewriteHashlinks && self::$options['rewriteHashlinks']) {
			if(strpos($output, '<base') !== false) {
				if(SSViewer::$options['rewriteHashlinks'] === 'php') { 
					$thisURLRelativeToBase = "<?php echo strip_tags(\$_SERVER['REQUEST_URI']); ?>"; 
				} else { 
					$thisURLRelativeToBase = strip_tags($_SERVER['REQUEST_URI']); 
				}
				$output = preg_replace('/(<a[^>]+href *= *)"#/i', '\\1"' . $thisURLRelativeToBase . '#', $output);
			}
		}

		return $output;
	}

	static function parseTemplateContent($content, $template="") {			
		// Remove UTF-8 byte order mark:
		// This is only necessary if you don't have zend-multibyte enabled.
		if(substr($content, 0,3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
			$content = substr($content, 3);
		}

		// Add template filename comments on dev sites
		if(Director::isDev() && self::$source_file_comments && $template && stripos($content, "<?xml") === false) {
			// If this template is a full HTML page, then put the comments just inside the HTML tag to prevent any IE glitches
			if(stripos($content, "<html") !== false) {
				$content = preg_replace('/(<html[^>]*>)/i', "\\1<!-- template $template -->", $content);
				$content = preg_replace('/(<\/html[^>]*>)/i', "\\1<!-- end template $template -->", $content);
			} else {
				$content = "<!-- template $template -->\n" . $content . "\n<!-- end template $template -->";
			}
		}
		
		while(true) {
			$oldContent = $content;
			
			// Add include filename comments on dev sites
			if(Director::isDev() && self::$source_file_comments) $replacementCode = 'return "<!-- include " . SSViewer::getTemplateFile($matches[1]) . " -->\n" 
				. SSViewer::getTemplateContent($matches[1]) 
				. "\n<!-- end include " . SSViewer::getTemplateFile($matches[1]) . " -->";';
			else $replacementCode = 'return SSViewer::getTemplateContent($matches[1]);';
			
			$content = preg_replace_callback('/<' . '% include +([A-Za-z0-9_]+) +%' . '>/', create_function(
				'$matches', $replacementCode
				), $content);
			if($oldContent == $content) break;
		}
		
		// $val, $val.property, $val(param), etc.
		$replacements = array(
			'/<%--.*--%>/U' =>  '',
			'/\$Iteration/' =>  '<?= {dlr}key ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",array("\\2","\\3"),true)->obj("\\4",null,true)->XML_val("\\5",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",array("\\2","\\3"),true)->XML_val("\\4",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)}/' => '<?= {dlr}item->XML_val("\\1",array("\\2","\\3"),true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",array("\\2"),true)->obj("\\3",null,true)->XML_val("\\4",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",array("\\2"),true)->XML_val("\\3",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)}/' => '<?= {dlr}item->XML_val("\\1",array("\\2"),true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",null,true)->obj("\\2",null,true)->XML_val("\\3",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",null,true)->XML_val("\\2",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)}/' => '<?= {dlr}item->XML_val("\\1",null,true) ?>\\2',

			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\(([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1")->XML_val("\\2",array("\\3"),true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\(([^),]+), *([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1")->XML_val("\\2",array("\\3", "\\4"),true) ?>\\5',

			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",array("\\2","\\3"),true)->obj("\\4",null,true)->XML_val("\\5",null,true) ?>\\6',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",array("\\2","\\3"),true)->XML_val("\\4",null,true) ?>\\5',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",array("\\2","\\3"),true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",array("\\2"),true)->obj("\\3",null,true)->XML_val("\\4",null,true) ?>\\5',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",array("\\2"),true)->XML_val("\\3",null,true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",array("\\2"),true) ?>\\3',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",null,true)->obj("\\2",null,true)->XML_val("\\3",null,true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",null,true)->XML_val("\\2",null,true) ?>\\3',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",null,true) ?>\\2',
		);
		
		$content = preg_replace(array_keys($replacements), array_values($replacements), $content);
		$content = str_replace('{dlr}','$',$content);

		// Cache block
		$content = SSViewer_PartialParser::process($template, $content);

		// legacy
		$content = ereg_replace('<!-- +pc +([A-Za-z0-9_(),]+) +-->', '<' . '% control \\1 %' . '>', $content);
		$content = ereg_replace('<!-- +pc_end +-->', '<' . '% end_control %' . '>', $content);
		
		// < % control Foo % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1")) foreach($loop as $key => $item) { ?>', $content);
		// < % control Foo.Bar % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? array_push($itemStack, $item); if(($loop = $item->obj("\\1")) && ($loop = $loop->obj("\\2"))) foreach($loop as $key => $item) { ?>', $content);
		// < % control Foo.Bar(Baz) % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)\\(([^),]+)\\) +%' . '>', '<? array_push($itemStack, $item); if(($loop = $item->obj("\\1")) && ($loop = $loop->obj("\\2", array("\\3")))) foreach($loop as $key => $item) { ?>', $content);
		// < % control Foo(Bar) % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([^),]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2"))) foreach($loop as $key => $item) { ?>', $content);
		// < % control Foo(Bar, Baz) % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([^),]+), *([^),]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2","\\3"))) foreach($loop as $key => $item) { ?>', $content);
		// < % control Foo(Bar, Baz, Buz) % >
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([^),]+), *([^),]+), *([^),]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2", "\\3", "\\4"))) foreach($loop as $key => $item) { ?>', $content);
		$content = ereg_replace('<' . '% +end_control +%' . '>', '<? } $item = array_pop($itemStack); ?>', $content);
		$content = ereg_replace('<' . '% +debug +%' . '>', '<? Debug::show($item) ?>', $content);
		$content = ereg_replace('<' . '% +debug +([A-Za-z0-9_]+) +%' . '>', '<? Debug::show($item->cachedCall("\\1")) ?>', $content);

		// < % if val1.property % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? if($item->obj("\\1",null,true)->hasValue("\\2")) {  ?>', $content);
		
		// < % if val1(parameter) % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+)\\(([A-Za-z0-9_-]+)\\) +%' . '>', '<? if($item->hasValue("\\1",array("\\2"))) {  ?>', $content);

		// < % if val1 % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+) +%' . '>', '<? if($item->hasValue("\\1")) {  ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) +%' . '>', '<? } else if($item->hasValue("\\1")) {  ?>', $content);

		// < % if val1 || val2 % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+) *\\|\\|? *([A-Za-z0-9_]+) +%' . '>', '<? if($item->hasValue("\\1") || $item->hasValue("\\2")) { ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) *\\|\\|? *([A-Za-z0-9_]+) +%' . '>', '<? else_if($item->hasValue("\\1") || $item->hasValue("\\2")) { ?>', $content);

		// < % if val1 && val2 % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+) *&&? *([A-Za-z0-9_]+) +%' . '>', '<? if($item->hasValue("\\1") && $item->hasValue("\\2")) { ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) *&&? *([A-Za-z0-9_]+) +%' . '>', '<? else_if($item->hasValue("\\1") && $item->hasValue("\\2")) { ?>', $content);

		// < % if val1 == val2 % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+) *==? *"?([A-Za-z0-9_-]+)"? +%' . '>', '<? if($item->XML_val("\\1",null,true) == "\\2") {  ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) *==? *"?([A-Za-z0-9_-]+)"? +%' . '>', '<? } else if($item->XML_val("\\1",null,true) == "\\2") {  ?>', $content);
		
		// < % if val1 != val2 % >
		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+) *!= *"?([A-Za-z0-9_-]+)"? +%' . '>', '<? if($item->XML_val("\\1",null,true) != "\\2") {  ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) *!= *"?([A-Za-z0-9_-]+)"? +%' . '>', '<? } else if($item->XML_val("\\1",null,true) != "\\2") {  ?>', $content);

		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+) +%' . '>', '<? } else if(($test = $item->cachedCall("\\1")) && ((!is_object($test) && $test) || ($test && $test->exists()) )) {  ?>', $content);

		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? $test = $item->obj("\\1",null,true)->cachedCall("\\2"); if((!is_object($test) && $test) || ($test && $test->exists())) {  ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? } else if(($test = $item->obj("\\1",null,true)->cachedCall("\\2")) && ((!is_object($test) && $test) || ($test && $test->exists()) )) {  ?>', $content);

		$content = ereg_replace('<' . '% +if +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? $test = $item->obj("\\1",null,true)->obj("\\2",null,true)->cachedCall("\\3"); if((!is_object($test) && $test) || ($test && $test->exists())) {  ?>', $content);
		$content = ereg_replace('<' . '% +else_if +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? } else if(($test = $item->obj("\\1",null,true)->obj("\\2",null,true)->cachedCall("\\3")) && ((!is_object($test) && $test) || ($test && $test->exists()) )) {  ?>', $content);
		
		$content = ereg_replace('<' . '% +else +%' . '>', '<? } else { ?>', $content);
		$content = ereg_replace('<' . '% +end_if +%' . '>', '<? }  ?>', $content);

		// i18n - get filename of currently parsed template
		// CAUTION: No spaces allowed between arguments for all i18n calls!
		ereg('.*[\/](.*)',$template,$path);
		
		// i18n _t(...) - with entity only (no dots in namespace), 
		// meaning the current template filename will be added as a namespace. 
		// This applies only to "root" templates, not includes which should always have their namespace set already.
		// See getTemplateContent() for more information.
		$content = ereg_replace('<' . '% +_t\((\'([^\.\']*)\'|"([^\."]*)")(([^)]|\)[^ ]|\) +[^% ])*)\) +%' . '>', '<?= _t(\''. $path[1] . '.\\2\\3\'\\4) ?>', $content);
		// i18n _t(...)
		$content = ereg_replace('<' . '% +_t\((\'([^\']*)\'|"([^"]*)")(([^)]|\)[^ ]|\) +[^% ])*)\) +%' . '>', '<?= _t(\'\\2\\3\'\\4) ?>', $content);

		// i18n sprintf(_t(...),$argument) with entity only (no dots in namespace), meaning the current template filename will be added as a namespace
		$content = ereg_replace('<' . '% +sprintf\(_t\((\'([^\.\']*)\'|"([^\."]*)")(([^)]|\)[^ ]|\) +[^% ])*)\),\<\?= +([^\?]*) +\?\>) +%' . '>', '<?= sprintf(_t(\''. $path[1] . '.\\2\\3\'\\4),\\6) ?>', $content);
		// i18n sprintf(_t(...),$argument)
		$content = ereg_replace('<' . '% +sprintf\(_t\((\'([^\']*)\'|"([^"]*)")(([^)]|\)[^ ]|\) +[^% ])*)\),\<\?= +([^\?]*) +\?\>) +%' . '>', '<?= sprintf(_t(\'\\2\\3\'\\4),\\6) ?>', $content);

		// </base> isnt valid html? !? 
		$content = ereg_replace('<' . '% +base_tag +%' . '>', '<?= SSViewer::get_base_tag($val); ?>', $content);

		$content = ereg_replace('<' . '% +current_page +%' . '>', '<?= $_SERVER[SCRIPT_URL] ?>', $content);
		
		// change < % require x() % > calls to corresponding Requirement::x() ones, including 0, 1 or 2 options
		$content = preg_replace('/<% +require +([a-zA-Z]+)(?:\(([^),]+)\))? +%>/', '<? Requirements::\\1("\\2"); ?>', $content);
		$content = preg_replace('/<% +require +([a-zA-Z]+)\(([^),]+), *([^),]+)\) +%>/', '<? Requirements::\\1("\\2", "\\3"); ?>', $content);
		
		// legacy
		$content = ereg_replace('<!-- +if +([A-Za-z0-9_]+) +-->', '<? if($item->cachedCall("\\1")) { ?>', $content);
		$content = ereg_replace('<!-- +else +-->', '<? } else { ?>', $content);
		$content = ereg_replace('<!-- +if_end +-->', '<? }  ?>', $content);
			
		// Fix link stuff
		$content = ereg_replace('href *= *"#', 'href="<?= SSViewer::$options[\'rewriteHashlinks\'] ? strip_tags( $_SERVER[\'REQUEST_URI\'] ) : "" ?>#', $content);
	
		// Protect xml header
		$content = ereg_replace('<\?xml([^>]+)\?' . '>', '<##xml\\1##>', $content);

		// Turn PHP file into string definition
		$content = str_replace('<?=',"\nSSVIEWER;\n\$val .= ", $content);
		$content = str_replace('<?',"\nSSVIEWER;\n", $content);
		$content = str_replace('?>',";\n \$val .= <<<SSVIEWER\n", $content);
		
		$output  = "<?php\n";
		$output .= '$val .= <<<SSVIEWER' . "\n" . $content . "\nSSVIEWER;\n"; 
		
		// Protect xml header @sam why is this run twice ?
		$output = ereg_replace('<##xml([^>]+)##>', '<' . '?xml\\1?' . '>', $output);
	
		return $output;
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
				echo str_pad($num+1,5) . htmlentities($line);
			}
			echo "</pre>";
		}

		$itemStack = array();
		$val = "";
		$valStack = array();
		
		$cache = SS_Cache::factory('cacheblock');
		
		include($tmpFile);
		unlink($tmpFile);
		

		return $val;
	}
}

/**
 * Handle the parsing for cacheblock tags.
 * 
 * Needs to be handled differently from the other tags, because cacheblock can take any number of arguments
 * 
 * This shouldn't be used as an example of how to add functionality to SSViewer - the eventual plan is to re-write
 * SSViewer using a proper parser (probably http://github.com/hafriedlander/php-peg), so that extra functionality
 * can be added without relying on ad-hoc parsers like this.
 * 
 * @package sapphire
 * @subpackage view
 */
class SSViewer_PartialParser {
 	
	static $tag = '/< % [ \t]+ (cached|cacheblock|uncached|end_cached|end_cacheblock|end_uncached) [ \t]+ ([^%]+ [ \t]+)? % >/xS';
	
	static $argument_splitter = '/^\s*
		# The argument itself
		(
			(?P<conditional> if | unless ) |                     # The if or unless keybreak
			(?P<property> (?P<identifier> \w+) \s*               # A property lookup or a function call
				( \( (?P<arguments> [^\)]*) \) )?
			) |
			(?P<sqstring> \' (\\\'|[^\'])+ \' ) |                # A string surrounded by \'
			(?P<dqstring> " (\\"|[^"])+ " )                      # A string surrounded by "
		)
		# Some seperator after the argument
		(
			\s*(?P<comma>,)\s* |                                 # A comma (maybe with whitespace before or after)
			(?P<fullstop>\.)                                     # A period (no whitespace before)
		)?
	/xS';
	
	static function process($template, $content) {
		$parser = new SSViewer_PartialParser($template, $content, 0);
		$parser->parse();
		return $parser->generate();
	}
	
	function __construct($template, $content, $offset) {
		$this->template = $template;
		$this->content = $content;
		$this->offset = $offset;

		$this->blocks = array();
	}

	function controlcheck($text) {
		// NOP - hook for Cached_PartialParser
	}

	function parse() {
		$current_tag_offset = 0;
		
		while (preg_match(self::$tag, $this->content, $matches, PREG_OFFSET_CAPTURE, $this->offset)) {
			$tag = $matches[1][0];

			$startpos = $matches[0][1];
			$endpos = $matches[0][1] + strlen($matches[0][0]);

			switch($tag) {
				case 'cached':
				case 'uncached':
				case 'cacheblock':

					$pretext = substr($this->content, $this->offset, $startpos - $this->offset);
					$this->controlcheck($pretext);
					$this->blocks[] = $pretext;

					if ($tag == 'cached' || $tag == 'cacheblock') {
						list($keyparts, $conditional, $condition) = $this->parseargs(@$matches[2][0]);
						$parser = new SSViewer_Cached_PartialParser($this->template, $this->content, $endpos, $keyparts, $conditional, $condition);
					}
					else {
						$parser = new SSViewer_PartialParser($this->template, $this->content, $endpos);
					}

					$parser->parse();
					$this->blocks[] = $parser;
					$this->offset = $parser->offset;
					break;

				case 'end_cached':
				case 'end_cacheblock':
				case 'end_uncached':
					$this->blocks[] = substr($this->content, $this->offset, $startpos - $this->offset);
					$this->content = null;

					$this->offset = $endpos;
					return $this;
			}
		}

		$this->blocks[] = substr($this->content, $this->offset);
		$this->content = null;
	}

	function parseargs($string) {
		preg_match_all(self::$argument_splitter, $string, $matches, PREG_SET_ORDER);

		$parts = array();
		$conditional = null; $condition = null;

		$current = '$item->';

		while (strlen($string) && preg_match(self::$argument_splitter, $string, $match)) {

			$string = substr($string, strlen($match[0]));

			// If this is a conditional keyword, break, and the next loop will grab the conditional
			if (@$match['conditional']) {
				$conditional = $match['conditional'];
				continue;
			}

			// If it's a property lookup or a function call
			if (@$match['property']) {
				// Get the property
				$what = $match['identifier'];
				$args = array();

				// Extract any arguments passed to the function call
				if (@$match['arguments']) {
					foreach (explode(',', $match['arguments']) as $arg) {
						$args[] = is_numeric($arg) ? (string)$arg : '"'.$arg.'"';
					}
				}

				$args = empty($args) ? 'null' : 'array('.implode(',',$args).')';

				// If this fragment ended with '.', then there's another lookup coming, so return an obj for that lookup
				if (@$match['fullstop']) {
					$current .= "obj('$what', $args, true)->";
				}
				// Otherwise this is the end of the lookup chain, so add the resultant value to the key array and reset the key-get php fragement
				else {
					$accessor = $current . "XML_val('$what', $args, true)"; $current = '$item->';

					// If we've hit a conditional already, this is the condition. Set it and be done.
					if ($conditional) {
						$condition = $accessor;
						break;
					}
					// Otherwise we're another key component. Add it to array.
					else $parts[] = $accessor;
				}
			}

			// Else it's a quoted string of some kind
			else if (@$match['sqstring']) $parts[] = $match['sqstring'];
			else if (@$match['dqstring']) $parts[] = $match['dqstring'];
		}

		if ($conditional && !$condition) {
			throw new Exception("You need to have a condition after the conditional $conditional in your cache block");
		}

		return array($parts, $conditional, $condition);
	}

	function generate() {
		$res = array();

		foreach ($this->blocks as $i => $block) {
			if ($block instanceof SSViewer_PartialParser)
				$res[] = $block->generate();
			else {
				$res[] = $block;
			}
		}

		return implode('', $res);
	}
}

/**
 * @package sapphire
 * @subpackage view
 */
class SSViewer_Cached_PartialParser extends SSViewer_PartialParser {

	function __construct($template, $content, $offset, $keyparts, $conditional, $condition) {
		$this->keyparts = $keyparts;
		$this->conditional = $conditional;
		$this->condition = $condition;

		parent::__construct($template, $content, $offset);
	}

	function controlcheck($text) {
		$ifs = preg_match_all('/<'.'% +if +/', $text, $matches);
		$end_ifs = preg_match_all('/<'.'% +end_if +/', $text, $matches);

		if ($ifs != $end_ifs) throw new Exception('You can\'t have cached or uncached blocks within condition structures');

		$controls = preg_match_all('/<'.'% +control +/', $text, $matches);
		$end_controls = preg_match_all('/<'.'% +end_control +/', $text, $matches);

		if ($controls != $end_controls) throw new Exception('You can\'t have cached or uncached blocks within control structures');
	}

	function key() {
		if (empty($this->keyparts)) return "''";
		return 'sha1(' . implode(".'_'.", $this->keyparts) . ')';
	}

	function generate() {
		$res = array();
		$key = $this->key();

		$condition = "";

		switch ($this->conditional) {
			case 'if':
				$condition = "{$this->condition} && ";
				break;
			case 'unless':
				$condition = "!({$this->condition}) && ";
				break;
		}

		/* Output this set of blocks */

		foreach ($this->blocks as $i => $block) {
			if ($block instanceof SSViewer_PartialParser)
				$res[] = $block->generate();
			else {
				// Include the template name and this cache block's current contents as a sha hash, so we get auto-seperation
				// of cache blocks, and invalidation of the cache when the template changes
				$partialkey = "'".sha1($this->template . $block)."_'.$key.'_$i'";

				// Try to load from cache
				$res[] = "<?\n".'if ('.$condition.' ($partial = $cache->load('.$partialkey.'))) $val .= $partial;'."\n";

				// Cache miss - regenerate
				$res[] = "else {\n";
				$res[] = '$oldval = $val; $val = "";'."\n";
				$res[] = "\n?>" . $block . "<?\n";
				$res[] = $condition . ' $cache->save($val); $val = $oldval . $val ;'."\n";
				$res[] = "}\n?>";
			}
		}

		return implode('', $res);
	}
}

function supressOutput() {
	return "";
}

?>