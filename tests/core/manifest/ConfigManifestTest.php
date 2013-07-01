<?php

class ConfigManifestTest_ConfigManifestAccess extends SS_ConfigManifest {
	public function relativeOrder($a, $b) {
		return parent::relativeOrder($a, $b);
	}
}

class ConfigManifestTest extends SapphireTest {

	protected function getConfigFixtureValue($name) {
		$manifest = new SS_ConfigManifest(dirname(__FILE__).'/fixtures/configmanifest', true, true);
		return $manifest->get('ConfigManifestTest', $name);
	}

	public function testClassRules() {
		$config = $this->getConfigFixtureValue('Class');

		$this->assertEquals(
			'Yes', @$config['DirectorExists'],
			'Only rule correctly detects existing class'
		);

		$this->assertEquals(
			'No', @$config['NoSuchClassExists'],
			'Except rule correctly detects missing class'
		);
	}

	public function testModuleRules() {
		$config = $this->getConfigFixtureValue('Module');

		$this->assertEquals(
			'Yes', @$config['MysiteExists'],
			'Only rule correctly detects existing module'
		);

		$this->assertEquals(
			'No', @$config['NoSuchModuleExists'],
			'Except rule correctly detects missing module'
		);
	}

	public function testEnvVarSetRules() {
		$_ENV['EnvVarSet_Foo'] = 1;
		$config = $this->getConfigFixtureValue('EnvVarSet');

		$this->assertEquals(
			'Yes', @$config['FooSet'],
			'Only rule correctly detects set environment variable'
		);

		$this->assertEquals(
			'No', @$config['BarSet'],
			'Except rule correctly detects unset environment variable'
		);
	}

	public function testConstantDefinedRules() {
		define('ConstantDefined_Foo', 1);
		$config = $this->getConfigFixtureValue('ConstantDefined');

		$this->assertEquals(
			'Yes', @$config['FooDefined'],
			'Only rule correctly detects defined constant'
		);

		$this->assertEquals(
			'No', @$config['BarDefined'],
			'Except rule correctly detects undefined constant'
		);
	}

	public function testEnvOrConstantMatchesValueRules() {
		$_ENV['EnvOrConstantMatchesValue_Foo'] = 'Foo';
		define('EnvOrConstantMatchesValue_Bar', 'Bar');
		$config = $this->getConfigFixtureValue('EnvOrConstantMatchesValue');

		$this->assertEquals(
			'Yes', @$config['FooIsFoo'],
			'Only rule correctly detects environment variable matches specified value'
		);

		$this->assertEquals(
			'Yes', @$config['BarIsBar'],
			'Only rule correctly detects constant matches specified value'
		);

		$this->assertEquals(
			'No', @$config['FooIsQux'],
			'Except rule correctly detects environment variable that doesn\'t match specified value'
		);

		$this->assertEquals(
			'No', @$config['BarIsQux'],
			'Except rule correctly detects environment variable that doesn\'t match specified value'
		);

		$this->assertEquals(
			'No', @$config['BazIsBaz'],
			'Except rule correctly detects undefined variable'
		);
	}

	public function testEnvironmentRules() {
		foreach (array('dev', 'test', 'live') as $env) {
			Config::inst()->nest();

			Config::inst()->update('Director', 'environment_type', $env);
			$config = $this->getConfigFixtureValue('Environment');

			foreach (array('dev', 'test', 'live') as $check) {
				$this->assertEquals(
					$env == $check ? $check : 'not'.$check, @$config[ucfirst($check).'Environment'],
					'Only & except rules correctly detect environment'
				);
			}

			Config::inst()->unnest();
		}
	}

	public function testDynamicEnvironmentRules() {
		Config::inst()->nest();

		// First, make sure environment_type is live
		Config::inst()->update('Director', 'environment_type', 'live');
		$this->assertEquals('live', Config::inst()->get('Director', 'environment_type'));

		// Then, load in a new manifest, which includes a _config.php that sets environment_type to dev
		$manifest = new SS_ConfigManifest(dirname(__FILE__).'/fixtures/configmanifest_dynamicenv', true, true);
		Config::inst()->pushConfigYamlManifest($manifest);

		// Make sure that stuck
		$this->assertEquals('dev', Config::inst()->get('Director', 'environment_type'));

		// And that the dynamic rule was calculated correctly
		$this->assertEquals('dev', Config::inst()->get('ConfigManifestTest', 'DynamicEnvironment'));

		Config::inst()->unnest();
	}


	public function testRelativeOrder() {
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

		// Wildcard should match any module even if there is an opposing rule, if opposing rule doesn't match, no
		// matter how many opposing rules
		$this->assertEquals($accessor->relativeOrder(
			$beforeWildcardedAfterExplicit,
			array('module' => 'qux', 'file' => 'delta', 'name' => '4')
		), 'before');

		// Wildcard should match any module even if there is an opposing rule, if opposing rule doesn't match
		// (even if some portions do)
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