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
	static $nolevel_after_private;

	// Assigning values
	static $snone;
	static $snull = null;
	static $sint = 1;
	static $sfloat = 2.5;
	static $sstring = 'string';
	static $sarray = array(1, 2, array(3, 4), 5);
	static $sheredoc = <<<DOC
heredoc
DOC;
	static $snowdoc = <<<'DOC'
nowdoc
DOC;

	// Assigning multiple values
	static $onone, $onull = null, $oint = 1, $ofloat = 2.5, $ostring = 'string', $oarray = array(1, 2, array(3, 4), 5), $oheredoc = <<<DOC
heredoc
DOC
, $onowdoc = <<<'DOC'
nowdoc
DOC;

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
		),
		$mheredoc = <<<DOC
heredoc
DOC
		,
		$mnowdoc = <<<'DOC'
nowdoc
DOC;


	static /* Has comment inline */ $commented_int = 1, /* And here */ $commented_string = 'string';

	static
		/**
		 * Has docblock inline
		 */
		$docblocked_int = 1,
		/** And here */
		$docblocked_string = 'string';

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
			$parser->parse();
		}

		return $parser;
	}

	public function testParsingAccessLevels() {
		$statics = $this->parseSelf()->getStatics();

		$levels = array(
			'nolevel' => null,
			'public' => T_PUBLIC,
			'public2' => T_PUBLIC,
			'protected' => T_PROTECTED,
			'protected2' => T_PROTECTED,
			'private' => T_PRIVATE,
			'private2' => T_PRIVATE,
			'nolevel_after_private' => null
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
		$statics = $this->parseSelf()->getStatics();

		// Check assigning values
		$values = array(
			'none',
			'null',
			'int',
			'float',
			'string',
			'array',
			'heredoc',
			'nowdoc'
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

	public function testIgnoreComments() {
		$statics = $this->parseSelf()->getStatics();

		$this->assertEquals(self::$commented_int, $statics[__CLASS__]['commented_int']['value']);
		$this->assertEquals(self::$commented_string, $statics[__CLASS__]['commented_string']['value']);

		$this->assertEquals(self::$docblocked_int, $statics[__CLASS__]['docblocked_int']['value']);
		$this->assertEquals(self::$docblocked_string, $statics[__CLASS__]['docblocked_string']['value']);
	}

	public function testIgnoresMethodStatics() {
		$statics = $this->parseSelf()->getStatics();
		$this->assertNull(@$statics[__CLASS__]['method_static']);
	}

	public function testIgnoresStaticMethods() {
		$statics = $this->parseSelf()->getStatics();
		$this->assertNull(@$statics[__CLASS__]['static_method']);
	}

	public function testParsingShortArray() {
		if(version_compare(PHP_VERSION, '5.4', '<')) {
			$this->markTestSkipped('This test requires PHP 5.4 or higher');
			return;
		}

		$parser = new SS_ConfigStaticManifest_Parser(__DIR__ .
			'/ConfigStaticManifestTest/ConfigStaticManifestTestMyObject.php');
		$parser->parse();

		$statics = $parser->getStatics();

		$expectedValue = array(
			'Name' => 'Varchar',
			'Description' => 'Text',
		);

		$this->assertEquals($expectedValue, $statics['ConfigStaticManifestTestMyObject']['db']['value']);
	}

	public function testParsingNamespacesclass() {
		$parser = new SS_ConfigStaticManifest_Parser(__DIR__ .
			'/ConfigStaticManifestTest/ConfigStaticManifestTestNamespace.php');
		$parser->parse();

		$statics = $parser->getStatics();

		$expectedValue = array(
			'Name' => 'Varchar',
			'Description' => 'Text',
		);

		$this->assertEquals($expectedValue, $statics['config\staticmanifest\NamespaceTest']['db']['value']);
	}
}
