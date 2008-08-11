<?php
/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 * 
 * @package sapphire
 * @subpackage dev
 * 
 * @todo Perhaps DebugView should be an interface / ABC, implemented by HTMLDebugView and CliDebugView?
 */

class CliDebugView extends DebugView {

	/**
	 * Render HTML header for development views
	 */
	public function writeHeader($httpRequest) {
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
		echo "ERROR: $errstr\nIN $httpRequest\n";
		echo "Line $errline in $errfile\n\n";
	}

	/**
	 * Write a fragment of the a source file
	 * @param $lines An array of file lines; the keys should be the original line numbers
	 */
	function writeSourceFragment($lines, $errline) {
		echo "Source\n======\n";
		foreach($lines as $offset => $line) {
			echo ($offset == $errline) ? "* " : "  ";
			echo str_pad("$offset:",5);
			echo wordwrap($line, 100, "\n       ");
		}
		echo "\n";
	}
	
	/**
	 * Write a backtrace
	 */
	function writeTrace() {
		Debug::backtrace();
	}
	
}

?>