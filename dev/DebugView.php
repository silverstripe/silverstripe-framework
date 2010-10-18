<?php
/**
 * @package sapphire
 * @subpackage dev
 */

/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 * 
 * @package sapphire
 * @subpackage dev
 */
class DebugView extends Object {

	protected static $error_types = array(
		E_USER_ERROR => array(
			'title' => 'User Error',
			'class' => 'error'
		),
		E_CORE_ERROR => array(
			'title' => 'Core Error',
			'class' => 'error'
		),
		E_NOTICE => array(
			'title' => 'Notice',
			'class' => 'notice'
		),
		E_USER_NOTICE => array(
			'title' => 'User Notice',
			'class' => 'notice'
		),
		E_CORE_ERROR => array(
			'title' => 'Core Error',
			'class' => 'error'
		),
		E_WARNING => array(
			'title' => 'Warning',
			'class' => 'warning'
		),
		E_CORE_WARNING => array(
			'title' => 'Core Warning',
			'class' => 'warning'
		),
		E_USER_WARNING => array(
			'title' => 'User Warning',
			'class' => 'warning'
		)
	);

	/**
	 * Generate breadcrumb links to the URL path being displayed
	 *
	 * @return string
	 */
	public function Breadcrumbs() {
		$basePath = str_replace(Director::protocolAndHost(), '', Director::absoluteBaseURL());
		$relPath = parse_url(substr($_SERVER['REQUEST_URI'], strlen($basePath), strlen($_SERVER['REQUEST_URI'])), PHP_URL_PATH);
		$parts = explode('/', $relPath);
		$base = Director::absoluteBaseURL();
		$pathPart = "";
		$pathLinks = array();
		foreach($parts as $part) {
			if ($part != '') {
				$pathPart .= "$part/";
				$pathLinks[] = "<a href=\"$base$pathPart\">$part</a>";
			}
		}
		return implode('&rarr;&nbsp;', $pathLinks);
	}	
	
	/**
	 * Render HTML header for development views
	 */
	public function writeHeader() {
		echo '<!DOCTYPE html><html><head><title>' . htmlentities($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']) . '</title>';
		echo '<style type="text/css">';
		echo 'body { background-color:#eee; margin:0; padding:0; font-family:Helvetica,Arial,sans-serif; }';
		echo '.info { border-bottom:1px dotted #333; background-color:#ccdef3; margin:0; padding:6px 12px; }';
		echo '.info h1 { margin:0; padding:0; color:#333; letter-spacing:-2px; }';
		echo '.header { margin:0; border-bottom:6px solid #ccdef3; height:23px; background-color:#666673; padding:4px 0 2px 6px; background-image:url('.Director::absoluteBaseURL().'cms/images/mainmenu/top-bg.gif); }';
		echo '.trace { padding:6px 12px; }';
		echo '.trace li { font-size:14px; margin:6px 0; }';
		echo 'pre { margin-left:18px; }';
		echo 'pre span { color:#999;}';
		echo 'pre .error { color:#f00; }';
		echo '.pass { margin-top:18px; padding:2px 20px 2px 40px; color:#006600; background:#E2F9E3 url('.Director::absoluteBaseURL() .'cms/images/alert-good.gif) no-repeat scroll 7px 50%; border:1px solid #8DD38D; }';
		echo '.fail { margin-top:18px; padding:2px 20px 2px 40px; color:#C80700; background:#FFE9E9 url('.Director::absoluteBaseURL() .'cms/images/alert-bad.gif) no-repeat scroll 7px 50%; border:1px solid #C80700; }';	
		echo '.failure span { color:#C80700; font-weight:bold; }';
		echo '</style></head>';
		echo '<body>';
		echo '<div class="header"><img src="'. Director::absoluteBaseURL() .'cms/images/mainmenu/logo.gif" width="26" height="23" /></div>';
	}
	
	/**
	 * Render the information header for the view
	 * 
	 * @param string $title
	 * @param string $title
	 */
	public function writeInfo($title, $subtitle, $description=false) {
		echo '<div class="info">';
		echo "<h1>" . Convert::raw2xml($title) . "</h1>";
		if($subtitle) echo "<h3>" . Convert::raw2xml($subtitle) . "</h3>";
		if ($description) {
			echo "<p>$description</p>";
		} else {
			echo $this->Breadcrumbs();
		}
		echo '</div>';
	}
	
	/**
	 * Render HTML footer for development views
	 */
	public function writeFooter() {
		echo "</body></html>";		
	}	

	/**
	 * Write information about the error to the screen
	 */
	public function writeError($httpRequest, $errno, $errstr, $errfile, $errline, $errcontext) {
		$errorType = self::$error_types[$errno];
		$httpRequestEnt = htmlentities($httpRequest);
		echo '<div class="info ' . $errorType['class'] . '">';
		echo "<h1>[" . $errorType['title'] . '] ' . strip_tags($errstr) . "</h1>";
		echo "<h3>$httpRequestEnt</h3>";
		echo "<p>Line <strong>$errline</strong> in <strong>$errfile</strong></p>";
		echo '</div>';
	}

	/**
	 * Write a fragment of the a source file
	 * @param $lines An array of file lines; the keys should be the original line numbers
	 */
	function writeSourceFragment($lines, $errline) {
		echo '<div class="trace"><h3>Source</h3>';
		echo '<pre>';
		foreach($lines as $offset => $line) {
			$line = htmlentities($line);
			if ($offset == $errline) {
				echo "<span>$offset</span> <span class=\"error\">$line</span>";
			} else {
				echo "<span>$offset</span> $line";
			}
		}
		echo '</pre>';
	}
	
	/**
	 * Write a backtrace
	 */
	function writeTrace($trace) {
		echo '<h3>Trace</h3>';
		echo SS_Backtrace::get_rendered_backtrace($trace);
		echo '</div>';
	}
	
	/**
	 * @param string $text
	 */
	function writeParagraph($text) {
		echo '<p class="info">' . $text . '</p>';
	}
}

?>