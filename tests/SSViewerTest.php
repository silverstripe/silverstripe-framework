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
}

class SSViewerTest_ViewableData extends ViewableData implements TestOnly {
	function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}
	
	function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}