<?php

use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ThemeManifest;

/**
 * Tests for the {@link TemplateLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class ThemeResourceLoaderTest extends SapphireTest {

	private $base;

	/**
	 * @var ThemeManifest
	 */
	private $manifest;

	/**
	 * @var ThemeResourceLoader
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
		$this->loader = new ThemeResourceLoader($this->base);
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

	public function testFindThemedCSS() {
		$this->assertEquals(
			"myproject/css/project.css",
			$this->loader->findThemedCSS('project', ['$default', 'theme'])
		);
		$this->assertEquals(
			"themes/theme/css/project.css",
			$this->loader->findThemedCSS('project', ['theme', '$default'])
		);
		$this->assertEmpty(
			$this->loader->findThemedCSS('nofile', ['theme', '$default'])
		);
		$this->assertEquals(
			'module/css/content.css',
			$this->loader->findThemedCSS('content', ['/module', 'theme'])
		);
		$this->assertEquals(
			'module/css/content.css',
			$this->loader->findThemedCSS('content', ['/module', 'theme', '$default'])
		);
		$this->assertEquals(
			'module/css/content.css',
			$this->loader->findThemedCSS('content', ['$default', '/module', 'theme'])
		);
	}

	public function testFindThemedJavascript() {
		$this->assertEquals(
			"myproject/javascript/project.js",
			$this->loader->findThemedJavascript('project', ['$default', 'theme'])
		);
		$this->assertEquals(
			"themes/theme/javascript/project.js",
			$this->loader->findThemedJavascript('project', ['theme', '$default'])
		);
		$this->assertEmpty(
			$this->loader->findThemedJavascript('nofile', ['theme', '$default'])
		);
		$this->assertEquals(
			'module/javascript/content.js',
			$this->loader->findThemedJavascript('content', ['/module', 'theme'])
		);
		$this->assertEquals(
			'module/javascript/content.js',
			$this->loader->findThemedJavascript('content', ['/module', 'theme', '$default'])
		);
		$this->assertEquals(
			'module/javascript/content.js',
			$this->loader->findThemedJavascript('content', ['$default', '/module', 'theme'])
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
