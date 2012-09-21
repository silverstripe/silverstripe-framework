<?php
/**
 * @package framework
 * @subpackage tests
 */
class HtmlEditorConfigTest extends SapphireTest {

	public function testEnablePluginsByString() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins('plugin1');
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
	}
	
	public function testEnablePluginsByArray() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins(array('plugin1', 'plugin2'));
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
		$this->assertContains('plugin2', array_keys($c->getPlugins()));
	}
	
	public function testEnablePluginsByMultipleStringParameters() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins('plugin1', 'plugin2');
		$this->assertContains('plugin1', array_keys($c->getPlugins()));
		$this->assertContains('plugin2', array_keys($c->getPlugins()));
	}
	
	public function testEnablePluginsByArrayWithPaths() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins(array('plugin1' => '/mypath/plugin1', 'plugin2' => '/mypath/plugin2'));
		$plugins = $c->getPlugins();
		$this->assertContains('plugin1', array_keys($plugins));
		$this->assertEquals('/mypath/plugin1', $plugins['plugin1']);
		$this->assertContains('plugin2', array_keys($plugins));
		$this->assertEquals('/mypath/plugin2', $plugins['plugin2']);
	}
	
	public function testDisablePluginsByString() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins('plugin1');
		$c->disablePlugins('plugin1');
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
	}
	
	public function testDisablePluginsByArray() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins(array('plugin1', 'plugin2'));
		$c->disablePlugins(array('plugin1', 'plugin2'));
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
		$this->assertNotContains('plugin2', array_keys($c->getPlugins()));
	}
	
	public function testDisablePluginsByMultipleStringParameters() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins('plugin1', 'plugin2');
		$c->disablePlugins('plugin1', 'plugin2');
		$this->assertNotContains('plugin1', array_keys($c->getPlugins()));
		$this->assertNotContains('plugin2', array_keys($c->getPlugins()));
	}
	
	public function testDisablePluginsByArrayWithPaths() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins(array('plugin1' => '/mypath/plugin1', 'plugin2' => '/mypath/plugin2'));
		$c->disablePlugins(array('plugin1', 'plugin2'));
		$plugins = $c->getPlugins();
		$this->assertNotContains('plugin1', array_keys($plugins));
		$this->assertNotContains('plugin2', array_keys($plugins));
	}
	
	public function testGenerateJSWritesPlugins() {
		$c = new HtmlEditorConfig();
		$c->enablePlugins(array('plugin1'));
		$c->enablePlugins(array('plugin2' => '/mypath/plugin2'));

		$this->assertContains('plugin1', $c->generateJS());
		$this->assertContains('tinymce.PluginManager.load("plugin2", "/mypath/plugin2");', $c->generateJS());
	}
}
