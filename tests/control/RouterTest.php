<?php

use SilverStripe\Framework\Control\Router;

/**
 * Tests for the {@link SilverStripe\Framework\Control\Router} class.
 */
class RouterTest extends SapphireTest {

	public function testRouting() {
		$router = new Router();
		$router->setRules(array('GET ' => 'get', 'POST ' => 'post'));

		$get = new SS_HTTPRequest('GET', '');
		$post = new SS_HTTPRequest('POST', '');
		$del = new SS_HTTPRequest('DELETE', '');

		$this->assertEquals('get', $router->route($get));
		$this->assertEquals('post', $router->route($post));
		$this->assertFalse($router->route($del));
	}

	public function testRootController() {
		$router = new Router();
		$router->setRules(array('' => 'root'));

		$root = new SS_HTTPRequest('GET', '/');
		$page = new SS_HTTPRequest('POST', '/page');

		$this->assertEquals('root', $router->route($root));
		$this->assertFalse($router->route($page));
	}

	public function testParams() {
		$router = new Router();
		$router->setRules(array('$Action//$ID!' => '$Action'));

		$meth = new SS_HTTPRequest('GET', 'method');
		$id = new SS_HTTPRequest('GET', 'method/1');

		$this->assertFalse($router->route($meth));
		$this->assertEquals('$Action', $router->route($id));

		$this->assertTrue($id->isAllRouted());
		$this->assertEquals(1, $id->getUnshiftedButParsed());
		$this->assertEquals('1', $id->getParam('ID'));
	}

	public function testRepeatRouting() {
		$router = new Router();
		$request = new SS_HTTPRequest('GET', 'page/Form/field/Name/action');
		$router->setRequest($request);

		$this->assertEquals('$Action', $router->route(null, array(
			'$URLSegment/$Action//$ID/$OtherID' => '$Action'
		)));
		$this->assertEquals(
			array(
				'URLSegment' => 'page',
				'Action' => 'Form',
				'ID' => 'field',
				'OtherID' => 'Name'
			),
			$request->getParams()
		);
		$this->assertEquals(2, $request->getUnshiftedButParsed());
		$this->assertEquals('field/Name/action', $request->getRemainingUrl());

		$this->assertEquals('handleField', $router->route(null, array(
			'field/$Name' => 'handleField'
		)));
		$this->assertEquals(
			array(
				'URLSegment' => 'page',
				'Action' => 'Form',
				'ID' => 'field',
				'OtherID' => 'Name',
				'Name' => 'Name'
			),
			$request->getParams()
		);
		$this->assertEquals('action', $request->getRemainingUrl());

		$this->assertEquals('get', $router->route(null, array(
			'POST action' => 'post',
			'GET action' => 'get'
		)));
		$this->assertTrue($request->isAllRouted());
	}

	public function testRouteParams() {
		$router = new Router();
		$request = new SS_HTTPRequest('GET', 'en/page');

		$router->route($request, array(
			'en/$URLSegment' => array(
				'Language' => 'en_US'
			)
		));

		$this->assertEquals('en_US', $request->getRouteParam('Language'));
		$this->assertEquals('en_US', $request->getParam('Language'));
		$this->assertEquals('page', $request->getParam('URLSegment'));

		$request = new SS_HTTPRequest('GET', 'lang/en_AU/page');
		$router->route($request, array(
			'lang/$Language/$URLSegment' => array(
				'Language' => 'en_US'
			)
		));

		$this->assertEquals('en_US', $request->getRouteParam('Language'));
		$this->assertEquals(
			'en_AU', $request->getParam('Language'), 'Matched parameters override route parameters'
		);
		$this->assertEquals('page', $request->getParam('URLSegment'));
	}

}