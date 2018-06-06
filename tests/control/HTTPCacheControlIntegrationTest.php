<?php

class HTTPCacheControlIntegrationTest extends FunctionalTest {

	public function setUp() {
		parent::setUp();
		Config::inst()->remove('HTTP', 'disable_http_cache');
		Injector::inst()->unregisterNamedObject('HTTPCacheControl');
	}

	public function testFormCSRF() {
		// CSRF sets caching to disabled
		$response = $this->get('HTTPCacheControlIntegrationTest_SessionController/showform');
		$header = $response->getHeader('Cache-Control');
		$this->assertContains('no-cache', $header);
		$this->assertContains('no-store', $header);
		$this->assertContains('must-revalidate', $header);
	}

	public function testPublicForm() {
		// Public forms (http get) allow public caching
		$response = $this->get('HTTPCacheControlIntegrationTest_SessionController/showpublicform');
		$header = $response->getHeader('Cache-Control');
		$this->assertContains('public', $header);
		$this->assertContains('must-revalidate', $header);
		$this->assertNotContains('no-cache', $response->getHeader('Cache-Control'));
		$this->assertNotContains('no-store', $response->getHeader('Cache-Control'));
	}

	public function testPrivateActionsError()
	{
		// disallowed private actions don't cache
		$response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
		$header = $response->getHeader('Cache-Control');
		$this->assertContains('no-cache', $header);
		$this->assertContains('no-store', $header);
		$this->assertContains('must-revalidate', $header);
	}

	public function testPrivateActionsAuthenticated()
	{
		$this->logInWithPermission('ADMIN');
		// Authenticated actions are private cache
		$response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
		$header = $response->getHeader('Cache-Control');
		$this->assertContains('private', $header);
		$this->assertContains('must-revalidate', $header);
		$this->assertNotContains('no-cache', $response->getHeader('Cache-Control'));
		$this->assertNotContains('no-store', $response->getHeader('Cache-Control'));
	}
}

/**
 * Test caching based on session
 */
class HTTPCacheControlIntegrationTest_SessionController extends Controller implements TestOnly
{
	private static $allowed_actions = array(
		'showform',
		'privateaction',
		'publicaction',
		'showpublicform',
		'Form',
	);

	public function init()
	{
		parent::init();
		// Prefer public by default
		HTTPCacheControl::singleton()->publicCache();
	}

	public function getContent()
	{
		return '<p>Hello world</p>';
	}

	public function showform()
	{
		// Form should be set to private due to CSRF
		SecurityToken::enable();
		return $this->renderWith('BlankPage');
	}

	public function showpublicform()
	{
		// Public form doesn't use CSRF and thus no session usage
		SecurityToken::disable();
		return $this->renderWith('BlankPage');
	}

	public function privateaction()
	{
		if (!Permission::check('ANYCODE')) {
			$this->httpError(403, 'Not allowed');
		}
		return 'ok';
	}

	public function publicaction()
	{
		return 'Hello!';
	}

	public function Form()
	{
		$form = new Form(
			$this,
			'Form',
			new FieldList(new TextField('Name')),
			new FieldList(new FormAction('submit', 'Submit'))
		);
		$form->setFormMethod('GET');
		return $form;
	}
}

/**
 * Test caching based on specific http caching directives
 */
class HTTPCacheControlIntegrationTest_RuleController extends Controller implements TestOnly
{
	private static $allowed_actions = array(
		'privateaction',
		'publicaction',
		'disabledaction',
	);

	public function init()
	{
		parent::init();
		// Prefer public by default
		HTTPCacheControl::singleton()->publicCache();
	}

	public function privateaction() {
		HTTPCacheControl::singleton()->privateCache();
		return 'private content';
	}

	private function publicaction() {
		HTTPCacheControl::singleton()->publicCache();
		return 'public content';
	}

	private function disabledaction() {
		HTTPCacheControl::singleton()->disableCache();
		return 'uncached content';
	}
}
