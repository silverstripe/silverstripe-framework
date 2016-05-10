<?php
/**
 * @package framework
 * @subpackage tests
 */
class HTMLEditorConfigTest extends SapphireTest {

	public function testEnablePluginsByString() {
		$c = new TinyMCEConfig();
		$c->enablePlugins('plugin1');
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
	}

	public function testEnablePluginsByArray() {
		$c = new TinyMCEConfig();
		$c->enablePlugins(array('plugin1', 'plugin2'));
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
		$this->assertContains('plugin2', array_keys($c->getPlugins()));
	}

	public function testEnablePluginsByMultipleStringParameters() {
		$c = new TinyMCEConfig();
		$c->enablePlugins('plugin1', 'plugin2');
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
		$this->assertContains('plugin2', array_keys($c->getPlugins()));
	}

	public function testEnablePluginsByArrayWithPaths() {
		Config::inst()->update('Director', 'alternate_base_url', 'http://mysite.com/subdir');
		$c = new TinyMCEConfig();
		$c->setTheme('modern');
		$c->setOption('language', 'es');
		$c->disablePlugins('table', 'emoticons', 'paste', 'code', 'link', 'importcss');
		$c->enablePlugins(array(
			'plugin1' => 'mypath/plugin1.js',
			'plugin2' => '/anotherbase/mypath/plugin2.js',
			'plugin3' => 'https://www.google.com/plugin.js',
			'plugin4' => null,
			'plugin5' => null,
		));
		$attributes = $c->getAttributes();
		$config = Convert::json2array($attributes['data-config']);
		$plugins = $config['external_plugins'];
		$this->assertNotEmpty($plugins);

		// Plugin specified via relative url
		$this->assertContains('plugin1', array_keys($plugins));
		$this->assertEquals(
			'http://mysite.com/subdir/mypath/plugin1.js',
			$plugins['plugin1']
		);

		// Plugin specified via root-relative url
		$this->assertContains('plugin2', array_keys($plugins));
		$this->assertEquals(
			'http://mysite.com/anotherbase/mypath/plugin2.js',
			$plugins['plugin2']
		);

		// Plugin specified with absolute url
		$this->assertContains('plugin3', array_keys($plugins));
		$this->assertEquals(
			'https://www.google.com/plugin.js',
			$plugins['plugin3']
		);

		// Plugin specified with standard location
		$this->assertContains('plugin4', array_keys($plugins));
		$this->assertEquals(
			'http://mysite.com/subdir/framework/thirdparty/tinymce/plugins/plugin4/plugin.min.js',
			$plugins['plugin4']
		);

		// Check that internal plugins are extractable separately
		$this->assertEquals(['plugin4', 'plugin5'], $c->getInternalPlugins());

		// Test plugins included via gzip compresser
		Config::inst()->update('HTMLEditorField', 'use_gzip', true);
		$this->assertEquals(
			'framework/thirdparty/tinymce/tiny_mce_gzip.php?js=1&plugins=plugin4,plugin5&themes=modern&languages=es&diskcache=true&src=true',
			$c->getScriptURL()
		);

		// If gzip is disabled only the core plugin is loaded
		Config::inst()->remove('HTMLEditorField', 'use_gzip');
		$this->assertEquals(
			'framework/thirdparty/tinymce/tinymce.min.js',
			$c->getScriptURL()
		);
	}

	public function testDisablePluginsByString() {
		$c = new TinyMCEConfig();
		$c->enablePlugins('plugin1');
		$c->disablePlugins('plugin1');
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
	}

	public function testDisablePluginsByArray() {
		$c = new TinyMCEConfig();
		$c->enablePlugins(array('plugin1', 'plugin2'));
		$c->disablePlugins(array('plugin1', 'plugin2'));
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
		$this->assertNotContains('plugin2', array_keys($c->getPlugins()));
	}

	public function testDisablePluginsByMultipleStringParameters() {
		$c = new TinyMCEConfig();
		$c->enablePlugins('plugin1', 'plugin2');
		$c->disablePlugins('plugin1', 'plugin2');
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
		$this->assertNotContains('plugin2', array_keys($c->getPlugins()));
	}

	public function testDisablePluginsByArrayWithPaths() {
		$c = new TinyMCEConfig();
		$c->enablePlugins(array('plugin1' => '/mypath/plugin1', 'plugin2' => '/mypath/plugin2'));
		$c->disablePlugins(array('plugin1', 'plugin2'));
		$plugins = $c->getPlugins();
		$this->assertNotContains('plugin1', array_keys($plugins));
		$this->assertNotContains('plugin2', array_keys($plugins));
	}

	public function testRequireJSIncludesAllConfigs() {
		$a = HTMLEditorConfig::get('configA');
		$c = HTMLEditorConfig::get('configB');

		$aAttributes = $a->getAttributes();
		$cAttributes = $c->getAttributes();

		$this->assertNotEmpty($aAttributes['data-config']);
		$this->assertNotEmpty($cAttributes['data-config']);
	}
}
