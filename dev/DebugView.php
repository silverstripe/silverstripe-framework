<?php
/**
 * @package framework
 * @subpackage dev
 */

/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 *
 * @package framework
 * @subpackage dev
 */
class DebugView extends Object {

	/**
	 * Column size to wrap long strings to
	 *
	 * @var int
	 * @config
	 */
	private static $columns = 100;

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
		E_DEPRECATED => array(
			'title' => 'Deprecated',
			'class' => 'notice'
		),
		E_USER_DEPRECATED => array(
			'title' => 'User Deprecated',
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
		),
		E_STRICT => array(
			'title' => 'Strict Notice',
			'class' => 'notice'
		),
		E_RECOVERABLE_ERROR => array(
			'title' => 'Recoverable Error',
			'class' => 'warning'
		)
	);

	protected static $unknown_error = array(
		'title' => 'Unknown Error',
		'class' => 'error'
	);

	/**
	 * Generate breadcrumb links to the URL path being displayed
	 *
	 * @return string
	 */
	public function Breadcrumbs() {
		$basePath = str_replace(Director::protocolAndHost(), '', Director::absoluteBaseURL());
		$relPath = parse_url(substr($_SERVER['REQUEST_URI'], strlen($basePath), strlen($_SERVER['REQUEST_URI'])),
			PHP_URL_PATH);
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
		return implode('&nbsp;&rarr;&nbsp;', $pathLinks);
	}

	/**
	 * Render HTML header for development views
	 */
	public function writeHeader() {
		$url = htmlentities(
			$_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'],
			ENT_COMPAT,
			'UTF-8'
		);

		$debugCSS = Controller::join_links(
			Director::absoluteBaseURL(),
			FRAMEWORK_DIR,
			'css/debug.css'
		);

		echo '<!DOCTYPE html><html><head><title>' . $url . '</title>';
		echo '<link rel="stylesheet" type="text/css" href="'. $debugCSS .'" />';
		echo '</head>';
		echo '<body>';
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
		$errorType = isset(self::$error_types[$errno]) ? self::$error_types[$errno] : self::$unknown_error;
		$httpRequestEnt = htmlentities($httpRequest, ENT_COMPAT, 'UTF-8');
		if (ini_get('html_errors')) {
			$errstr = strip_tags($errstr);
		} else {
			$errstr = Convert::raw2xml($errstr);
		}
		echo '<div class="info ' . $errorType['class'] . '">';
		echo "<h1>[" . $errorType['title'] . '] ' . $errstr . "</h1>";
		echo "<h3>$httpRequestEnt</h3>";
		echo "<p>Line <strong>$errline</strong> in <strong>$errfile</strong></p>";
		echo '</div>';
	}

	/**
	 * Write a fragment of the a source file
	 * @param $lines An array of file lines; the keys should be the original line numbers
	 */
	public function writeSourceFragment($lines, $errline) {
		echo '<div class="trace"><h3>Source</h3>';
		echo '<pre>';
		foreach($lines as $offset => $line) {
			$line = htmlentities($line, ENT_COMPAT, 'UTF-8');
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
	public function writeTrace($trace) {
		echo '<h3>Trace</h3>';
		echo SS_Backtrace::get_rendered_backtrace($trace);
		echo '</div>';
	}

	/**
	 * @param string $text
	 */
	public function writeParagraph($text) {
		echo '<p class="info">' . $text . '</p>';
	}

	/**
	 * Formats the caller of a method
	 *
	 * @param array $caller
	 * @return string
	 */
	protected function formatCaller($caller) {
		$return = basename($caller['file']) . ":" . $caller['line'];
		if(!empty($caller['class']) && !empty($caller['function'])) {
			$return .= " - {$caller['class']}::{$caller['function']}()";
		}
		return $return;
	}

	/**
	 * Outputs a variable in a user presentable way
	 *
	 * @param object $val
	 * @param array $caller Caller information
	 */
	public function writeVariable($val, $caller) {
		echo '<pre style="background-color:#ccc;padding:5px;font-size:14px;line-height:18px;">';
		echo "<span style=\"font-size: 12px;color:#666;\">" . $this->formatCaller($caller). " - </span>\n";
		if (is_string($val)) print_r(wordwrap($val, self::config()->columns));
		else print_r($val);
		echo '</pre>';
	}
}
