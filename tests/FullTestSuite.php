<?php
@require_once('sapphire/tests/bootstrap.php');

class FullTestSuite {

	public static function get_tests() {
		ManifestBuilder::load_test_manifest();
		$tests = ClassInfo::subclassesFor('SapphireTest');
		array_shift($tests);
		unset($tests['FunctionalTest']);

		// Remove tests that don't need to be executed every time
		unset($tests['PhpSyntaxTest']);

		foreach($tests as $class => $v) {
			$reflection = new ReflectionClass($class);
			if (!$reflection->isInstantiable()) {
				unset($tests[$class]);
			}
		}
		
		return $tests;
	}

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite();
		$classList = self::get_tests();
		foreach($classList as $className) {
			$suite->addTest(new SapphireTestSuite($className));
		}

		return $suite;
	}
}

