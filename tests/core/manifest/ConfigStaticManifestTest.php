<?php

class ConfigStaticManifestTest extends SapphireTest {

	/* Example statics */

	// Different access levels
	static $nolevel;
	public static $public;
	protected static $protected;
	private static $private;
	static public $public2;
	static protected $protected2;
	static private $private2;

	// Assigning values
	static $snone;
	static $snull = null;
	static $sint = 1;
	static $sfloat = 2.5;
	static $sstring = 'string';
	static $sarray = array(1, 2, array(3, 4), 5);

	// Assigning multiple values
	static $onone, $onull = null, $oint = 1, $ofloat = 2.5, $ostring = 'string', $oarray = array(1, 2, array(3, 4), 5);

	static
		$mnone,
		$mnull = null,
		$mint = 1,
		$mfloat = 2.5,
		$mstring = 'string',
		$marray = array(
			1, 2,
			array(3, 4),
			5
		);

	// Should ignore static methpds
	static function static_method() {}

	// Should ignore method statics
	function instanceMethod() {
		static $method_static;
	}

	/* The tests */

	protected function parseSelf() {
		static $statics = null;

		if ($statics === null) {
			$parser = new SS_ConfigStaticManifest_Parser(__FILE__);
			$statics = $parser->parse();
		}

		return $statics;
	}

	public function testParsingAccessLevels() {
		$statics = $this->parseSelf();

		$levels = array(
			'nolevel' => null,
			'public' => T_PUBLIC,
			'public2' => T_PUBLIC,
			'protected' => T_PROTECTED,
			'protected2' => T_PROTECTED,
			'private' => T_PRIVATE,
			'private2' => T_PRIVATE
		);

		foreach($levels as $var => $level) {
			$this->assertEquals(
				$level,
				$statics[__CLASS__][$var]['access'],
				'Variable '.$var.' has '.($level ? token_name($level) : 'no').' access level'
			);
		}
	}

	public function testParsingValues() {
		$statics = $this->parseSelf();

		// Check assigning values
		$values = array(
			'none',
			'null',
			'int',
			'float',
			'string',
			'array',
		);

		$prepends = array(
			's', // Each on it's own
			'o', // All on one line
			'm'  // All in on static statement, but each on own line
		);

		foreach ($values as $value) {
			foreach ($prepends as $prepend) {
				$var = "$prepend$value";

				$this->assertEquals(
					self::$$var,
					$statics[__CLASS__][$var]['value'],
					'Variable '.$var.' value is extracted properly'
				);
			}
		}
	}

	public function testIgnoresMethodStatics() {
		$statics = $this->parseSelf();
		$this->assertNull(@$statics[__CLASS__]['method_static']);
	}

	public function testIgnoresStaticMethods() {
		$statics = $this->parseSelf();
		$this->assertNull(@$statics[__CLASS__]['static_method']);
	}
}