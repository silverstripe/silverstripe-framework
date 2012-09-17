<?php

class SSViewerTest extends SapphireTest {
	function setUp() {
		parent::setUp();
		SSViewer::set_source_file_comments(false);
	}
	
	/**
	 * Tests for {@link SSViewer::current_theme()} for different behaviour
	 * of user defined themes via {@link SiteConfig} and default theme
	 * when no user themes are defined.
	 */
	function testCurrentTheme() {
		//TODO: SiteConfig moved to CMS 
		SSViewer::set_theme('mytheme');
		$this->assertEquals('mytheme', SSViewer::current_theme(), 'Current theme is the default - user has not defined one');
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
		$jsFile = FRAMEWORK_DIR . '/tests/forms/a.js';
		$cssFile = FRAMEWORK_DIR . '/tests/forms/a.js';
		
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
	
	function testBasicText() {
		$this->assertEquals('"', $this->render('"'), 'Double-quotes are left alone');
		$this->assertEquals("'", $this->render("'"), 'Single-quotes are left alone');
		$this->assertEquals('A', $this->render('\\A'), 'Escaped characters are unescaped');
		$this->assertEquals('\\A', $this->render('\\\\A'), 'Escaped back-slashed are correctly unescaped');
	}
	
	function testBasicInjection() {
		$this->assertEquals('[out:Test]', $this->render('$Test'), 'Basic stand-alone injection');
		$this->assertEquals('[out:Test]', $this->render('{$Test}'), 'Basic stand-alone wrapped injection');
		$this->assertEquals('A[out:Test]!', $this->render('A$Test!'), 'Basic surrounded injection');
		$this->assertEquals('A[out:Test]B', $this->render('A{$Test}B'), 'Basic surrounded wrapped injection');
		
		$this->assertEquals('A$B', $this->render('A\\$B'), 'No injection as $ escaped');
		$this->assertEquals('A$ B', $this->render('A$ B'), 'No injection as $ not followed by word character');
		$this->assertEquals('A{$ B', $this->render('A{$ B'), 'No injection as {$ not followed by word character');
		
		$this->assertEquals('{$Test}', $this->render('{\\$Test}'), 'Escapes can be used to avoid injection');
		$this->assertEquals('{\\[out:Test]}', $this->render('{\\\\$Test}'), 'Escapes before injections are correctly unescaped');
	}


	function testGlobalVariableCalls() {
		$this->assertEquals('automatic', $this->render('$SSViewerTest_GlobalAutomatic'));
		$this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedByString'));
		$this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedInArray'));
	}

	function testGlobalVariableCallsWithArguments() {
		$this->assertEquals('zz', $this->render('$SSViewerTest_GlobalThatTakesArguments'));
		$this->assertEquals('zFooz', $this->render('$SSViewerTest_GlobalThatTakesArguments("Foo")'));
		$this->assertEquals('zFoo:Bar:Bazz', $this->render('$SSViewerTest_GlobalThatTakesArguments("Foo", "Bar", "Baz")'));
		$this->assertEquals('zreferencez', $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalReferencedByString)'));
	}

	function testGlobalVariablesAreEscaped() {
		$this->assertEquals('<div></div>', $this->render('$SSViewerTest_GlobalHTMLFragment'));
		$this->assertEquals('&lt;div&gt;&lt;/div&gt;', $this->render('$SSViewerTest_GlobalHTMLEscaped'));

		$this->assertEquals('z<div></div>z', $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLFragment)'));
		$this->assertEquals('z&lt;div&gt;&lt;/div&gt;z', $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLEscaped)'));
	}

	function testCoreGlobalVariableCalls() {
		$this->assertEquals(Director::absoluteBaseURL(), $this->render('{$absoluteBaseURL}'), 'Director::absoluteBaseURL can be called from within template');
		$this->assertEquals(Director::absoluteBaseURL(), $this->render('{$AbsoluteBaseURL}'), 'Upper-case %AbsoluteBaseURL can be called from within template');

		$this->assertEquals(Director::is_ajax(), $this->render('{$isAjax}'), 'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$IsAjax}'), 'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$is_ajax}'), 'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$Is_ajax}'), 'All variations of is_ajax result in the correct call');

		$this->assertEquals(i18n::get_locale(), $this->render('{$i18nLocale}'), 'i18n template functions result correct result');
		$this->assertEquals(i18n::get_locale(), $this->render('{$get_locale}'), 'i18n template functions result correct result');

		$this->assertEquals((string)Member::currentUser(), $this->render('{$CurrentMember}'), 'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$CurrentUser}'), 'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$currentMember}'), 'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$currentUser}'), 'Member template functions result correct result');

		$this->assertEquals(SecurityToken::getSecurityID(), $this->render('{$getSecurityID}'), 'SecurityToken template functions result correct result');
		$this->assertEquals(SecurityToken::getSecurityID(), $this->render('{$SecurityID}'), 'SecurityToken template functions result correct result');

		$this->assertEquals(Permission::check("ADMIN"), (bool)$this->render('{$HasPerm(\'ADMIN\')}'), 'Permissions template functions result correct result');
		$this->assertEquals(Permission::check("ADMIN"), (bool)$this->render('{$hasPerm(\'ADMIN\')}'), 'Permissions template functions result correct result');
	}

	function testLocalFunctionsTakePriorityOverGlobals() {
		$data = new ArrayData(array(
			'Page' => new SSViewerTest_Object()
		));

		//call method with lots of arguments
		$result = $this->render('<% with Page %>$lotsOfArguments11("a","b","c","d","e","f","g","h","i","j","k")<% end_with %>',$data);
		$this->assertEquals("abcdefghijk",$result, "Function can accept up to 11 arguments");

		//call method that does not exist
		$result = $this->render('<% with Page %><% if IDoNotExist %>hello<% end_if %><% end_with %>',$data);
		$this->assertEquals("",$result, "Method does not exist - empty result");

		//call if that does not exist
		$result = $this->render('<% with Page %>$IDoNotExist("hello")<% end_with %>',$data);
		$this->assertEquals("",$result, "Method does not exist - empty result");

		//call method with same name as a global method (local call should take priority)
		$result = $this->render('<% with Page %>$absoluteBaseURL<% end_with %>',$data);
		$this->assertEquals("testLocalFunctionPriorityCalled",$result, "Local Object's function called. Did not return the actual baseURL of the current site");
	}

	function testCurrentScopeLoopWith() {
		// Data to run the loop tests on - one sequence of three items, each with a subitem
		$data = new ArrayData(array(
			'Foo' => new ArrayList(array(
				'Subocean' => new ArrayData(array(
						'Name' => 'Higher'
					)),
				new ArrayData(array(
					'Sub' => new ArrayData(array(
						'Name' => 'SubKid1'
					))
				)),
				new ArrayData(array(
					'Sub' => new ArrayData(array(
						'Name' => 'SubKid2'
					))
				)),
				new SSViewerTest_Object('Number6')
			))
		));

		$result = $this->render('<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop works");

		$result = $this->render('<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop works");

		$result = $this->render('<% with Foo %>$Count<% end_with %>',$data);
		$this->assertEquals("4",$result, "4 items in the DataObjectSet");

		$result = $this->render('<% with Foo %><% loop Up.Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %><% end_with %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop in with Up.Foo scope works");

		$result = $this->render('<% with Foo %><% loop %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %><% end_with %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop in current scope works");
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

		// Nested test
		$this->assertEquals('AB1C',
			$this->render('A<% if IsSet %>B$NotSet<% if IsSet %>1<% else %>2<% end_if %><% end_if %>C'));

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

		// Negation
		$this->assertEquals('AC',
			$this->render('A<% if not IsSet %>B<% end_if %>C'));
		$this->assertEquals('ABC',
			$this->render('A<% if not NotSet %>B<% end_if %>C'));
		
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
		
		// Negated Or
		$this->assertEquals('ACD',
			$this->render('A<% if not IsSet || AlsoNotSet %>B<% else_if A %>C<% end_if %>D'));
		$this->assertEquals('ABD',
			$this->render('A<% if not NotSet || AlsoNotSet %>B<% else_if A %>C<% end_if %>D'));
		$this->assertEquals('ABD',
			$this->render('A<% if NotSet || not AlsoNotSet %>B<% else_if A %>C<% end_if %>D'));

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

		// Bare words with ending space
		$this->assertEquals('ABC',
			$this->render('A<% if "RawVal" == RawVal %>B<% end_if %>C'));

		// Else
		$this->assertEquals('ADE',
			$this->render('A<% if Right == Wrong %>B<% else_if RawVal != RawVal %>C<% else %>D<% end_if %>E'));
		
		// Empty if with else
		$this->assertEquals('ABC',
			$this->render('A<% if NotSet %><% else %>B<% end_if %>C'));
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

	function testIncludeWithArguments() {
		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments %>'),
			'<p>[out:Arg1]</p><p>[out:Arg2]</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1=A %>'),
			'<p>A</p><p>[out:Arg2]</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1=A, Arg2=B %>'),
			'<p>A</p><p>B</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1=A Bare String, Arg2=B Bare String %>'),
			'<p>A Bare String</p><p>B Bare String</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1="A", Arg2=$B %>', new ArrayData(array('B' => 'Bar'))),
			'<p>A</p><p>Bar</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1="A" %>', new ArrayData(array('Arg1' => 'Foo', 'Arg2' => 'Bar'))),
			'<p>A</p><p>Bar</p>'
		);
	}

	
	function testRecursiveInclude() {
		$view = new SSViewer(array('SSViewerTestRecursiveInclude'));

		$data = new ArrayData(array(
			'Title' => 'A',
			'Children' => new ArrayList(array(
				new ArrayData(array(
					'Title' => 'A1',
					'Children' => new ArrayList(array(
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
	
	function assertEqualIgnoringWhitespace($a, $b) {
		$this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b));
	}

	/**
	 * See {@link ViewableDataTest} for more extensive casting tests,
	 * this test just ensures that basic casting is correctly applied during template parsing.
	 */
	function testCastingHelpers() {
		$vd = new SSViewerTest_ViewableData();
		$vd->TextValue = '<b>html</b>';
		$vd->HTMLValue = '<b>html</b>';
		$vd->UncastedValue = '<b>html</b>';

		// Value casted as "Text"
		$this->assertEquals(
			'&lt;b&gt;html&lt;/b&gt;',
			$t = SSViewer::fromString('$TextValue')->process($vd)
		);
		$this->assertEquals(
			'<b>html</b>',
			$t = SSViewer::fromString('$TextValue.RAW')->process($vd)
		);
		$this->assertEquals(
			'&lt;b&gt;html&lt;/b&gt;',
			$t = SSViewer::fromString('$TextValue.XML')->process($vd)
		);

		// Value casted as "HTMLText"
		$this->assertEquals(
			'<b>html</b>',
			$t = SSViewer::fromString('$HTMLValue')->process($vd)
		);
		$this->assertEquals(
			'<b>html</b>',
			$t = SSViewer::fromString('$HTMLValue.RAW')->process($vd)
		);
		$this->assertEquals(
			'&lt;b&gt;html&lt;/b&gt;',
			$t = SSViewer::fromString('$HTMLValue.XML')->process($vd)
		);

		// Uncasted value (falls back to ViewableData::$default_cast="HTMLText")
		$vd = new SSViewerTest_ViewableData(); // TODO Fix caching
		$vd->UncastedValue = '<b>html</b>';
		$this->assertEquals(
			'<b>html</b>',
			$t = SSViewer::fromString('$UncastedValue')->process($vd)
		);
		$vd = new SSViewerTest_ViewableData(); // TODO Fix caching
		$vd->UncastedValue = '<b>html</b>';
		$this->assertEquals(
			'<b>html</b>',
			$t = SSViewer::fromString('$UncastedValue.RAW')->process($vd)
		);
		$vd = new SSViewerTest_ViewableData(); // TODO Fix caching
		$vd->UncastedValue = '<b>html</b>';
		$this->assertEquals(
			'&lt;b&gt;html&lt;/b&gt;',
			$t = SSViewer::fromString('$UncastedValue.XML')->process($vd)
		);
	}
	
	function testSSViewerBasicIteratorSupport() {
		$data = new ArrayData(array(
			'Set' => new ArrayList(array(
				new SSViewerTest_Object("1"),
				new SSViewerTest_Object("2"),
				new SSViewerTest_Object("3"),
				new SSViewerTest_Object("4"),
				new SSViewerTest_Object("5"),
				new SSViewerTest_Object("6"),
				new SSViewerTest_Object("7"),
				new SSViewerTest_Object("8"),
				new SSViewerTest_Object("9"),
				new SSViewerTest_Object("10"),
			))
		));

		//base test
		$result = $this->render('<% loop Set %>$Number<% end_loop %>',$data);
		$this->assertEquals("12345678910",$result,"Numbers rendered in order");

		//test First
		$result = $this->render('<% loop Set %><% if First %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("1",$result,"Only the first number is rendered");

		//test Last
		$result = $this->render('<% loop Set %><% if Last %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("10",$result,"Only the last number is rendered");
				
		//test Even
		$result = $this->render('<% loop Set %><% if Even() %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("246810",$result,"Even numbers rendered in order");

		//test Even with quotes
		$result = $this->render('<% loop Set %><% if Even("1") %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("246810",$result,"Even numbers rendered in order");

		//test Even without quotes
		$result = $this->render('<% loop Set %><% if Even(1) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("246810",$result,"Even numbers rendered in order");

		//test Even with zero-based start index
		$result = $this->render('<% loop Set %><% if Even("0") %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("13579",$result,"Even (with zero-based index) numbers rendered in order");

		//test Odd
		$result = $this->render('<% loop Set %><% if Odd %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("13579",$result,"Odd numbers rendered in order");

		//test FirstLast
		$result = $this->render('<% loop Set %><% if FirstLast %>$Number$FirstLast<% end_if %><% end_loop %>',$data);
		$this->assertEquals("1first10last",$result,"First and last numbers rendered in order");

		//test Middle
		$result = $this->render('<% loop Set %><% if Middle %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("23456789",$result,"Middle numbers rendered in order");

		//test MiddleString
		$result = $this->render('<% loop Set %><% if MiddleString == "middle" %>$Number$MiddleString<% end_if %><% end_loop %>',$data);
		$this->assertEquals("2middle3middle4middle5middle6middle7middle8middle9middle",$result,"Middle numbers rendered in order");

		//test EvenOdd
		$result = $this->render('<% loop Set %>$EvenOdd<% end_loop %>',$data);
		$this->assertEquals("oddevenoddevenoddevenoddevenoddeven",$result,"Even and Odd is returned in sequence numbers rendered in order");

		//test Pos
		$result = $this->render('<% loop Set %>$Pos<% end_loop %>',$data);
		$this->assertEquals("12345678910",$result,"Even and Odd is returned in sequence numbers rendered in order");

		//test Total
		$result = $this->render('<% loop Set %>$TotalItems<% end_loop %>',$data);
		$this->assertEquals("10101010101010101010",$result,"10 total items X 10 are returned");

		//test Modulus
		$result = $this->render('<% loop Set %>$Modulus(2,1)<% end_loop %>',$data);
		$this->assertEquals("1010101010",$result,"1-indexed pos modular divided by 2 rendered in order");

		//test MultipleOf 3
		$result = $this->render('<% loop Set %><% if MultipleOf(3) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("369",$result,"Only numbers that are multiples of 3 are returned");

		//test MultipleOf 4
		$result = $this->render('<% loop Set %><% if MultipleOf(4) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("48",$result,"Only numbers that are multiples of 4 are returned");

		//test MultipleOf 5
		$result = $this->render('<% loop Set %><% if MultipleOf(5) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("510",$result,"Only numbers that are multiples of 5 are returned");

		//test MultipleOf 10
		$result = $this->render('<% loop Set %><% if MultipleOf(10,1) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("10",$result,"Only numbers that are multiples of 10 (with 1-based indexing) are returned");

		//test MultipleOf 9 zero-based
		$result = $this->render('<% loop Set %><% if MultipleOf(9,0) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("110",$result,"Only numbers that are multiples of 9 with zero-based indexing are returned. I.e. the first and last item");

		//test MultipleOf 11
		$result = $this->render('<% loop Set %><% if MultipleOf(11) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("",$result,"Only numbers that are multiples of 11 are returned. I.e. nothing returned");
	}

	/**
	 * Test $Up works when the scope $Up refers to was entered with a "with" block
	 */
	function testUpInWith() {

		// Data to run the loop tests on - three levels deep
		$data = new ArrayData(array(
			'Name' => 'Top',
			'Foo' => new ArrayData(array(
				'Name' => 'Foo',
				'Bar' => new ArrayData(array(
					'Name' => 'Bar',
					'Baz' => new ArrayData(array(
						'Name' => 'Baz'
					)),
					'Qux' => new ArrayData(array(
						'Name' => 'Qux'
					))
				))
			))
		));
		
		// Basic functionality
		$this->assertEquals('BarFoo',
			$this->render('<% with Foo %><% with Bar %>{$Name}{$Up.Name}<% end_with %><% end_with %>', $data));
		
		// Two level with block, up refers to internally referenced Bar
		$this->assertEquals('BarFoo',
			$this->render('<% with Foo.Bar %>{$Name}{$Up.Name}<% end_with %>', $data));

		// Stepping up & back down the scope tree
		$this->assertEquals('BazBarQux',
			$this->render('<% with Foo.Bar.Baz %>{$Name}{$Up.Name}{$Up.Qux.Name}<% end_with %>', $data));
		
		// Using $Up in a with block
		$this->assertEquals('BazBarQux',
			$this->render('<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}{$Qux.Name}<% end_with %><% end_with %>', $data));

		// Stepping up & back down the scope tree with with blocks
		$this->assertEquals('BazBarQuxBarBaz',
			$this->render('<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}<% with Qux %>{$Name}<% end_with %>{$Name}<% end_with %>{$Name}<% end_with %>', $data));

		// Using $Up.Up, where first $Up points to a previous scope entered using $Up, thereby skipping up to Foo 
		$this->assertEquals('Foo',
			$this->render('<% with Foo.Bar.Baz %><% with Up %><% with Qux %>{$Up.Up.Name}<% end_with %><% end_with %><% end_with %>', $data));
		
		// Using $Up.Up, where first $Up points to an Up used in a local scope lookup, should still skip to Foo 
		$this->assertEquals('Foo',
			$this->render('<% with Foo.Bar.Baz.Up.Qux %>{$Up.Up.Name}<% end_with %>', $data));
	}
	
	/**
	 * Test $Up works when the scope $Up refers to was entered with a "loop" block
	 */
	function testUpInLoop(){
		
		// Data to run the loop tests on - one sequence of three items, each with a subitem
		$data = new ArrayData(array(
			'Name' => 'Top',
			'Foo' => new ArrayList(array(
				new ArrayData(array(
					'Name' => '1',
					'Sub' => new ArrayData(array(
						'Name' => 'Bar'
					))
				)),
				new ArrayData(array(
					'Name' => '2',
					'Sub' => new ArrayData(array(
						'Name' => 'Baz'
					))
				)),
				new ArrayData(array(
					'Name' => '3',
					'Sub' => new ArrayData(array(
						'Name' => 'Qux'
					))
				))
			))
		));

		// Make sure inside a loop, $Up refers to the current item of the loop
		$this->assertEqualIgnoringWhitespace(
			'111 222 333',
			$this->render(
				'<% loop $Foo %>$Name<% with $Sub %>$Up.Name<% end_with %>$Name<% end_loop %>',
				$data
			)
		);

		// Make sure inside a loop, looping over $Up uses a separate iterator,
		// and doesn't interfere with the original iterator
		$this->assertEqualIgnoringWhitespace(
			'1Bar123Bar1 2Baz123Baz2 3Qux123Qux3',
			$this->render(
				'<% loop $Foo %>
					$Name
					<% with $Sub %>
						$Name
						<% loop $Up %>$Name<% end_loop %>
						$Name
					<% end_with %>
					$Name 
				<% end_loop %>', 
				$data
			)
		);
		
		// Make sure inside a loop, looping over $Up uses a separate iterator,
		// and doesn't interfere with the original iterator or local lookups
		$this->assertEqualIgnoringWhitespace(
			'1 Bar1 123 1Bar 1   2 Baz2 123 2Baz 2   3 Qux3 123 3Qux 3',
			$this->render(
				'<% loop $Foo %>
					$Name
					<% with $Sub %>
						{$Name}{$Up.Name}
						<% loop $Up %>$Name<% end_loop %>
						{$Up.Name}{$Name}
					<% end_with %>
					$Name
				<% end_loop %>',
				$data
			)
		);
	}
	
	/**
	 * Test that nested loops restore the loop variables correctly when pushing and popping states
	 */
	function testNestedLoops(){
		
		// Data to run the loop tests on - one sequence of three items, one with child elements
		// (of a different size to the main sequence)
		$data = new ArrayData(array(
			'Foo' => new ArrayList(array(
				new ArrayData(array(
					'Name' => '1',
					'Children' => new ArrayList(array(
						new ArrayData(array(
							'Name' => 'a'
						)),
						new ArrayData(array(
							'Name' => 'b'
						)),
					)),
				)),
				new ArrayData(array(
					'Name' => '2',
					'Children' => new ArrayList(),
				)),
				new ArrayData(array(
					'Name' => '3',
					'Children' => new ArrayList(),
				)),
			)),
		));

		// Make sure that including a loop inside a loop will not destroy the internal count of
		// items, checked by using "Last"
		$this->assertEqualIgnoringWhitespace(
			'1ab23last',
			$this->render(
				'<% loop $Foo %>$Name<% loop Children %>$Name<% end_loop %><% if Last %>last<% end_if %><% end_loop %>',
				$data
			)
		);
	}

	/**
	 * @covers SSViewer::get_themes()
	 */
	function testThemeRetrieval() {
		$ds = DIRECTORY_SEPARATOR;
		$testThemeBaseDir = TEMP_FOLDER . $ds . 'test-themes';

		if(file_exists($testThemeBaseDir)) Filesystem::removeFolder($testThemeBaseDir);

		mkdir($testThemeBaseDir);
		mkdir($testThemeBaseDir . $ds . 'blackcandy');
		mkdir($testThemeBaseDir . $ds . 'blackcandy_blog');
		mkdir($testThemeBaseDir . $ds . 'darkshades');
		mkdir($testThemeBaseDir . $ds . 'darkshades_blog');

		$this->assertEquals(array(
			'blackcandy' => 'blackcandy',
			'darkshades' => 'darkshades'
		), SSViewer::get_themes($testThemeBaseDir), 'Our test theme directory contains 2 themes');

		$this->assertEquals(array(
			'blackcandy' => 'blackcandy',
			'blackcandy_blog' => 'blackcandy_blog',
			'darkshades' => 'darkshades',
			'darkshades_blog' => 'darkshades_blog'
		), SSViewer::get_themes($testThemeBaseDir, true), 'Our test theme directory contains 2 themes and 2 sub-themes');

		// Remove all the test themes we created
		Filesystem::removeFolder($testThemeBaseDir);
	}
	
	function testRewriteHashlinks() {
		$oldRewriteHashLinks = SSViewer::getOption('rewriteHashlinks');
		SSViewer::setOption('rewriteHashlinks', true);
		
		// Emulate SSViewer::process()
		$base = Convert::raw2att($_SERVER['REQUEST_URI']);
		
		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinks_' . sha1(rand()) . '.ss';
		
		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$result = $tmpl->process($obj);
		$this->assertContains(
			'<a class="inserted" href="' . $base . '#anchor">InsertedLink</a>',
			$result
		);
		$this->assertContains(
			'<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
			$result
		);
		
		unlink($tmplFile);
		
		SSViewer::setOption('rewriteHashlinks', $oldRewriteHashLinks);
	}
	
	function testRewriteHashlinksInPhpMode() {
		$oldRewriteHashLinks = SSViewer::getOption('rewriteHashlinks');
		SSViewer::setOption('rewriteHashlinks', 'php');
		
		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinksInPhpMode_' . sha1(rand()) . '.ss';
		
		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$result = $tmpl->process($obj);
		$this->assertContains(
			'<a class="inserted" href="<?php echo strip_tags(',
			$result
		);
		// TODO Fix inline links in PHP mode
		// $this->assertContains(
		// 	'<a class="inline" href="<?php echo str_replace(',
		// 	$result
		// );
		
		unlink($tmplFile);
		
		SSViewer::setOption('rewriteHashlinks', $oldRewriteHashLinks);
	}
	
	function testRenderWithSourceFileComments() {
		$origType = Director::get_environment_type();
		Director::set_environment_type('dev');
		SSViewer::set_source_file_comments(true);
		
		$view = new SSViewer(array('SSViewerTestCommentsFullSource'));
		$data = new ArrayData(array());
		
		$result = $view->process($data);
		$expected = '<!doctype html>
<html><!-- template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsFullSource.ss -->
	<head></head>
	<body></body>
<!-- end template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsFullSource.ss --></html>
';
		$this->assertEquals($result, $expected);
		
		$view = new SSViewer(array('SSViewerTestCommentsPartialSource'));
		$data = new ArrayData(array());
		
		$result = $view->process($data);
		$expected = '<!-- template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsPartialSource.ss --><div class=\'typography\'></div><!-- end template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsPartialSource.ss -->';
		$this->assertEquals($result, $expected);
		
		$view = new SSViewer(array('SSViewerTestCommentsWithInclude'));
		$data = new ArrayData(array());
		
		$result = $view->process($data);
		$expected = '<!-- template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsWithInclude.ss --><div class=\'typography\'><!-- include \'SSViewerTestCommentsInclude\' --><!-- template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsInclude.ss -->Included<!-- end template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsInclude.ss --><!-- end include \'SSViewerTestCommentsInclude\' --></div><!-- end template ' . FRAMEWORK_PATH . '/tests/templates/SSViewerTestCommentsWithInclude.ss -->';
		$this->assertEquals($result, $expected);
		
		SSViewer::set_source_file_comments(false);
		Director::set_environment_type($origType);
	}

	function testLoopIteratorIterator() {
		$list = new PaginatedList(new ArrayList());
		$viewer = new SSViewer_FromString('<% loop List %>$ID - $FirstName<br /><% end_loop %>');
		$result = $viewer->process(new ArrayData(array('List' => $list)));
		$this->assertEquals($result, '');
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
			$output = new ArrayList();
			for($i=0;$i<$matches[1];$i++) $output->push(new SSViewerTestFixture($childName));
			return $output;

		} else if(preg_match('/NotSet/i', $fieldName)) {
			return new ViewableData();

		} else {
			return new SSViewerTestFixture($childName);
		}
	}
	

	function XML_val($fieldName, $arguments = null, $cache = false) {
		if(preg_match('/NotSet/i', $fieldName)) {
			return '';
		} else if(preg_match('/Raw/i', $fieldName)) {
			return $fieldName;
		} else {
			return '[out:' . $this->argedName($fieldName, $arguments) . ']';
		}
	}

	function hasValue($fieldName, $arguments = null, $cache = true) {
		return (bool)$this->XML_val($fieldName, $arguments);
	}
}

class SSViewerTest_ViewableData extends ViewableData implements TestOnly {

	public static $casting = array(
		'TextValue' => 'Text',
		'HTMLValue' => 'HTMLText'
	);

	function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}
	
	function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}


class SSViewerTest_Controller extends Controller {
	
}

class SSViewerTest_Object extends DataObject {

	public $number = null;

	function __construct($number = null) {
		parent::__construct();
		$this->number = $number;
	}

	function Number() {
		return $this->number;
	}

	function absoluteBaseURL() {
		return "testLocalFunctionPriorityCalled";
	}

	function lotsOfArguments11($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k) {
		return $a. $b. $c. $d. $e. $f. $g. $h. $i. $j. $k;
	}
}

class SSViewerTest_GlobalProvider implements TemplateGlobalProvider, TestOnly {

	public static function get_template_global_variables() {
		return array(
			'SSViewerTest_GlobalHTMLFragment' => array('method' => 'get_html'),
			'SSViewerTest_GlobalHTMLEscaped' => array('method' => 'get_html', 'casting' => 'Varchar'),

			'SSViewerTest_GlobalAutomatic',
			'SSViewerTest_GlobalReferencedByString' => 'get_reference',
			'SSViewerTest_GlobalReferencedInArray' => array('method' => 'get_reference'),

			'SSViewerTest_GlobalThatTakesArguments' => array('method' => 'get_argmix')

		);
	}

	static function get_html() {
		return '<div></div>';
	}

	static function SSViewerTest_GlobalAutomatic() {
		return 'automatic';
	}

	static function get_reference() {
		return 'reference';
	}

	static function get_argmix() {
		$args = func_get_args();
		return 'z' . implode(':', $args) . 'z';
	}

}
