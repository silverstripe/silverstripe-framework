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
			'[out:TestObject.methodWithOneArgument(one)]
				[out:TestObject.methodWithTwoArguments(one,two)]
				[out:TestMethod(Arg1,Arg2).Bar.Val]
				[out:TestMethod(Arg1,Arg2).Bar]
				[out:TestMethod(Arg1,Arg2)]
				[out:TestMethod(Arg1).Bar.Val]
				[out:TestMethod(Arg1).Bar]
				[out:TestMethod(Arg1)]',
			$this->render('$TestObject.methodWithOneArgument(one)
				$TestObject.methodWithTwoArguments(one,two)
				$TestMethod(Arg1, Arg2).Bar.Val
				$TestMethod(Arg1, Arg2).Bar
				$TestMethod(Arg1, Arg2)
				$TestMethod(Arg1).Bar.Val
				$TestMethod(Arg1).Bar
				$TestMethod(Arg1)')
		);
	}

	function testEscapedArguments() {
		$this->assertEquals(
			'[out:Foo(Arg1,Arg2).Bar.Val].Suffix
				[out:Foo(Arg1,Arg2).Val]_Suffix
				[out:Foo(Arg1,Arg2)]/Suffix
				[out:Foo(Arg1).Bar.Val]textSuffix
				[out:Foo(Arg1).Bar].Suffix
				[out:Foo(Arg1)].Suffix
				[out:Foo.Bar.Val].Suffix
				[out:Foo.Bar].Suffix
				[out:Foo].Suffix',
			$this->render('{$Foo(Arg1, Arg2).Bar.Val}.Suffix
				{$Foo(Arg1, Arg2).Val}_Suffix
				{$Foo(Arg1, Arg2)}/Suffix
				{$Foo(Arg1).Bar.Val}textSuffix
				{$Foo(Arg1).Bar}.Suffix
				{$Foo(Arg1)}.Suffix
				{$Foo.Bar.Val}.Suffix
				{$Foo.Bar}.Suffix
				{$Foo}.Suffix')
		);
	}
	
	function testControlWhitespace() {
		$this->assertEquals(
			'before[out:SingleItem.Test]after
				beforeTestafter',
			$this->render('before<% control SingleItem %>$Test<% end_control %>after
				before<% control SingleItem %>Test<% end_control %>after')
		);

		// The control tags are removed from the output, but no whitespace
		// This is a quirk that could be changed, but included in the test to make the current
		// behaviour explicit
		$this->assertEquals(
			'before

[out:SingleItem.ItemOnItsOwnLine]

after',
			$this->render('before
<% control SingleItem %>
$ItemOnItsOwnLine
<% end_control %>
after')
		);

		// The whitespace within the control tags is preserve in a loop
		// This is a quirk that could be changed, but included in the test to make the current
		// behaviour explicit
		$this->assertEquals(
			'before

[out:Loop3.ItemOnItsOwnLine]

[out:Loop3.ItemOnItsOwnLine]

[out:Loop3.ItemOnItsOwnLine]

after',
			$this->render('before
<% control Loop3 %>
$ItemOnItsOwnLine
<% end_control %>
after')
		);
	}

	function testControls() {
		// Single item controls
		$this->assertEquals(
			'a[out:Foo.Bar.Item]b
				[out:Foo.Bar(Arg1).Item]
				[out:Foo(Arg1).Item]
				[out:Foo(Arg1,Arg2).Item]
				[out:Foo(Arg1,Arg2,Arg3).Item]',
			$this->render('<% control Foo.Bar %>a{$Item}b<% end_control %>
				<% control Foo.Bar(Arg1) %>$Item<% end_control %>
				<% control Foo(Arg1) %>$Item<% end_control %>
				<% control Foo(Arg1, Arg2) %>$Item<% end_control %>
				<% control Foo(Arg1, Arg2, Arg3) %>$Item<% end_control %>')
		);

		// Loop controls
		$this->assertEquals('a[out:Foo.Loop2.Item]ba[out:Foo.Loop2.Item]b',
			$this->render('<% control Foo.Loop2 %>a{$Item}b<% end_control %>'));

		$this->assertEquals('[out:Foo.Loop2(Arg1).Item][out:Foo.Loop2(Arg1).Item]',
			$this->render('<% control Foo.Loop2(Arg1) %>$Item<% end_control %>'));

		$this->assertEquals('[out:Loop2(Arg1).Item][out:Loop2(Arg1).Item]',
			$this->render('<% control Loop2(Arg1) %>$Item<% end_control %>'));

		$this->assertEquals('[out:Loop2(Arg1,Arg2).Item][out:Loop2(Arg1,Arg2).Item]',
			$this->render('<% control Loop2(Arg1, Arg2) %>$Item<% end_control %>'));

		$this->assertEquals('[out:Loop2(Arg1,Arg2,Arg3).Item][out:Loop2(Arg1,Arg2,Arg3).Item]',
			$this->render('<% control Loop2(Arg1, Arg2, Arg3) %>$Item<% end_control %>'));

	}

	function testIfBlocks() {
		// Basic test
		$this->assertEquals('AC',
			$this->render('A<% if NotSet %>B$NotSet<% end_if %>C'));

		// else_if
		$this->assertEquals('ACD',
			$this->render('A<% if NotSet %>B<% else_if IsSet %>C<% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% end_if %>D'));
		$this->assertEquals('ADE',
			$this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% else_if IsSet %>D<% end_if %>E'));

		$this->assertEquals('ADE',
			$this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% else_if IsSet %>D<% end_if %>E'));

		// Dot syntax
		$this->assertEquals('ACD',
			$this->render('A<% if Foo.NotSet %>B<% else_if Foo.IsSet %>C<% end_if %>D'));
		$this->assertEquals('ACD',
			$this->render('A<% if Foo.Bar.NotSet %>B<% else_if Foo.Bar.IsSet %>C<% end_if %>D'));

		// Params
		$this->assertEquals('ACD',
			$this->render('A<% if NotSet(Param) %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ABD',
			$this->render('A<% if IsSet(Param) %>B<% else %>C<% end_if %>D'));

		// Or
		$this->assertEquals('ABD',
			$this->render('A<% if IsSet || NotSet %>B<% else_if A %>C<% end_if %>D'));
		$this->assertEquals('ACD',
			$this->render('A<% if NotSet || AlsoNotSet %>B<% else_if IsSet %>C<% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet || AlsoNotSet %>B<% else_if NotSet3 %>C<% end_if %>D'));
		$this->assertEquals('ACD',
			$this->render('A<% if NotSet || AlsoNotSet %>B<% else_if IsSet || NotSet %>C<% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet || AlsoNotSet %>B<% else_if NotSet2 || NotSet3 %>C<% end_if %>D'));

		// And
		$this->assertEquals('ABD',
			$this->render('A<% if IsSet && AlsoSet %>B<% else_if A %>C<% end_if %>D'));
		$this->assertEquals('ACD',
			$this->render('A<% if IsSet && NotSet %>B<% else_if IsSet %>C<% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet && NotSet2 %>B<% else_if NotSet3 %>C<% end_if %>D'));
		$this->assertEquals('ACD',
			$this->render('A<% if IsSet && NotSet %>B<% else_if IsSet && AlsoSet %>C<% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet && NotSet2 %>B<% else_if IsSet && NotSet3 %>C<% end_if %>D'));

		// Equality
		$this->assertEquals('ABC',
			$this->render('A<% if RawVal == RawVal %>B<% end_if %>C'));
		$this->assertEquals('ACD',
			$this->render('A<% if Right == Wrong %>B<% else_if RawVal == RawVal %>C<% end_if %>D'));
		$this->assertEquals('ABC',
			$this->render('A<% if Right != Wrong %>B<% end_if %>C'));
		$this->assertEquals('AD',
			$this->render('A<% if Right == Wrong %>B<% else_if RawVal != RawVal %>C<% end_if %>D'));

		// Else
		$this->assertEquals('ADE',
			$this->render('A<% if Right == Wrong %>B<% else_if RawVal != RawVal %>C<% else %>D<% end_if %>E'));
	}

	function testBaseTagGeneration() {
		// XHTML wil have a closed base tag
		$tmpl1 = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
		$this->assertRegExp('/<head><base href=".*" \/><\/head>/', $this->render($tmpl1));
			
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
		$this->assertRegExp('/<head><base href=".*" \/><\/head>/', $response->getBody());
	}

	
	function testRecursiveInclude() {
		$view = new SSViewer(array('SSViewerTestRecursiveInclude'));
		
		$data = new ArrayData(array(
			'Title' => 'A',
			'Children' => new DataObjectSet(array(
				new ArrayData(array(
					'Title' => 'A1',
					'Children' => new DataObjectSet(array(
						new ArrayData(array( 'Title' => 'A1 i', )),
						new ArrayData(array( 'Title' => 'A1 ii', )),
					)),
				)),
				new ArrayData(array( 'Title' => 'A2', )),
				new ArrayData(array( 'Title' => 'A3', )),
			)),
		));
		
		$result = $view->process($data);
		// We don't care about whitespace
		$rationalisedResult = trim(preg_replace('/\s+/', ' ', $result));
		
		$this->assertEquals('A A1 A1 i A1 ii A2 A3', $rationalisedResult);
	}
}

/**
 * A test fixture that will echo back the template item
 */
class SSViewerTestFixture extends ViewableData {
	protected $name;

	function __construct($name = null) {
		$this->name = $name;
		parent::__construct();
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

class SSViewerTest_ViewableData extends ViewableData implements TestOnly {
	function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}
	
	function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}
