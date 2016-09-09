<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPRequest;

/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 *
 * @todo Perhaps DebugView should be an interface / ABC, implemented by HTMLDebugView and CliDebugView?
 */
class CliDebugView extends DebugView
{

	/**
	 * Render HTML header for development views
	 *
	 * @param HTTPRequest $httpRequest
	 * @return string
	 */
	public function renderHeader($httpRequest = null) {
		return null;
	}

	/**
	 * Render HTML footer for development views
	 */
	public function renderFooter() {
	}

	/**
	 * Write information about the error to the screen
	 *
	 * @param string $httpRequest
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @return string
	 */
	public function renderError($httpRequest, $errno, $errstr, $errfile, $errline) {
		if(!isset(self::$error_types[$errno])) {
			$errorTypeTitle = "UNKNOWN TYPE, ERRNO $errno";
		} else {
			$errorTypeTitle = self::$error_types[$errno]['title'];
		}
		$output = CLI::text("ERROR [" . $errorTypeTitle . "]: $errstr\nIN $httpRequest\n", "red", null, true);
		$output .= CLI::text("Line $errline in $errfile\n\n", "red");

		return $output;
	}

	/**
	 * Write a fragment of the a source file
	 *
	 * @param array $lines An array of file lines; the keys should be the original line numbers
	 * @param int $errline Index of the line in $lines which has the error
	 * @return string
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
	 *
	 * @param array $trace
	 * @return string
	 */
	public function renderTrace($trace = null) {
		$output = "Trace\n=====\n";
		$output .= Backtrace::get_rendered_backtrace($trace ? $trace : debug_backtrace(), true);

		return $output;
	}

	/**
	 * Render the information header for the view
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param string $description
	 * @return string
	 */
	public function renderInfo($title, $subtitle, $description = null) {
		$output = wordwrap(strtoupper($title), self::config()->columns) . "\n";
		$output .= wordwrap($subtitle, self::config()->columns) . "\n";
		$output .= str_repeat('-', min(self::config()->columns, max(strlen($title), strlen($subtitle)))) . "\n";
		$output .= wordwrap($description, self::config()->columns) . "\n\n";

		return $output;
	}

	public function renderVariable($val, $caller) {
		$output = PHP_EOL;
		$output .= CLI::text(str_repeat('=', self::config()->columns), 'green');
		$output .= PHP_EOL;
		$output .= CLI::text($this->formatCaller($caller), 'blue', null, true);
		$output .= PHP_EOL.PHP_EOL;
		if (is_string($val)) {
			$output .= wordwrap($val, self::config()->columns);
		} else {
			$output .= var_export($val, true);
		}
		$output .= PHP_EOL;
		$output .= CLI::text(str_repeat('=', self::config()->columns), 'green');
		$output .= PHP_EOL;

		return $output;
	}
}
