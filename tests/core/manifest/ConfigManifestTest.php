<?php

class ConfigManifestTest_ConfigManifestAccess extends SS_ConfigManifest {
	public function relativeOrder($a, $b) {
		return parent::relativeOrder($a, $b);
	}
}

class ConfigManifestTest extends SapphireTest {

	/**
	 * This is a helper method for getting a new manifest
	 * @param $name
	 * @return any
	 */
	protected function getConfigFixtureValue($name) {
		$manifest = new SS_ConfigManifest(dirname(__FILE__).'/fixtures/configmanifest', true, true);
		return $manifest->get('ConfigManifestTest', $name);
	}

	/**
	 * This is a helper method for displaying a relevant message about a parsing failure
	 */
	protected function getParsedAsMessage($path) {
		return sprintf('Reference path "%s" failed to parse correctly', $path);
	}

	/**
	 * A helper method to return a mock of the cache in order to test expectations and reduce dependency
	 * @return Zend_Cache_Core
	 */
	protected function getCacheMock() {
		return $this->getMock(
			'Zend_Cache_Core',
			array('load', 'save'),
			array(),
			'',
			false
		);
	}

	/**
	 * A helper method to return a mock of the manifest in order to test expectations and reduce dependency
	 * @param $methods
	 * @return SS_ConfigManifest
	 */
	protected function getManifestMock($methods) {
		return $this->getMock(
			'SS_ConfigManifest',
			$methods,
			array(), // no constructor arguments
			'', // default
			false // don't call the constructor
		);
	}

	/**
	 * Test the caching functionality when we are forcing regeneration
	 *
	 * 1. Test that regenerate is called in the default case and that cache->load isn't
	 * 2. Test that save is called correctly after the regeneration
	 */
	public function testCachingForceRegeneration() {
		// Test that regenerate is called correctly.
		$manifest = $this->getManifestMock(array('getCache', 'regenerate', 'buildYamlConfigVariant'));

		$manifest->expects($this->once()) // regenerate should be called once
			->method('regenerate')
			->with($this->equalTo(true)); // includeTests = true

		// Set up a cache where we expect load to never be called
		$cache = $this->getCacheMock();
		$cache->expects($this->never())
			->method('load');

		$manifest->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$manifest->__construct(dirname(__FILE__).'/fixtures/configmanifest', true, true);

		// Test that save is called correctly
		$manifest = $this->getManifestMock(array('getCache'));

		$cache = $this->getCacheMock();
		$cache->expects($this->atLeastOnce())
			->method('save');

		$manifest->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$manifest->__construct(dirname(__FILE__).'/fixtures/configmanifest', true, true);
	}

	/**
	 * Test the caching functionality when we are not forcing regeneration
	 *
	 * 1. Test that load is called
	 * 2. Test the regenerate is called when the cache is unprimed
	 * 3. Test that when there is a value in the cache regenerate isn't called
	 */
	public function testCachingNotForceRegeneration() {
		// Test that load is called
		$manifest = $this->getManifestMock(array('getCache', 'regenerate', 'buildYamlConfigVariant'));

		// Load should be called twice
		$cache = $this->getCacheMock();
		$cache->expects($this->exactly(2))
			->method('load');

		$manifest->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$manifest->__construct(dirname(__FILE__).'/fixtures/configmanifest', true, false);


		// Now test that regenerate is called because the cache is unprimed
		$manifest = $this->getManifestMock(array('getCache', 'regenerate', 'buildYamlConfigVariant'));

		$cache = $this->getCacheMock();
		$cache->expects($this->exactly(2))
			->method('load')
			->will($this->onConsecutiveCalls(false, false));

		$manifest->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$manifest->expects($this->once())
			->method('regenerate')
			->with($this->equalTo(false)); //includeTests = false

		$manifest->__construct(dirname(__FILE__).'/fixtures/configmanifest', false, false);

		// Now test that when there is a value in the cache that regenerate isn't called
		$manifest = $this->getManifestMock(array('getCache', 'regenerate', 'buildYamlConfigVariant'));

		$cache = $this->getCacheMock();
		$cache->expects($this->exactly(2))
			->method('load')
			->will($this->onConsecutiveCalls(array(), array()));

		$manifest->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$manifest->expects($this->never())
			->method('regenerate');

		$manifest->__construct(dirname(__FILE__).'/fixtures/configmanifest', false, false);
	}

