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
		$expect = [
			'root' => [
				'module' => "{$this->base}/module/Root.ss"
			],
			'page' => [
				'main'   => "{$this->base}/module/templates/Page.ss",
				'Layout' => "{$this->base}/module/templates/Layout/Page.ss",
				'themes' => ['theme' => [
					'main'   => "{$this->base}/themes/theme/templates/Page.ss",
					'Layout' => "{$this->base}/themes/theme/templates/Layout/Page.ss"
				]]
			],
			'custompage' => [
				'Layout' => "{$this->base}/module/templates/Layout/CustomPage.ss"
			],
			'customtemplate' => [
				'main' => "{$this->base}/module/templates/CustomTemplate.ss",
				'myproject' => [
					'main' => "{$this->base}/myproject/templates/CustomTemplate.ss"
				]
			],
			'subfolder' => [
				'main' => "{$this->base}/module/subfolder/templates/Subfolder.ss"
			],
			'customthemepage' => [
				'Layout' => "{$this->base}/module/templates/Layout/CustomThemePage.ss",
				'themes' =>
				[
					'theme' => ['main' => "{$this->base}/themes/theme/templates/CustomThemePage.ss",]
				]
			],
			'include' => ['themes' => [
				'theme' => [
					'Includes' => "{$this->base}/themes/theme/templates/Includes/Include.ss"
				]
			]]
		];

		$expectTests = $expect;
		$expectTests['test'] = [
			'main' => "{$this->base}/module/tests/templates/Test.ss"
		];

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
		$expectPage = [
			'main'   => "{$this->base}/module/templates/Page.ss",
			'Layout' => "{$this->base}/module/templates/Layout/Page.ss",
			'themes' => ['theme' => [
				'main'   => "{$this->base}/themes/theme/templates/Page.ss",
				'Layout' => "{$this->base}/themes/theme/templates/Layout/Page.ss"
			]]
		];

		$expectTests = [
			'main' => "{$this->base}/module/tests/templates/Test.ss"
		];

		$this->assertEquals($expectPage, $this->manifest->getTemplate('Page'));
		$this->assertEquals($expectPage, $this->manifest->getTemplate('PAGE'));
		$this->assertEquals($expectPage, $this->manifestTests->getTemplate('Page'));
		$this->assertEquals($expectPage, $this->manifestTests->getTemplate('PAGE'));

		$this->assertEquals([], $this->manifest->getTemplate('Test'));
		$this->assertEquals($expectTests, $this->manifestTests->getTemplate('Test'));

		$this->assertEquals([
			'main' => "{$this->base}/module/templates/CustomTemplate.ss",
			'myproject' => [
				'main' => "{$this->base}/myproject/templates/CustomTemplate.ss"
		]], $this->manifestTests->getTemplate('CustomTemplate'));
	}

}
