<?php
/**
 * Tests for the {@link SS_TemplateLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateLoaderTest extends SapphireTest {

	public function testFindTemplates() {
		$base     = dirname(__FILE__) . '/fixtures/templatemanifest';
		$manifest = new SS_TemplateManifest($base, false, true);
		$loader   = new SS_TemplateLoader();

		$manifest->regenerate(false);
		$loader->pushManifest($manifest);

		$expectPage = array(
			'main'   => "$base/module/templates/Page.ss",
			'Layout' => "$base/module/templates/Layout/Page.ss"
		);
		$expectPageThemed = array(
			'main'   => "$base/themes/theme/templates/Page.ss",
			'Layout' => "$base/themes/theme/templates/Layout/Page.ss"
		);

		$this->assertEquals($expectPage, $loader->findTemplates('Page'));
		$this->assertEquals($expectPage, $loader->findTemplates(array('Foo', 'Page')));
		$this->assertEquals($expectPage, $loader->findTemplates('PAGE'));
		$this->assertEquals($expectPageThemed, $loader->findTemplates('Page', 'theme'));

		$expectPageLayout       = array('main' => "$base/module/templates/Layout/Page.ss");
		$expectPageLayoutThemed = array('main' => "$base/themes/theme/templates/Layout/Page.ss");

		$this->assertEquals($expectPageLayout, $loader->findTemplates('Layout/Page'));
		$this->assertEquals($expectPageLayout, $loader->findTemplates('Layout/PAGE'));
		$this->assertEquals($expectPageLayoutThemed, $loader->findTemplates('Layout/Page', 'theme'));

		$expectCustomPage = array(
			'main'   => "$base/module/templates/Page.ss",
			'Layout' => "$base/module/templates/Layout/CustomPage.ss"
		);
		$this->assertEquals($expectCustomPage, $loader->findTemplates(array('CustomPage', 'Page')));
	}

}
