<?php

use SilverStripe\View\TemplateLoader;
use SilverStripe\View\ThemeManifest;

/**
 * Tests for the {@link TemplateLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateLoaderTest extends SapphireTest {

	private $base;

	/**
	 * @var ThemeManifest
	 */
	private $manifest;

	/**
	 * @var TemplateLoader
	 */
	private $loader;

	/**
	 * Set up manifest before each test
	 */
	public function setUp() {
		parent::setUp();
		// Fake project root
		$this->base = dirname(__FILE__) . '/fixtures/templatemanifest';
		// New ThemeManifest for that root
		$this->manifest = new ThemeManifest($this->base, 'myproject', false, true);
		// New Loader for that root
		$this->loader = new TemplateLoader($this->base);
		$this->loader->addSet('$default', $this->manifest);
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from module
	 */
	public function testFindTemplatesInModule() {
		$this->assertEquals(
			"$this->base/module/templates/Page.ss",
			$this->loader->findTemplate('Page', ['$default'])
		);

		$this->assertEquals(
			"$this->base/module/templates/Layout/Page.ss",
			$this->loader->findTemplate(['type' => 'Layout', 'Page'], ['$default'])
		);
	}

	public function testFindNestedThemeTemplates() {
		// Without including the theme this template cannot be found
		$this->assertEquals(null, $this->loader->findTemplate('NestedThemePage', ['$default']));

		// With a nested theme available then it is available
		$this->assertEquals(
			"{$this->base}/module/themes/subtheme/templates/NestedThemePage.ss",
			$this->loader->findTemplate('NestedThemePage', [
				'silverstripe/module:subtheme',
				'$default'
			])
		);

		// Can also be found if excluding $default theme
		$this->assertEquals(
			"{$this->base}/module/themes/subtheme/templates/NestedThemePage.ss",
			$this->loader->findTemplate('NestedThemePage', [
				'silverstripe/module:subtheme',
			])
		);
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from set theme
	 */
	public function testFindTemplatesInTheme() {
		$this->assertEquals(
			"$this->base/themes/theme/templates/Page.ss",
			$this->loader->findTemplate('Page', ['theme'])
		);

		$this->assertEquals(
			"$this->base/themes/theme/templates/Layout/Page.ss",
			$this->loader->findTemplate(['type' => 'Layout', 'Page'], ['theme'])
		);
	}

	/**
	 * Test that 'main' and 'Layout' templates are loaded from project without a set theme
	 */
	public function testFindTemplatesInApplication() {
		// TODO: replace with one that doesn't create temporary files (so bad)
		$templates = array(
			$this->base . '/myproject/templates/Page.ss',
			$this->base . '/myproject/templates/Layout/Page.ss'
		);
		$this->createTestTemplates($templates);

		$this->assertEquals(
			"$this->base/myproject/templates/Page.ss",
			$this->loader->findTemplate('Page', ['$default'])
		);

		$this->assertEquals(
			"$this->base/myproject/templates/Layout/Page.ss",
			$this->loader->findTemplate(['type' => 'Layout', 'Page'], ['$default'])
		);

		$this->removeTestTemplates($templates);
	}

	/**
	 * Test that 'main' template is found in theme and 'Layout' is found in module
	 */
	public function testFindTemplatesMainThemeLayoutModule() {
		$this->assertEquals(
			"$this->base/themes/theme/templates/CustomThemePage.ss",
			$this->loader->findTemplate('CustomThemePage', ['theme', '$default'])
		);

		$this->assertEquals(
			"$this->base/module/templates/Layout/CustomThemePage.ss",
			$this->loader->findTemplate(['type' => 'Layout', 'CustomThemePage'], ['theme', '$default'])
		);
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
