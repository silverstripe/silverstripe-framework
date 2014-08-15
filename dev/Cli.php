<?php
/**
 * Class to facilitate command-line output.
 * Support less-trivial output stuff such as colours (on xterm-color)
 *
 * @package framework
 * @subpackage dev
 */
class SS_Cli extends Object {
	/**
	 * Returns true if the current STDOUT supports the use of colour control codes.
	 */
	public static function supports_colour() {
		// Special case for buildbot
		if(isset($_ENV['_']) && strpos($_ENV['_'],'buildbot') !== false) return false;

		if(!defined('STDOUT')) define('STDOUT', fopen("php://stdout","w"));
		return function_exists('posix_isatty') ? @posix_isatty(STDOUT) : false;
	}

	/**
	 * Return text encoded for CLI output, optionally coloured
	 * @param string $fgColour The foreground colour - black, red, green, yellow, blue, magenta, cyan, white.
	 *                         Null is default.
	 * @param string $bgColour The foreground colour - black, red, green, yellow, blue, magenta, cyan, white.
	 *                         Null is default.
	 * @param string $bold A boolean variable - bold or not.
	 */
	public static function text($text, $fgColour = null, $bgColour = null, $bold = false) {
		if(!self::supports_colour()) return $text;

		if($fgColour || $bgColour || $bold) {
			$prefix = self::start_colour($fgColour, $bgColour, $bold);
			$suffix = self::end_colour();
		} else {
			$prefix = $suffix = "";
		}

		return $prefix . $text . $suffix;


	}

	/**
	 * Send control codes for changing text to the given colour
	 * @param string $fgColour The foreground colour - black, red, green, yellow, blue, magenta, cyan, white.
	 *                         Null is default.
	 * @param string $bgColour The foreground colour - black, red, green, yellow, blue, magenta, cyan, white.
	 *                         Null is default.
	 * @param string $bold A boolean variable - bold or not.
	 */
	public static function start_colour($fgColour = null, $bgColour = null, $bold = false) {
		if(!self::supports_colour()) return "";
		$colours = array(
			'black' => 0,
			'red' => 1,
			'green' => 2,
			'yellow' => 3,
			'blue' => 4,
			'magenta' => 5,
			'cyan' => 6,
			'white' => 7,
		);

		$prefix = "";

		if($fgColour || $bold) {
			if(!$fgColour) $fgColour = "white";
			$prefix .= "\033[" . ($bold ? "1;" :"") . "3" . $colours[$fgColour] . "m";
		}


		if($bgColour) {
			$prefix .= "\033[4" . $colours[$bgColour] . "m";
		}

		return $prefix;
	}

	/**
	 * Send control codes for returning to normal colour
	 */
	public static function end_colour() {
		return self::supports_colour() ? "\033[0m" : "";
	}
}

