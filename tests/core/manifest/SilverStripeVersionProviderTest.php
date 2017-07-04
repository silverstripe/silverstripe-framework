<?php

class SilverStripeVersionProviderTest extends SapphireTest
{
	/**
	 * @var SilverStripeVersionProvider
	 */
	protected $provider;

	public function setUp()
	{
		parent::setUp();
		$this->provider = new SilverStripeVersionProvider;
	}

	public function testGetModules()
	{
		Config::inst()->update('SilverStripeVersionProvider', 'modules', array(
			'silverstripe/somepackage' => 'Some Package',
			'silverstripe/hidden' => '',
			'silverstripe/another' => 'Another'
		));

		$result = $this->provider->getModules();
		$this->assertArrayHasKey('silverstripe/somepackage', $result);
		$this->assertSame('Some Package', $result['silverstripe/somepackage']);
		$this->assertArrayHasKey('silverstripe/another', $result);
		$this->assertArrayNotHasKey('silverstripe/hidden', $result);
	}

	public function testGetModuleVersionFromComposer()
	{
		Config::inst()->update('SilverStripeVersionProvider', 'modules', array(
			'silverstripe/framework' => 'Framework',
			'silverstripe/siteconfig' => 'SiteConfig'
		));

		$result = $this->provider->getModules(array('silverstripe/framework'));
		$this->assertArrayHasKey('silverstripe/framework', $result);
		$this->assertNotEmpty($result['silverstripe/framework']);
	}

	public function testGetVersion()
	{
		Config::inst()->update('SilverStripeVersionProvider', 'modules', array(
			'silverstripe/framework' => 'Framework',
			'silverstripe/siteconfig' => 'SiteConfig'
		));

		$result = $this->provider->getVersion();
		$this->assertContains('SiteConfig: ', $result);
		$this->assertContains('Framework: ', $result);
		$this->assertContains(', ', $result);
	}
}
