<?php
@require_once('sapphire/tests/bootstrap.php');

/**
 * Alternative to letting PHPUnit handle class retrieval via
 * traversing the filesystem. Works around restrictions of PHPUnit
 * on running tests on multiple directories at once, without resorting
 * to group or testsuite definitions in a custom phpunit.xml file.
 * 
 * Usage:
 * - "phpunit sapphire/tests/FullTestSuite.php" 
 *    (all tests)
 * - "phpunit sapphire/tests/FullTestSuite.php '' module=sapphire,cms" 
 *   (comma-separated modules. empty quotes are necessary to avoid PHPUnit argument confusion)
 * 
 * See http://www.phpunit.de/manual/current/en/organizing-tests.html#organizing-tests.testsuite
 * 
 * Note: We can't unit test this class because of segfaults in PHP5.3 when trying to
 * use get_all_tests() within a SapphireTest.
 * 
 * @package sapphire
 * @subpackage testing
 */
class FullTestSuite {
	
	/**
	 * Called by the PHPUnit runner to gather runnable tests.
	 * 
	 * @return PHPUnit_Framework_TestSuite
	 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite();
		if(isset($_GET['module'])) {
			$classList = self::get_module_tests($_GET['module']);
		} else {
			$classList = self::get_all_tests();
		}

		foreach($classList as $className) {
			$suite->addTest(new SapphireTestSuite($className));
		}

		return $suite;
	}

	/**
	 * @return Array
	 */
	public static function get_all_tests() {
		ManifestBuilder::load_test_manifest();
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		
		return $tests;
	}
		
	/**
	 * Run tests for one or more "modules".
	 * A module is generally a toplevel folder, e.g. "mysite" or "sapphire".
	 * 
	 * @param String $nameStr
	 * @return Array
	 */
	protected static function get_module_tests($namesStr) {
		$tests = array();
		$names = explode(',', $namesStr);
		foreach($names as $name) {
			$classesForModule = ClassInfo::classes_for_folder($name);
			if($classesForModule) foreach($classesForModule as $class) {
				if(class_exists($class) && is_subclass_of($class, 'SapphireTest')) {
					$tests[] = $class;
				}
			}
		}

		return $tests;
	}
}