	/**
	 * This test checks the processing of before and after reference paths (module-name/filename#fragment)
	 * This method uses fixture/configmanifest/mysite/_config/addyamlconfigfile.yml as a fixture
	 */
	public function testAddYAMLConfigFileReferencePathParsing() {
		// Use a mock to avoid testing unrelated functionality
		$manifest = $this->getManifestMock(array('addModule'));

		// This tests that the addModule method is called with the correct value
		$manifest->expects($this->once())
			->method('addModule')
			->with($this->equalTo(dirname(__FILE__).'/fixtures/configmanifest/mysite'));

		// Call the method to be tested
		$manifest->addYAMLConfigFile(
			'addyamlconfigfile.yml',
			dirname(__FILE__).'/fixtures/configmanifest/mysite/_config/addyamlconfigfile.yml',
			false
		);

		// There is no getter for yamlConfigFragments
		$property = new ReflectionProperty('SS_ConfigManifest', 'yamlConfigFragments');
		$property->setAccessible(true);

		// Get the result back from the parsing
		$result = $property->getValue($manifest);

		$this->assertEquals(
			array(
				array(
					'module' => 'mysite',
					'file' => 'testfile',
					'name' => 'fragment',
				),
			),
			@$result[0]['after'],
			$this->getParsedAsMessage('mysite/testfile#fragment')
		);

		$this->assertEquals(
			array(
				array(
					'module' => 'test-module',
					'file' => 'testfile',
					'name' => 'fragment',
				),
			),
			@$result[1]['after'],
			$this->getParsedAsMessage('test-module/testfile#fragment')
		);

		$this->assertEquals(
			array(
				array(
					'module' => '*',
					'file' => '*',
					'name' => '*',
				),
			),
			@$result[2]['after'],
			$this->getParsedAsMessage('*')
		);

		$this->assertEquals(
			array(
				array(
					'module' => '*',
					'file' => 'testfile',
					'name' => '*'
				),
			),
			@$result[3]['after'],
			$this->getParsedAsMessage('*/testfile')
		);

		$this->assertEquals(
			array(
				array(
					'module' => '*',
					'file' => '*',
					'name' => 'fragment'
				),
			),
			@$result[4]['after'],
			$this->getParsedAsMessage('*/*#fragment')
		);

		$this->assertEquals(
			array(
				array(
					'module' => '*',
					'file' => '*',
					'name' => 'fragment'
				),
			),
			@$result[5]['after'],
			$this->getParsedAsMessage('#fragment')
		);

		$this->assertEquals(
			array(
				array(
					'module' => 'test-module',
					'file' => '*',
					'name' => 'fragment'
				),
			),
			@$result[6]['after'],
			$this->getParsedAsMessage('test-module#fragment')
		);

		$this->assertEquals(
			array(
				array(
					'module' => 'test-module',
					'file' => '*',
					'name' => '*'
				),
			),
			@$result[7]['after'],
			$this->getParsedAsMessage('test-module')
		);

		$this->assertEquals(
			array(
				array(
					'module' => 'test-module',
					'file' => '*',
					'name' => '*'
				),
			),
			@$result[8]['after'],
			$this->getParsedAsMessage('test-module/*')
		);

		$this->assertEquals(
			array(
				array(
					'module' => 'test-module',
					'file' => '*',
					'name' => '*'
				),
			),
			@$result[9]['after'],
			$this->getParsedAsMessage('test-module/*/#*')
		);
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
			Config::nest();

			Config::inst()->update('Director', 'environment_type', $env);
			$config = $this->getConfigFixtureValue('Environment');

			foreach (array('dev', 'test', 'live') as $check) {
				$this->assertEquals(
					$env == $check ? $check : 'not'.$check, @$config[ucfirst($check).'Environment'],
					'Only & except rules correctly detect environment'
				);
			}

			Config::unnest();
		}
	}

	public function testDynamicEnvironmentRules() {
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
	}

	public function testMultipleRules() {
		$_ENV['MultilpleRules_EnvVariableSet'] = 1;
		define('MultilpleRules_DefinedConstant', 'defined');
		$config = $this->getConfigFixtureValue('MultipleRules');

		$this->assertFalse(
			isset($config['TwoOnlyFail']),
			'Fragment is not included if one of the Only rules fails.'
		);

		$this->assertTrue(
			isset($config['TwoOnlySucceed']),
			'Fragment is included if both Only rules succeed.'
		);

		$this->assertTrue(
			isset($config['TwoExceptSucceed']),
			'Fragment is included if one of the Except rules matches.'
		);

		$this->assertFalse(
			isset($config['TwoExceptFail']),
			'Fragment is not included if both of the Except rules fail.'
		);

		$this->assertFalse(
			isset($config['TwoBlocksFail']),
			'Fragment is not included if one block fails.'
		);

		$this->assertTrue(
			isset($config['TwoBlocksSucceed']),
			'Fragment is included if both blocks succeed.'
		);
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
