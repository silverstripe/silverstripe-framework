<?php

/**
 * @package sapphire
 * @subpackage view
 */

/**
 * The SSViewer executes a .ss template file.
 * The SSViewer class handles rendering of .ss templates.  In addition to a full template in
 * the views folder, a template in views/Content or views/Layout will be rendered into $Content and
 * $Layout, respectively.
 *
 * Compiled templates are cached.  If you put ?flush=1 on your URL, it will force the template to be recompiled.  This
 * is a hack; the system should really detect when a page needs re-fetching.
 * @todo Fix the broken caching.
 * @package sapphire
 * @subpackage view
 */
class SSViewer extends Object {
	private $chosenTemplates;
	protected $rewriteHashlinks = true;
	
	protected static $current_theme = null;
	
	/**
	 * Create a template from a string instead of a .ss file
	 */
	static function fromString($content) {
		return new SSViewer_FromString($content);
	}
	
	static function set_theme($theme) {
		self::$current_theme = $theme;
	}
	static function current_theme() {
		return self::$current_theme;
	}
	
	/**
	 * Pass the SilverStripe template to be used
	 */
	public function __construct($templateList) {
		if(substr((string) $templateList,-3) == '.ss') {
			$this->chosenTemplates['main'] = $templateList;
		} else {
			if(!is_array($templateList)) $templateList = array($templateList);
		
			$this->chosenTemplates = array();
			global $_TEMPLATE_MANIFEST;
			// if($_REQUEST['showtemplate']) Debug::show($_TEMPLATE_MANIFEST);

			foreach($templateList as $template) {
				if(strpos($template,'/') !== false) list($templateFolder, $template) = explode('/', $template, 2);
				else $templateFolder = null;
				
				// Use the theme template if available
				if(self::$current_theme && isset($_TEMPLATE_MANIFEST[$template]['themes'][self::$current_theme])) {
					$this->chosenTemplates = array_merge($_TEMPLATE_MANIFEST[$template]['themes'][self::$current_theme], 
						$this->chosenTemplates);

				} else if(isset($_TEMPLATE_MANIFEST[$template])) {
					$this->chosenTemplates = array_merge($_TEMPLATE_MANIFEST[$template], $this->chosenTemplates);
					unset($this->chosenTemplates['themes']);
				}
				
				if($templateFolder) {
					$this->chosenTemplates['main'] = $this->chosenTemplates[$templateFolder];
					unset($this->chosenTemplates[$templateFolder]);
				}
			}
		}

		if(!$this->chosenTemplates) user_error("None of these templates can be found: ". implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);

		parent::__construct();
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
	 */
	public static function setOption($optionName, $optionVal) {
		SSViewer::$options[$optionName] = $optionVal;
	}
	protected static $options = array(
		'rewriteHashlinks' => true,
	);
    
	protected static $topLevel = null;
  public static function topLevel() {
     return SSViewer::$topLevel;
  }
	
	/**
	 * Call this to disable rewriting of <a href="#xxx"> links.  This is useful in Ajax applications.
	 * It returns the SSViewer objects, so that you can call new SSViewer("X")->dontRewriteHashlinks()->process();
	 */
	public function dontRewriteHashlinks() {
		$this->rewriteHashlinks = false;
		return $this;
	}
	
	public function exists() {
		return $this->chosenTemplates;
	}
	
	public static function getTemplateFile($identifier) {
		global $_TEMPLATE_MANIFEST;
		if(self::$current_theme && isset($_TEMPLATE_MANIFEST[$identifier]['themes'][self::$current_theme]['Includes'])) {
			return $_TEMPLATE_MANIFEST[$identifier]['themes'][self::$current_theme]['Includes'];
		} else if(isset($_TEMPLATE_MANIFEST[$identifier]['Includes'])){
			return $_TEMPLATE_MANIFEST[$identifier]['Includes'];
		} else if(self::$current_theme && isset($_TEMPLATE_MANIFEST[$identifier]['themes'][self::$current_theme]['main'])) {
			return $_TEMPLATE_MANIFEST[$identifier]['themes'][self::$current_theme]['main'];
		} else {
			return isset($_TEMPLATE_MANIFEST[$identifier]['main']) ? $_TEMPLATE_MANIFEST[$identifier]['main'] : null;
		}
	}
	public static function getTemplateContent($identifier) {
		return file_get_contents(SSViewer::getTemplateFile($identifier));
	}
	
	/**
	 * The process() method handles the "meat" of the template processing.
	 */
	 
	public function process($item) {
		SSViewer::$topLevel = $item;
        
		if(isset($this->chosenTemplates['main'])) {
			$template = $this->chosenTemplates['main'];
		} else {
			$template = $this->chosenTemplates[ reset($dummy = array_keys($this->chosenTemplates)) ];
		}

		
		if(isset($_GET['debug_profile'])) Profiler::mark("SSViewer::process", " for $template");
		$cacheFile = TEMP_FOLDER . "/.cache" . str_replace(array('\\','/',':'),'.',realpath($template));

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
		
		
		foreach(array('Content', 'Layout') as $subtemplate) {
			if(isset($this->chosenTemplates[$subtemplate])) {
				$subtemplateViewer = new SSViewer($this->chosenTemplates[$subtemplate]);
				$item = $item->customise(array(
					$subtemplate => $subtemplateViewer->process($item)
				));
			}
		}
		
		$itemStack = array();
		$val = "";

		include($cacheFile);

		$output = $val;		
		$output = Requirements::includeInHTML($template, $output);
		
		SSViewer::$topLevel = null;

		if(isset($_GET['debug_profile'])) Profiler::unmark("SSViewer::process", " for $template");
		
		
		// If we have our crazy base tag, then fix # links referencing the current page.
		if(strpos($output, '<base') !== false) {		
			$thisURLRelativeToBase = Director::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI']));
			$output = preg_replace('/(<a[^>+]href *= *")#/i', '\\1' . $thisURLRelativeToBase . '#', $output);
		}

		return $output;
	}

	static function parseTemplateContent($content, $template="") {			
		while(true) {
			$oldContent = $content;
			$content = preg_replace_callback('/<' . '% include +([A-Za-z0-9_]+) +%' . '>/', create_function(
				'$matches', 'return SSViewer::getTemplateContent($matches[1]);'
				), $content);
			if($oldContent == $content) break;
		}
		
		// $val, $val.property, $val(param), etc.
		$replacements = array(
			'/\$Iteration/' =>  '<?= {dlr}key ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)}/' => '<?= {dlr}item->XML_val("\\1",array("\\2","\\3"),true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)}/' => '<?= {dlr}item->XML_val("\\1",array("\\2"),true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",null,true)->obj("\\2",null,true)->XML_val("\\3",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)}/' => '<?= {dlr}item->obj("\\1",null,true)->XML_val("\\2",null,true) ?>',
			'/{\\$([A-Za-z_][A-Za-z0-9_]*)}/' => '<?= {dlr}item->XML_val("\\1",null,true) ?>\\2',

			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\(([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1")->XML_val("\\2",array("\\3"),true) ?>\\4',

			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+), *([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",array("\\2","\\3"),true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\(([^),]+)\\)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",array("\\2"),true) ?>\\3',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",null,true)->obj("\\2",null,true)->XML_val("\\3",null,true) ?>\\4',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)\\.([A-Za-z0-9_]+)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->obj("\\1",null,true)->XML_val("\\2",null,true) ?>\\3',
			'/\\$([A-Za-z_][A-Za-z0-9_]*)([^A-Za-z0-9]|$)/' => '<?= {dlr}item->XML_val("\\1",null,true) ?>\\2',
		);
		
		$content = preg_replace(array_keys($replacements), array_values($replacements), $content);
		$content = str_replace('{dlr}','$',$content);

		// legacy
		$content = ereg_replace('<!-- +pc +([A-Za-z0-9_(),]+) +-->', '<' . '% control \\1 %' . '>', $content);
		$content = ereg_replace('<!-- +pc_end +-->', '<' . '% end_control %' . '>', $content);

		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1")) foreach($loop as $key => $item) { ?>', $content);
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+) +%' . '>', '<? array_push($itemStack, $item); if(($loop = $item->obj("\\1")) && ($loop = $loop->obj("\\2"))) foreach($loop as $key => $item) { ?>', $content);

		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\.([A-Za-z0-9_]+)\\(([A-Za-z0-9_-]+)\\) +%' . '>', '<? array_push($itemStack, $item); if(($loop = $item->obj("\\1")) && ($loop = $loop->obj("\\2", array("\\3")))) foreach($loop as $key => $item) { ?>', $content);

		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([A-Za-z0-9_-]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2"))) foreach($loop as $key => $item) { ?>', $content);
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([A-Za-z0-9_-]+), *([A-Za-z0-9_-]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2","\\3"))) foreach($loop as $key => $item) { ?>', $content);
		$content = ereg_replace('<' . '% +control +([A-Za-z0-9_]+)\\(([A-Za-z0-9_-]+), *([A-Za-z0-9_-]+), *([A-Za-z0-9_-]+)\\) +%' . '>', '<? array_push($itemStack, $item); if($loop = $item->obj("\\1", array("\\2", "\\3", "\\4"))) foreach($loop as $key => $item) { ?>', $content);
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

		// i18n
		ereg('.*[\/](.*)',$template,$path);
		$content = ereg_replace('<' . '% +_t\((\'([^\']*)\'|"([^"]*)")(([^)]|\)[^ ]|\) +[^% ])*)\) +%' . '>', '<?= _t(\''. $path[1] . '.\\2\\3\'\\4) ?>', $content);

		// i18n - sprintf => "sprintf(_t(...),$argument)"
		// CAUTION: No spaces allowed between arguments!
		$content = ereg_replace('<' . '% +sprintf\(_t\((\'([^\']*)\'|"([^"]*)")(([^)]|\)[^ ]|\) +[^% ])*)\),\<\?= +([^\?]*) +\?\>) +%' . '>', '<?= sprintf(_t(\''. $path[1] . '.\\2\\3\'\\4),\\6) ?>', $content);
		
		// </base> isnt valid html? !? 
		$content = ereg_replace('<' . '% +base_tag +%' . '>', '<base href="<?= Director::absoluteBaseURL(); ?>" />', $content);

		$content = ereg_replace('<' . '% +current_page +%' . '>', '<?= $_SERVER[SCRIPT_URL] ?>', $content);
		
		// legacy
		$content = ereg_replace('<!-- +if +([A-Za-z0-9_]+) +-->', '<? if($item->cachedCall("\\1")) { ?>', $content);
		$content = ereg_replace('<!-- +else +-->', '<? } else { ?>', $content);
		$content = ereg_replace('<!-- +if_end +-->', '<? }  ?>', $content);
			
		// Fix link stuff
		$content = ereg_replace('href *= *"#', 'href="<?= SSViewer::$options[\'rewriteHashlinks\'] ? Convert::raw2att( $_SERVER[\'REQUEST_URI\'] ) : "" ?>#', $content);
	
		// Protect xml header
		$content = ereg_replace('<\?xml([^>]+)\?' . '>', '<##xml\\1##>', $content);

		// Turn PHP file into string definition
		$content = str_replace('<?=',"\nSSVIEWER;\n\$val .= ", $content);
		$content = str_replace('<?',"\nSSVIEWER;\n", $content);
		$content = str_replace('?>',";\n \$val .= <<<SSVIEWER\n", $content);

		$content = "<?php \$val .= <<<SSVIEWER\n" . $content . "\nSSVIEWER;\n ?>";

		// Protect xml header @sam why is this run twice ?
		$content = ereg_replace('<##xml([^>]+)##>', '<' . '?xml\\1?' . '>', $content);
	
		return $content;
	}

	/**
	 * Returns the filenames of the template that will be rendered.  It is a map that may contain
	 * 'Content' & 'Layout', and will have to contain 'main'
	 */
	public function templates() {
		return $this->chosenTemplates;
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
	
	public function process($item) {
		$template = SSViewer::parseTemplateContent($this->content);

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

		include($tmpFile);
		unlink($tmpFile);
		

		return $val;
	}
}
	

function supressOutput() {
	return "";
}

?>
