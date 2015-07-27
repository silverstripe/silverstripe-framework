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

class CliDebugView extends DebugView
{

	/**
	 * Render HTML header for development views
	 */
	public function renderHeader($httpRequest = null) {
	}

	/**
	 * Render HTML footer for development views
	 */
	public function renderFooter() {
	}

	/**
	 * Write information about the error to the screen
	 */
	public function renderError($httpRequest, $errno, $errstr, $errfile, $errline) {
		if(!isset(self::$error_types[$errno])) {
			$errorTypeTitle = "UNKNOWN TYPE, ERRNO $errno";
		} else {
			$errorTypeTitle = self::$error_types[$errno]['title'];
		}
		$output = SS_Cli::text("ERROR [" . $errorTypeTitle . "]: $errstr\nIN $httpRequest\n", "red", null, true);
		$output .= SS_Cli::text("Line $errline in $errfile\n\n", "red");

		return $output;
	}

	/**
	 * Write a fragment of the a source file
	 * @param $lines An array of file lines; the keys should be the original line numbers
	 */
	public function renderSourceFragment($lines, $errline) {
		$output = "Source\n======\n";
		foreach($lines as $offset => $line) {
			$output .= ($offset == $errline) ? "* " : "  ";
			$output .= str_pad("$offset:", 5);
			$output .= wordwrap($line, self::config()->columns, "\n       ");
		}
		$output .= "\n";

		return $output;
	}

	/**
	 * Write a backtrace
	 */
	public function renderTrace($trace = null) {
		$output = "Trace\n=====\n";
		$output .= SS_Backtrace::get_rendered_backtrace($trace ? $trace : debug_backtrace(), true);

		return $output;
	}

	/**
	 * Render the information header for the view
	 *
	 * @param string $title
	 * @param string $title
	 */
	public function renderInfo($title, $subtitle, $description=false) {
		$output = wordwrap(strtoupper($title), self::config()->columns) . "\n";
		$output .= wordwrap($subtitle, self::config()->columns) . "\n";
		$output .= str_repeat('-', min(self::config()->columns, max(strlen($title), strlen($subtitle)))) . "\n";
		$output .= wordwrap($description, self::config()->columns) . "\n\n";

		return $output;
	}

	public function renderVariable($val, $caller) {
		$output = PHP_EOL;
		$output .= SS_Cli::text(str_repeat('=', self::config()->columns), 'green');
		$output .= PHP_EOL;
		$output .= SS_Cli::text($this->formatCaller($caller), 'blue', null, true);
		$output .= PHP_EOL.PHP_EOL;
		if (is_string($val)) {
			$output .= wordwrap($val, self::config()->columns);
		} else {
			$output .= var_export($val, true);
		}
		$output .= PHP_EOL;
		$output .= SS_Cli::text(str_repeat('=', self::config()->columns), 'green');
		$output .= PHP_EOL;

		return $output;
	}
}
