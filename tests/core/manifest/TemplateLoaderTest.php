<?php
/**
 * Tests for the {@link SS_TemplateLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateLoaderTest extends SapphireTest {

	private $base;
	private $manifest;
	private $loader;

	/**
	 * Set up manifest before each test
	 */
	public function setUp() {
		parent::setUp();
		$this->base = dirname(__FILE__) . '/fixtures/templatemanifest';
		$this->manifest = new SS_TemplateManifest($this->base, 'myproject', false, true);
		$this->loader = new SS_TemplateLoader();
		$this->refreshLoader();
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from module
	 */
	public function testFindTemplatesInModule() {
		$expect = array(
			'main'   => "$this->base/module/templates/Page.ss",
			'Layout' => "$this->base/module/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page'));
		$this->assertEquals($expect, $this->loader->findTemplates('PAGE'));
		$this->assertEquals($expect, $this->loader->findTemplates(array('Foo', 'Page')));
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from set theme
	 */
	public function testFindTemplatesInTheme() {
		$expect = array(
			'main'   => "$this->base/themes/theme/templates/Page.ss",
			'Layout' => "$this->base/themes/theme/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page', 'theme'));
		$this->assertEquals($expect, $this->loader->findTemplates('PAGE', 'theme'));
		$this->assertEquals($expect, $this->loader->findTemplates(array('Foo', 'Page'), 'theme'));
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from project without a set theme
	 */
	public function testFindTemplatesInApplication() {
		$templates = array(
			$this->base . '/myproject/templates/Page.ss',
			$this->base . '/myproject/templates/Layout/Page.ss'
		);
		$this->createTestTemplates($templates);
		$this->refreshLoader();

		$expect = array(
			'main'   => "$this->base/myproject/templates/Page.ss",
			'Layout' => "$this->base/myproject/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page'));
		$this->assertEquals($expect, $this->loader->findTemplates('PAGE'));
		$this->assertEquals($expect, $this->loader->findTemplates(array('Foo', 'Page')));

		$this->removeTestTemplates($templates);
	}

	/**
	 * Test that 'Layout' template is loaded from module
	 */
	public function testFindTemplatesInModuleLayout() {
		$expect = array(
			'main' => "$this->base/module/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Layout/Page'));
	}

	/**
	 * Test that 'Layout' template is loaded from theme
	 */
	public function testFindTemplatesInThemeLayout() {
		$expect = array(
			'main' => "$this->base/themes/theme/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Layout/Page', 'theme'));
	}

	/**
	 * Test that 'main' template is found in theme and 'Layout' is found in module
	 */
	public function testFindTemplatesMainThemeLayoutModule() {
		$expect = array(
			'main'   => "$this->base/themes/theme/templates/CustomThemePage.ss",
			'Layout' => "$this->base/module/templates/Layout/CustomThemePage.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates(array('CustomThemePage', 'Page'), 'theme'));
	}

	/**
	 * Test that project template overrides module template of same name
	 */
	public function testFindTemplatesApplicationOverridesModule() {
		$expect = array(
			'main'   => "$this->base/myproject/templates/CustomTemplate.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('CustomTemplate'));
	}

	/**
	 * Test that project templates overrides theme templates
	 */
	public function testFindTemplatesApplicationOverridesTheme() {
		$templates = array(
			$this->base . '/myproject/templates/Page.ss',
			$this->base . '/myproject/templates/Layout/Page.ss'
		);
		$this->createTestTemplates($templates);
		$this->refreshLoader();

		$expect = array(
			'main'   => "$this->base/myproject/templates/Page.ss",
			'Layout' => "$this->base/myproject/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page'), 'theme');

		$this->removeTestTemplates($templates);
	}

	/**
	 * Test that project 'Layout' template overrides theme 'Layout' template
	 */
	public function testFindTemplatesApplicationLayoutOverridesThemeLayout() {
		$templates = array(
			$this->base . '/myproject/templates/Layout/Page.ss'
		);
		$this->createTestTemplates($templates);
		$this->refreshLoader();

		$expect = array(
			'main' => "$this->base/themes/theme/templates/Page.ss",
			'Layout' => "$this->base/myproject/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page', 'theme'));

		$this->removeTestTemplates($templates);
	}

	/**
	 * Test that project 'main' template overrides theme 'main' template
	 */
	public function testFindTemplatesApplicationMainOverridesThemeMain() {
		$templates = array(
			$this->base . '/myproject/templates/Page.ss'
		);
		$this->createTestTemplates($templates);
		$this->refreshLoader();

		$expect = array(
			'main' => "$this->base/myproject/templates/Page.ss",
			'Layout' => "$this->base/themes/theme/templates/Layout/Page.ss"
		);
		$this->assertEquals($expect, $this->loader->findTemplates('Page', 'theme'));

		$this->removeTestTemplates($templates);
	}

	protected function refreshLoader() {
		$this->manifest->regenerate(false);
		$this->loader->pushManifest($this->manifest);
	}

	protected function createTestTemplates($templates) {
		foreach ($templates as $template) {
			file_put_contents($template, '');
		}
	}

	protected function removeTestTemplates($templates) {
		foreach ($templates as $template) {
			unlink($template);
		}
	}

}
