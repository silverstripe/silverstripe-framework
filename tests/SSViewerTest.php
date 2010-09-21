<?php

class SSViewerTest extends SapphireTest {
	
	/**
	 * Tests for {@link SSViewer::current_theme()} for different behaviour
	 * of user defined themes via {@link SiteConfig} and default theme
	 * when no user themes are defined.
	 */
	function testCurrentTheme() {
		$config = SiteConfig::current_site_config();
		$oldTheme = $config->Theme;
		$config->Theme = '';
		$config->write();
		
		SSViewer::set_theme('mytheme');
		$this->assertEquals('mytheme', SSViewer::current_theme(), 'Current theme is the default - user has not defined one');

		$config->Theme = 'myusertheme';
		$config->write();

		// Pretent to load the page
		$c = new ContentController();
		$c->handleRequest(new SS_HTTPRequest('GET', '/'));

		$this->assertEquals('myusertheme', SSViewer::current_theme(), 'Current theme is a user defined one');

		// Set the theme back to the original
		$config->Theme = $oldTheme;
		$config->write();
	}
	
	/**
	 * Test that a template without a <head> tag still renders.
	 */
	function testTemplateWithoutHeadRenders() {
		$data = new ArrayData(array(
			'Var' => 'var value'
		));
		
		$result = $data->renderWith("SSViewerTestPartialTemplate");
		$this->assertEquals('Test partial template: var value', trim(preg_replace("/<!--.*-->/U",'',$result)));
	}
	
	function testRequirements() {
		$requirements = $this->getMock("Requirements_Backend", array("javascript", "css"));
		$jsFile = 'sapphire/tests/forms/a.js';
		$cssFile = 'sapphire/tests/forms/a.js';
		
		$requirements->expects($this->once())->method('javascript')->with($jsFile);
		$requirements->expects($this->once())->method('css')->with($cssFile);
		
		Requirements::set_backend($requirements);
		
		$data = new ArrayData(array());
		
		$viewer = SSViewer::fromString(<<<SS
		<% require javascript($jsFile) %>
		<% require css($cssFile) %>
SS
);
		$template = $viewer->process($data);
		$this->assertFalse((bool)trim($template), "Should be no content in this return.");
	}
	
	function testComments() {
		$viewer = SSViewer::fromString(<<<SS
This is my template<%-- this is a comment --%>This is some content<%-- this is another comment --%>This is the final content
SS
);
		$output = $viewer->process(new ArrayData(array()));
		
		$this->assertEquals("This is my templateThis is some contentThis is the final content", preg_replace("/\n?<!--.*-->\n?/U",'',$output));
	}
	
	function testObjectDotArguments() {
		// one argument
		$viewer = SSViewer::fromString(<<<SS
\$TestObject.methodWithOneArgument(one)
SS
);
		$obj = new SSViewerTest_ViewableData();
		$this->assertEquals(
			$viewer->process(new ArrayData(array('TestObject'=>$obj))),
			"arg1:one",
			"Object method calls in dot notation work with one argument"
		);
		
		// two arguments
		$viewer = SSViewer::fromString(<<<SS
\$TestObject.methodWithTwoArguments(one,two)
SS
);
		$obj = new SSViewerTest_ViewableData();
		$this->assertEquals(
			$viewer->process(new ArrayData(array('TestObject'=>$obj))),
			"arg1:one,arg2:two",
			"Object method calls in dot notation work with two arguments"
		);
	}
	
	function testBaseTagGeneration() {
		// XHTML wil have a closed base tag
		$tmpl1 = SSViewer::fromString('<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>');
		$this->assertRegExp('/<head><base href=".*" \/><\/head>/', $tmpl1->process(new ViewableData()));
			
		// HTML4 and 5 will only have it for IE
		$tmpl2 = SSViewer::fromString('<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>');
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $tmpl2->process(new ViewableData()));
			
			
		$tmpl3 = SSViewer::fromString('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>');
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $tmpl3->process(new ViewableData()));

		// Check that the content negotiator converts to the equally legal formats
		$negotiator = new ContentNegotiator();
		
		$response = new SS_HTTPResponse($tmpl1->process(new ViewableData()));
		$negotiator->html($response);
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $response->getBody());

		$response = new SS_HTTPResponse($tmpl1->process(new ViewableData()));
		$negotiator->xhtml($response);
		$this->assertRegExp('/<head><base href=".*" \/><\/head>/', $response->getBody());
	}
}

class SSViewerTest_ViewableData extends ViewableData implements TestOnly {
	function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}
	
	function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}