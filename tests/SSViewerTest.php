<?php

class SSViewerTest extends SapphireTest {
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
	
	
	/**
	 * Small helper to render templates from strings
	 */
	function render($templateString, $data = null) {
		$t = SSViewer::fromString($templateString);
		if(!$data) $data = new SSViewerTestFixture();
		return $t->process($data);
	}
	
	function testRequirements() {
		$requirements = $this->getMock("Requirements_Backend", array("javascript", "css"));
		$jsFile = 'sapphire/tests/forms/a.js';
		$cssFile = 'sapphire/tests/forms/a.js';
		
		$requirements->expects($this->once())->method('javascript')->with($jsFile);
		$requirements->expects($this->once())->method('css')->with($cssFile);
		
		Requirements::set_backend($requirements);
		
		$template = $this->render("<% require javascript($jsFile) %>
		<% require css($cssFile) %>");

		$this->assertFalse((bool)trim($template), "Should be no content in this return.");
	}
	
	function testComments() {
		$output = $this->render(<<<SS
This is my template<%-- this is a comment --%>This is some content<%-- this is another comment --%>This is the final content
SS
);
		
		$this->assertEquals("This is my templateThis is some contentThis is the final content", preg_replace("/\n?<!--.*-->\n?/U",'',$output));
	}
	
	function testObjectDotArguments() {
		$this->assertEquals(
			$this->render("\$TestObject.methodWithOneArgument(one)"),
			"TestObject.methodWithOneArgument(one)",
			"Object method calls in dot notation work with one argument"
		);
		
		// two arguments
		$this->assertEquals(
			$this->render("\$TestObject.methodWithTwoArguments(one,two)"),
			"TestObject.methodWithTwoArguments(one,two)",
			"Object method calls in dot notation work with two arguments"
		);
	}
	
	function testBaseTagGeneration() {
		// XHTML wil have a closed base tag
		$tmpl1 = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
		$this->assertRegExp('/<head><base href=".*"><\/base><\/head>/', $this->render($tmpl1));
			
		// HTML4 and 5 will only have it for IE
		$tmpl2 = '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $this->render($tmpl2));
			
			
		$tmpl3 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $this->render($tmpl3));

		// Check that the content negotiator converts to the equally legal formats
		$negotiator = new ContentNegotiator();
		
		$response = new SS_HTTPResponse($this->render($tmpl1));
		$negotiator->html($response);
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/', $response->getBody());

		$response = new SS_HTTPResponse($this->render($tmpl1));
		$negotiator->xhtml($response);
		$this->assertRegExp('/<head><base href=".*"><\/base><\/head>/', $response->getBody());
	}
}

/**
 * A test fixture that will echo back the template item
 */
class SSViewerTestFixture extends ViewableData {
	protected $name;

	function __construct($name = null) {
		$this->name = $name;
	}
	

	private function argedName($fieldName, $arguments) {
		$childName = $this->name ? "$this->name.$fieldName" : $fieldName;
		if($arguments) return $childName . '(' . implode(',', $arguments) . ')';
		else return $childName;
	}
	function obj($fieldName, $arguments=null, $forceReturnedObject=true, $cache=false, $cacheName=null) {
		$childName = $this->argedName($fieldName, $arguments);

		// Special field name Loop### to create a list
		if(preg_match('/^Loop([0-9]+)$/', $fieldName, $matches)) {
			$output = new DataObjectSet();
			for($i=0;$i<$matches[1];$i++) $output->push(new SSViewerTestFixture($childName));
			return $output;

		} else if(preg_match('/NotSet/i', $fieldName)) {
			return new ViewableData();

		} else {
			return new SSViewerTestFixture($childName);
		}
	}
	

	function XML_val($fieldName, $arguments = null) {
		if(preg_match('/NotSet/i', $fieldName)) {
			return '';
		} else if(preg_match('/Raw/i', $fieldName)) {
			return $fieldName;
		} else {
			return '[out:' . $this->argedName($fieldName, $arguments) . ']';
		}
	}

	function hasValue($fieldName, $arguments = null) {
		return (bool)$this->XML_val($fieldName, $arguments);
	}
}
/*
class SSViewerTest_ViewableData extends ViewableData implements TestOnly {
	function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}
	
	function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}
*/