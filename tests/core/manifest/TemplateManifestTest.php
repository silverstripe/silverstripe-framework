<?php
/**
 * Tests for the template manifest.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateManifestTest extends SapphireTest {

	protected $base;
	protected $manifest;
	protected $manifestTests;

	public function setUp() {
		parent::setUp();

		$this->base = dirname(__FILE__) . '/fixtures/templatemanifest';
		$this->manifest      = new SS_TemplateManifest($this->base, 'myproject');
		$this->manifestTests = new SS_TemplateManifest($this->base, 'myproject', true);

		$this->manifest->regenerate(false);
		$this->manifestTests->regenerate(false);
	}

	public function testGetTemplates() {
		$expect = array(
			'root' => array(
				'module' => "{$this->base}/module/Root.ss"
			),
			'page' => array(
				'main'   => "{$this->base}/module/templates/Page.ss",
				'Layout' => "{$this->base}/module/templates/Layout/Page.ss",
				'themes' => array('theme' => array(
					'main'   => "{$this->base}/themes/theme/templates/Page.ss",
					'Layout' => "{$this->base}/themes/theme/templates/Layout/Page.ss"
				))
			),
			'custompage' => array(
				'Layout' => "{$this->base}/module/templates/Layout/CustomPage.ss"
			),
			'customtemplate' => array(
				'main' => "{$this->base}/module/templates/CustomTemplate.ss",
				'myproject' => array(
					'main' => "{$this->base}/myproject/templates/CustomTemplate.ss"
				)
			),
			'subfolder' => array(
				'main' => "{$this->base}/module/subfolder/templates/Subfolder.ss"
			),
			'customthemepage' => array (
				'Layout' => "{$this->base}/module/templates/Layout/CustomThemePage.ss",
				'themes' =>
				array(
					'theme' => array('main' => "{$this->base}/themes/theme/templates/CustomThemePage.ss",)
				)
			),
			'include' => array('themes' => array(
				'theme' => array(
					'Includes' => "{$this->base}/themes/theme/templates/Includes/Include.ss"
				)
			))
		);

		$expectTests = $expect;
		$expectTests['test'] = array(
			'main' => "{$this->base}/module/tests/templates/Test.ss"
		);

		$manifest      = $this->manifest->getTemplates();
		$manifestTests = $this->manifestTests->getTemplates();

		ksort($expect);
		ksort($expectTests);
		ksort($manifest);
		ksort($manifestTests);

		$this->assertEquals(
			$expect, $manifest,
			'All templates are correctly loaded in the manifest.'
		);

		$this->assertEquals(
			$expectTests, $manifestTests,
			'The test manifest is the same, but includes test templates.'
		);
	}

	public function testGetTemplate() {
		$expectPage = array(
			'main'   => "{$this->base}/module/templates/Page.ss",
			'Layout' => "{$this->base}/module/templates/Layout/Page.ss",
			'themes' => array('theme' => array(
				'main'   => "{$this->base}/themes/theme/templates/Page.ss",
				'Layout' => "{$this->base}/themes/theme/templates/Layout/Page.ss"
			))
		);

		$expectTests = array(
			'main' => "{$this->base}/module/tests/templates/Test.ss"
		);

		$this->assertEquals($expectPage, $this->manifest->getTemplate('Page'));
		$this->assertEquals($expectPage, $this->manifest->getTemplate('PAGE'));
		$this->assertEquals($expectPage, $this->manifestTests->getTemplate('Page'));
		$this->assertEquals($expectPage, $this->manifestTests->getTemplate('PAGE'));

		$this->assertEquals(array(), $this->manifest->getTemplate('Test'));
		$this->assertEquals($expectTests, $this->manifestTests->getTemplate('Test'));

		$this->assertEquals(array(
			'main' => "{$this->base}/module/templates/CustomTemplate.ss",
			'myproject' => array(
				'main' => "{$this->base}/myproject/templates/CustomTemplate.ss"
		)), $this->manifestTests->getTemplate('CustomTemplate'));
	}

}
