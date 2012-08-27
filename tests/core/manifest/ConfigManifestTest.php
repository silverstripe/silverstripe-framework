<?php

class ConfigManifestTest_ConfigManifestAccess extends SS_ConfigManifest {
	public function relativeOrder($a, $b) {
		return parent::relativeOrder($a, $b);
	}
}

class ConfigManifestTest extends SapphireTest {

	function testRelativeOrder() {
		$accessor = new ConfigManifestTest_ConfigManifestAccess(BASE_PATH, true, false);

		// A fragment with a fully wildcard before rule
		$beforeWildcarded = array(
			'module' => 'foo', 'file' => 'alpha', 'name' => '1',
			'before' => array(array('module' => '*', 'file' => '*', 'name' => '*'))
		);
		// A fragment with a fully wildcard before rule and a fully explicit after rule
		$beforeWildcardedAfterExplicit = array_merge($beforeWildcarded, array(
			'after' => array(array('module' => 'bar', 'file' => 'beta', 'name' => '2'))
		));
		// A fragment with a fully wildcard before rule and two fully explicit after rules
		$beforeWildcardedAfterTwoExplicitRules = array_merge($beforeWildcarded, array(
			'after' => array(
				array('module' => 'bar', 'file' => 'beta', 'name' => '2'),
				array('module' => 'baz', 'file' => 'gamma', 'name' => '3')
			)
		));
		// A fragment with a fully wildcard before rule and a partially explicit after rule
		$beforeWildcardedAfterPartialWildcarded = array_merge($beforeWildcarded, array(
			'after' => array(array('module' => 'bar', 'file' => 'beta', 'name' => '*'))
		));

		// Wildcard should match any module
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcarded,
			array('module' => 'qux', 'file' => 'delta', 'name' => '4')
		), 'before');

		// Wildcard should match any module even if there is an opposing rule, if opposing rule doesn't match
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterExplicit,
			array('module' => 'qux', 'file' => 'delta', 'name' => '4')
		), 'before');

		// Wildcard should match any module even if there is an opposing rule, if opposing rule doesn't match, no matter how many opposing rules
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterExplicit,
			array('module' => 'qux', 'file' => 'delta', 'name' => '4')
		), 'before');

		// Wildcard should match any module even if there is an opposing rule, if opposing rule doesn't match (even if some portions do)
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterExplicit,
			array('module' => 'bar', 'file' => 'beta', 'name' => 'nomatchy')
		), 'before');

		// When opposing rule matches, wildcard should be ignored
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterExplicit,
			array('module' => 'bar', 'file' => 'beta', 'name' => '2')
		), 'after');

		// When any one of mutiple opposing rule exists, wildcard should be ignored
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterTwoExplicitRules,
			array('module' => 'bar', 'file' => 'beta', 'name' => '2')
		), 'after');

		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterTwoExplicitRules,
			array('module' => 'baz', 'file' => 'gamma', 'name' => '3')
		), 'after');

		// When two opposed wildcard rules, and more specific one doesn't match, other should win
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterPartialWildcarded,
			array('module' => 'qux', 'file' => 'delta', 'name' => '4')
		), 'before');

		// When two opposed wildcard rules, and more specific one does match, more specific one should win
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterPartialWildcarded,
			array('module' => 'bar', 'file' => 'beta', 'name' => 'wildcardmatchy')
		), 'after');
	}

}