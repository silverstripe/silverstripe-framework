<?php
/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 *
 * @package framework
 * @subpackage dev
 *
 * @todo Perhaps DebugView should be an interface / ABC, implemented by HTMLDebugView and CliDebugView?
 */

class CliDebugView extends DebugView {

	/**
	 * Render HTML header for development views
	 */
	public function writeHeader($httpRequest = null) {
	}

	/**
	 * Render HTML footer for development views
	 */
	public function writeFooter() {
	}

	/**
	 * Write information about the error to the screen
	 */
	public function writeError($httpRequest, $errno, $errstr, $errfile, $errline, $errcontext) {
		$errorType = self::$error_types[$errno];
		echo SS_Cli::text("ERROR [" . $errorType['title'] . "]: $errstr\nIN $httpRequest\n", "red", null, true);
		echo SS_Cli::text("Line $errline in $errfile\n\n", "red");
	}

	/**
	 * Write a fragment of the a source file
	 * @param $lines An array of file lines; the keys should be the original line numbers
	 */
	public function writeSourceFragment($lines, $errline) {
		echo "Source\n======\n";
		foreach($lines as $offset => $line) {
			echo ($offset == $errline) ? "* " : "  ";
			echo str_pad("$offset:",5);
			echo wordwrap($line, self::config()->columns, "\n       ");
		}
		echo "\n";
	}

	/**
	 * Write a backtrace
	 */
	public function writeTrace($trace = null) {
		echo "Trace\n=====\n";
		echo SS_Backtrace::get_rendered_backtrace($trace ? $trace : debug_backtrace(), true);
	}

	/**
	 * Render the information header for the view
	 *
	 * @param string $title
	 * @param string $title
	 */
	public function writeInfo($title, $subtitle, $description=false) {
		echo wordwrap(strtoupper($title),self::config()->columns) . "\n";
		echo wordwrap($subtitle,self::config()->columns) . "\n";
		echo str_repeat('-',min(self::config()->columns,max(strlen($title),strlen($subtitle)))) . "\n";
		echo wordwrap($description,self::config()->columns) . "\n\n";
	}

	public function writeVariable($val, $caller) {
		echo PHP_EOL;
		echo SS_Cli::text(str_repeat('=', self::config()->columns), 'green');
		echo PHP_EOL;
		echo SS_Cli::text($this->formatCaller($caller), 'blue', null, true);
		echo PHP_EOL.PHP_EOL;
		if (is_string($val)) {
			print_r(wordwrap($val, self::config()->columns));
		} else {
			print_r($val);
		}
		echo PHP_EOL;
		echo SS_Cli::text(str_repeat('=', self::config()->columns), 'green');
		echo PHP_EOL;
	}
}
