<?php

class SSViewerTest extends SapphireTest {

	/**
	 * Backup of $_SERVER global
	 *
	 * @var array
	 */
	protected $oldServer = array();

	protected $extraDataObjects = array(
		'SSViewerTest_Object',
	);

	public function setUp() {
		parent::setUp();
		Config::inst()->update('SSViewer', 'source_file_comments', false);
		Config::inst()->update('SSViewer_FromString', 'cache_template', false);
		$this->oldServer = $_SERVER;
	}

	public function tearDown() {
		$_SERVER = $this->oldServer;
		parent::tearDown();
	}

	/**
	 * Tests for {@link Config::inst()->get('SSViewer', 'theme')} for different behaviour
	 * of user defined themes via {@link SiteConfig} and default theme
	 * when no user themes are defined.
	 */
	public function testCurrentTheme() {
		//TODO: SiteConfig moved to CMS
		Config::inst()->update('SSViewer', 'theme', 'mytheme');
		$this->assertEquals('mytheme', Config::inst()->get('SSViewer', 'theme'),
			'Current theme is the default - user has not defined one');
	}

	/**
	 * Test that a template without a <head> tag still renders.
	 */
	public function testTemplateWithoutHeadRenders() {
		$data = new ArrayData(array(
			'Var' => 'var value'
		));

		$result = $data->renderWith("SSViewerTestPartialTemplate");
		$this->assertEquals('Test partial template: var value', trim(preg_replace("/<!--.*-->/U",'',$result)));
	}

	public function testIncludeScopeInheritance() {
		$data = $this->getScopeInheritanceTestData();
		$expected = array(
			'Item 1 - First-ODD top:Item 1',
			'Item 2 - EVEN top:Item 2',
			'Item 3 - ODD top:Item 3',
			'Item 4 - EVEN top:Item 4',
			'Item 5 - ODD top:Item 5',
			'Item 6 - Last-EVEN top:Item 6',
		);

		$result = $data->renderWith('SSViewerTestIncludeScopeInheritance');
		$this->assertExpectedStrings($result, $expected);

		// reset results for the tests that include arguments (the title is passed as an arg)
		$expected = array(
			'Item 1 _ Item 1 - First-ODD top:Item 1',
			'Item 2 _ Item 2 - EVEN top:Item 2',
			'Item 3 _ Item 3 - ODD top:Item 3',
			'Item 4 _ Item 4 - EVEN top:Item 4',
			'Item 5 _ Item 5 - ODD top:Item 5',
			'Item 6 _ Item 6 - Last-EVEN top:Item 6',
		);

		$result = $data->renderWith('SSViewerTestIncludeScopeInheritanceWithArgs');
		$this->assertExpectedStrings($result, $expected);
	}

	public function testIncludeTruthyness() {
		$data = new ArrayData(array(
			'Title' => 'TruthyTest',
			'Items' => new ArrayList(array(
				new ArrayData(array('Title' => 'Item 1')),
				new ArrayData(array('Title' => '')),
				new ArrayData(array('Title' => true)),
				new ArrayData(array('Title' => false)),
				new ArrayData(array('Title' => null)),
				new ArrayData(array('Title' => 0)),
				new ArrayData(array('Title' => 7))
			))
		));
		$result = $data->renderWith('SSViewerTestIncludeScopeInheritanceWithArgs');

		// We should not end up with empty values appearing as empty
		$expected = array(
			'Item 1 _ Item 1 - First-ODD top:Item 1',
			'Untitled - EVEN top:',
			'1 _ 1 - ODD top:1',
			'Untitled - EVEN top:',
			'Untitled - ODD top:',
			'Untitled - EVEN top:0',
			'7 _ 7 - Last-ODD top:7'
		);
		$this->assertExpectedStrings($result, $expected);
	}

	private function getScopeInheritanceTestData() {
		return new ArrayData(array(
			'Title' => 'TopTitleValue',
			'Items' => new ArrayList(array(
				new ArrayData(array('Title' => 'Item 1')),
				new ArrayData(array('Title' => 'Item 2')),
				new ArrayData(array('Title' => 'Item 3')),
				new ArrayData(array('Title' => 'Item 4')),
				new ArrayData(array('Title' => 'Item 5')),
				new ArrayData(array('Title' => 'Item 6'))
			))
		));
	}

	private function assertExpectedStrings($result, $expected) {
		foreach ($expected as $expectedStr) {
			$this->assertTrue(
				(boolean) preg_match("/{$expectedStr}/", $result),
				"Didn't find '{$expectedStr}' in:\n{$result}"
			);
		}
	}

	/**
	 * Small helper to render templates from strings
	 */
	public function render($templateString, $data = null, $cacheTemplate = false) {
		$t = SSViewer::fromString($templateString, $cacheTemplate);
		if(!$data) $data = new SSViewerTestFixture();
		return trim(''.$t->process($data));
	}

	public function testRequirements() {
		$requirements = $this->getMockBuilder("Requirements_Backend")
			->setMethods(array("javascript", "css"))
			->getMock();
		$jsFile = FRAMEWORK_DIR . '/tests/forms/a.js';
		$cssFile = FRAMEWORK_DIR . '/tests/forms/a.js';

		$requirements->expects($this->once())->method('javascript')->with($jsFile);
		$requirements->expects($this->once())->method('css')->with($cssFile);

		Requirements::set_backend($requirements);

		$template = $this->render("<% require javascript($jsFile) %>
		<% require css($cssFile) %>");
		$this->assertFalse((bool)trim($template), "Should be no content in this return.");
	}

	public function testRequirementsCombine(){
		$oldBackend = Requirements::backend();
		$testBackend = new Requirements_Backend();
		Requirements::set_backend($testBackend);
		$combinedTestFilePath = BASE_PATH . '/' . $testBackend->getCombinedFilesFolder() . '/testRequirementsCombine.js';

		$jsFile = FRAMEWORK_DIR . '/tests/view/themes/javascript/bad.js';
		$jsFileContents = file_get_contents(BASE_PATH . '/' . $jsFile);
		Requirements::combine_files('testRequirementsCombine.js', array($jsFile));
		require_once('thirdparty/jsmin/jsmin.php');

		// first make sure that our test js file causes an exception to be thrown
		try{
			$content = JSMin::minify($content);
			Requirements::set_backend($oldBackend);
			$this->fail('JSMin did not throw exception on minify bad file: ');
		}catch(Exception $e){
			// exception thrown... good
		}

		// secondly, make sure that requirements combine throws the correct warning, and only that warning
		@unlink($combinedTestFilePath);
		try{
			Requirements::process_combined_files();
		}catch(PHPUnit_Framework_Error_Warning $e){
			if(strstr($e->getMessage(), 'Failed to minify') === false){
				Requirements::set_backend($oldBackend);
				$this->fail('Requirements::process_combined_files raised a warning, which is good, but this is not the expected warning ("Failed to minify..."): '.$e);
			}
		}catch(Exception $e){
			Requirements::set_backend($oldBackend);
			$this->fail('Requirements::process_combined_files did not catch exception caused by minifying bad js file: '.$e);
		}

		// and make sure the combined content matches the input content, i.e. no loss of functionality
		if(!file_exists($combinedTestFilePath)){
			Requirements::set_backend($oldBackend);
			$this->fail('No combined file was created at expected path: '.$combinedTestFilePath);
		}
		$combinedTestFileContents = file_get_contents($combinedTestFilePath);
		$this->assertContains($jsFileContents, $combinedTestFileContents);

		// reset
		Requirements::set_backend($oldBackend);
	}



	public function testComments() {
		$output = $this->render(<<<SS
This is my template<%-- this is a comment --%>This is some content<%-- this is another comment --%>Final content
<%-- Alone multi
	line comment --%>
Some more content
Mixing content and <%-- multi
	line comment --%> Final final
content
SS
);
		$shouldbe = <<<SS
This is my templateThis is some contentFinal content

Some more content
Mixing content and  Final final
content
SS;

		$this->assertEquals($shouldbe, $output);
	}

	public function testBasicText() {
		$this->assertEquals('"', $this->render('"'), 'Double-quotes are left alone');
		$this->assertEquals("'", $this->render("'"), 'Single-quotes are left alone');
		$this->assertEquals('A', $this->render('\\A'), 'Escaped characters are unescaped');
		$this->assertEquals('\\A', $this->render('\\\\A'), 'Escaped back-slashed are correctly unescaped');
	}

	public function testBasicInjection() {
		$this->assertEquals('[out:Test]', $this->render('$Test'), 'Basic stand-alone injection');
		$this->assertEquals('[out:Test]', $this->render('{$Test}'), 'Basic stand-alone wrapped injection');
		$this->assertEquals('A[out:Test]!', $this->render('A$Test!'), 'Basic surrounded injection');
		$this->assertEquals('A[out:Test]B', $this->render('A{$Test}B'), 'Basic surrounded wrapped injection');

		$this->assertEquals('A$B', $this->render('A\\$B'), 'No injection as $ escaped');
		$this->assertEquals('A$ B', $this->render('A$ B'), 'No injection as $ not followed by word character');
		$this->assertEquals('A{$ B', $this->render('A{$ B'), 'No injection as {$ not followed by word character');

		$this->assertEquals('{$Test}', $this->render('{\\$Test}'), 'Escapes can be used to avoid injection');
		$this->assertEquals('{\\[out:Test]}', $this->render('{\\\\$Test}'),
			'Escapes before injections are correctly unescaped');
	}


	public function testGlobalVariableCalls() {
		$this->assertEquals('automatic', $this->render('$SSViewerTest_GlobalAutomatic'));
		$this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedByString'));
		$this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedInArray'));
	}

	public function testGlobalVariableCallsWithArguments() {
		$this->assertEquals('zz', $this->render('$SSViewerTest_GlobalThatTakesArguments'));
		$this->assertEquals('zFooz', $this->render('$SSViewerTest_GlobalThatTakesArguments("Foo")'));
		$this->assertEquals('zFoo:Bar:Bazz',
			$this->render('$SSViewerTest_GlobalThatTakesArguments("Foo", "Bar", "Baz")'));
		$this->assertEquals('zreferencez',
			$this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalReferencedByString)'));
	}

	public function testGlobalVariablesAreEscaped() {
		$this->assertEquals('<div></div>', $this->render('$SSViewerTest_GlobalHTMLFragment'));
		$this->assertEquals('&lt;div&gt;&lt;/div&gt;', $this->render('$SSViewerTest_GlobalHTMLEscaped'));

		$this->assertEquals('z<div></div>z',
			$this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLFragment)'));
		$this->assertEquals('z&lt;div&gt;&lt;/div&gt;z',
			$this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLEscaped)'));
	}

	public function testCoreGlobalVariableCalls() {
		$this->assertEquals(Director::absoluteBaseURL(),
			$this->render('{$absoluteBaseURL}'), 'Director::absoluteBaseURL can be called from within template');
		$this->assertEquals(Director::absoluteBaseURL(), $this->render('{$AbsoluteBaseURL}'),
			'Upper-case %AbsoluteBaseURL can be called from within template');

		$this->assertEquals(Director::is_ajax(), $this->render('{$isAjax}'),
			'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$IsAjax}'),
			'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$is_ajax}'),
			'All variations of is_ajax result in the correct call');
		$this->assertEquals(Director::is_ajax(), $this->render('{$Is_ajax}'),
			'All variations of is_ajax result in the correct call');

		$this->assertEquals(i18n::get_locale(), $this->render('{$i18nLocale}'),
			'i18n template functions result correct result');
		$this->assertEquals(i18n::get_locale(), $this->render('{$get_locale}'),
			'i18n template functions result correct result');

		$this->assertEquals((string)Member::currentUser(), $this->render('{$CurrentMember}'),
			'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$CurrentUser}'),
			'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$currentMember}'),
			'Member template functions result correct result');
		$this->assertEquals((string)Member::currentUser(), $this->render('{$currentUser}'),
			'Member template functions result correct result');

		$this->assertEquals(SecurityToken::getSecurityID(), $this->render('{$getSecurityID}'),
			'SecurityToken template functions result correct result');
		$this->assertEquals(SecurityToken::getSecurityID(), $this->render('{$SecurityID}'),
			'SecurityToken template functions result correct result');

		$this->assertEquals(Permission::check("ADMIN"), (bool)$this->render('{$HasPerm(\'ADMIN\')}'),
			'Permissions template functions result correct result');
		$this->assertEquals(Permission::check("ADMIN"), (bool)$this->render('{$hasPerm(\'ADMIN\')}'),
			'Permissions template functions result correct result');
	}

	public function testNonFieldCastingHelpersNotUsedInHasValue() {
		// check if Link without $ in front of variable
		$result = $this->render(
			'A<% if Link %>$Link<% end_if %>B', new SSViewerTest_Object());
		$this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if Link %>');

		// check if Link with $ in front of variable
		$result = $this->render(
			'A<% if $Link %>$Link<% end_if %>B', new SSViewerTest_Object());
		$this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if $Link %>');
	}

	public function testLocalFunctionsTakePriorityOverGlobals() {
		$data = new ArrayData(array(
			'Page' => new SSViewerTest_Object()
		));

		//call method with lots of arguments
		$result = $this->render(
			'<% with Page %>$lotsOfArguments11("a","b","c","d","e","f","g","h","i","j","k")<% end_with %>',$data);
		$this->assertEquals("abcdefghijk",$result, "public function can accept up to 11 arguments");

		//call method that does not exist
		$result = $this->render('<% with Page %><% if IDoNotExist %>hello<% end_if %><% end_with %>',$data);
		$this->assertEquals("",$result, "Method does not exist - empty result");

		//call if that does not exist
		$result = $this->render('<% with Page %>$IDoNotExist("hello")<% end_with %>',$data);
		$this->assertEquals("",$result, "Method does not exist - empty result");

		//call method with same name as a global method (local call should take priority)
		$result = $this->render('<% with Page %>$absoluteBaseURL<% end_with %>',$data);
		$this->assertEquals("testLocalFunctionPriorityCalled",$result,
			"Local Object's public function called. Did not return the actual baseURL of the current site");
	}

	public function testCurrentScopeLoopWith() {
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

		$result = $this->render(
			'<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop works");

		$result = $this->render(
			'<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop works");

		$result = $this->render('<% with Foo %>$Count<% end_with %>',$data);
		$this->assertEquals("4",$result, "4 items in the DataObjectSet");

		$result = $this->render('<% with Foo %><% loop Up.Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %>'
			. '<% end_if %><% end_loop %><% end_with %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop in with Up.Foo scope works");

		$result = $this->render('<% with Foo %><% loop %>$Number<% if Sub %><% with Sub %>$Name<% end_with %>'
			. '<% end_if %><% end_loop %><% end_with %>',$data);
		$this->assertEquals("SubKid1SubKid2Number6",$result, "Loop in current scope works");
	}

	public function testObjectDotArguments() {
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

	public function testEscapedArguments() {
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

	public function testLoopWhitespace() {
		$this->assertEquals(
			'before[out:SingleItem.Test]after
				beforeTestafter',
			$this->render('before<% loop SingleItem %>$Test<% end_loop %>after
				before<% loop SingleItem %>Test<% end_loop %>after')
		);

		// The control tags are removed from the output, but no whitespace
		// This is a quirk that could be changed, but included in the test to make the current
		// behaviour explicit
		$this->assertEquals(
			'before

[out:SingleItem.ItemOnItsOwnLine]

after',
			$this->render('before
<% loop SingleItem %>
$ItemOnItsOwnLine
<% end_loop %>
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
<% loop Loop3 %>
$ItemOnItsOwnLine
<% end_loop %>
after')
		);
	}

	public function testControls() {
		// Single item controls
		$this->assertEquals(
			'a[out:Foo.Bar.Item]b
				[out:Foo.Bar(Arg1).Item]
				[out:Foo(Arg1).Item]
				[out:Foo(Arg1,Arg2).Item]
				[out:Foo(Arg1,Arg2,Arg3).Item]',
			$this->render('<% with Foo.Bar %>a{$Item}b<% end_with %>
				<% with Foo.Bar(Arg1) %>$Item<% end_with %>
				<% with Foo(Arg1) %>$Item<% end_with %>
				<% with Foo(Arg1, Arg2) %>$Item<% end_with %>
				<% with Foo(Arg1, Arg2, Arg3) %>$Item<% end_with %>')
		);

		// Loop controls
		$this->assertEquals('a[out:Foo.Loop2.Item]ba[out:Foo.Loop2.Item]b',
			$this->render('<% loop Foo.Loop2 %>a{$Item}b<% end_loop %>'));

		$this->assertEquals('[out:Foo.Loop2(Arg1).Item][out:Foo.Loop2(Arg1).Item]',
			$this->render('<% loop Foo.Loop2(Arg1) %>$Item<% end_loop %>'));

		$this->assertEquals('[out:Loop2(Arg1).Item][out:Loop2(Arg1).Item]',
			$this->render('<% loop Loop2(Arg1) %>$Item<% end_loop %>'));

		$this->assertEquals('[out:Loop2(Arg1,Arg2).Item][out:Loop2(Arg1,Arg2).Item]',
			$this->render('<% loop Loop2(Arg1, Arg2) %>$Item<% end_loop %>'));

		$this->assertEquals('[out:Loop2(Arg1,Arg2,Arg3).Item][out:Loop2(Arg1,Arg2,Arg3).Item]',
			$this->render('<% loop Loop2(Arg1, Arg2, Arg3) %>$Item<% end_loop %>'));

	}

	public function testIfBlocks() {
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

		// test inequalities with simple numbers
		$this->assertEquals('ABD', $this->render('A<% if 5 > 3 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ABD', $this->render('A<% if 5 >= 3 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 3 > 5 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 3 >= 5 %>B<% else %>C<% end_if %>D'));

		$this->assertEquals('ABD', $this->render('A<% if 3 < 5 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ABD', $this->render('A<% if 3 <= 5 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 5 < 3 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 5 <= 3 %>B<% else %>C<% end_if %>D'));

		$this->assertEquals('ABD', $this->render('A<% if 4 <= 4 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ABD', $this->render('A<% if 4 >= 4 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 4 > 4 %>B<% else %>C<% end_if %>D'));
		$this->assertEquals('ACD', $this->render('A<% if 4 < 4 %>B<% else %>C<% end_if %>D'));

		// empty else_if and else tags, if this would not be supported,
		// the output would stop after A, thereby failing the assert
		$this->assertEquals('AD', $this->render('A<% if IsSet %><% else %><% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet %><% else_if IsSet %><% else %><% end_if %>D'));
		$this->assertEquals('AD',
			$this->render('A<% if NotSet %><% else_if AlsoNotSet %><% else %><% end_if %>D'));

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

	public function testBaseTagGeneration() {
		// XHTML wil have a closed base tag
		$tmpl1 = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
				. ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
			$this->render($tmpl2));


		$tmpl3 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
			$this->render($tmpl3));

		// Check that the content negotiator converts to the equally legal formats
		$negotiator = new ContentNegotiator();

		$response = new SS_HTTPResponse($this->render($tmpl1));
		$negotiator->html($response);
		$this->assertRegExp('/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
			$response->getBody());

		$response = new SS_HTTPResponse($this->render($tmpl1));
		$negotiator->xhtml($response);
		$this->assertRegExp('/<head><base href=".*" \/><\/head>/', $response->getBody());
	}

	public function testIncludeWithArguments() {
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
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1="A", Arg2=$B %>',
				new ArrayData(array('B' => 'Bar'))),
			'<p>A</p><p>Bar</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeWithArguments Arg1="A" %>',
				new ArrayData(array('Arg1' => 'Foo', 'Arg2' => 'Bar'))),
			'<p>A</p><p>Bar</p>'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeScopeInheritanceWithArgsInLoop Title="SomeArg" %>',
				new ArrayData(array('Items' => new ArrayList(array(
					new ArrayData(array('Title' => 'Foo')),
					new ArrayData(array('Title' => 'Bar'))
				))))),
			'SomeArg - Foo - Bar - SomeArg'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeScopeInheritanceWithArgsInWith Title="A" %>',
				new ArrayData(array('Item' => new ArrayData(array('Title' =>'B'))))),
			'A - B - A'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeScopeInheritanceWithArgsInNestedWith Title="A" %>',
				new ArrayData(array(
					'Item' => new ArrayData(array(
						'Title' =>'B', 'NestedItem' => new ArrayData(array('Title' => 'C'))
					)))
				)),
			'A - B - C - B - A'
		);

		$this->assertEquals(
			$this->render('<% include SSViewerTestIncludeScopeInheritanceWithUpAndTop Title="A" %>',
				new ArrayData(array(
					'Item' => new ArrayData(array(
						'Title' =>'B', 'NestedItem' => new ArrayData(array('Title' => 'C'))
					)))
				)),
			'A - A - A'
		);

		$data = new ArrayData(array(
			'Nested' => new ArrayData(array(
				'Object' => new ArrayData(array('Key' => 'A'))
			)),
			'Object' => new ArrayData(array('Key' => 'B'))
		));

		$tmpl = SSViewer::fromString('<% include SSViewerTestIncludeObjectArguments A=$Nested.Object, B=$Object %>');
		$res  = $tmpl->process($data);
		$this->assertEqualIgnoringWhitespace('A B', $res, 'Objects can be passed as named arguments');
	}


	public function testRecursiveInclude() {
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

	public function assertEqualIgnoringWhitespace($a, $b) {
		$this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b));
	}

	/**
	 * See {@link ViewableDataTest} for more extensive casting tests,
	 * this test just ensures that basic casting is correctly applied during template parsing.
	 */
	public function testCastingHelpers() {
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

	public function testSSViewerBasicIteratorSupport() {
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
		$result = $this->render('<% loop Set %><% if MiddleString == "middle" %>$Number$MiddleString<% end_if %>'
			. '<% end_loop %>',$data);
		$this->assertEquals("2middle3middle4middle5middle6middle7middle8middle9middle",$result,
			"Middle numbers rendered in order");

		//test EvenOdd
		$result = $this->render('<% loop Set %>$EvenOdd<% end_loop %>',$data);
		$this->assertEquals("oddevenoddevenoddevenoddevenoddeven",$result,
			"Even and Odd is returned in sequence numbers rendered in order");

		//test Pos
		$result = $this->render('<% loop Set %>$Pos<% end_loop %>',$data);
		$this->assertEquals("12345678910", $result, '$Pos is rendered in order');

		//test Pos
		$result = $this->render('<% loop Set %>$Pos(0)<% end_loop %>',$data);
		$this->assertEquals("0123456789", $result, '$Pos(0) is rendered in order');

		//test FromEnd
		$result = $this->render('<% loop Set %>$FromEnd<% end_loop %>',$data);
		$this->assertEquals("10987654321", $result, '$FromEnd is rendered in order');

		//test FromEnd
		$result = $this->render('<% loop Set %>$FromEnd(0)<% end_loop %>',$data);
		$this->assertEquals("9876543210", $result, '$FromEnd(0) rendered in order');

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
		$this->assertEquals("110",$result,
			"Only numbers that are multiples of 9 with zero-based indexing are returned. (The first and last item)");

		//test MultipleOf 11
		$result = $this->render('<% loop Set %><% if MultipleOf(11) %>$Number<% end_if %><% end_loop %>',$data);
		$this->assertEquals("",$result,"Only numbers that are multiples of 11 are returned. I.e. nothing returned");
	}

	/**
	 * Test $Up works when the scope $Up refers to was entered with a "with" block
	 */
	public function testUpInWith() {

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
			$this->render('<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}{$Qux.Name}<% end_with %>'
				.'<% end_with %>', $data));

		// Stepping up & back down the scope tree with with blocks
		$this->assertEquals('BazBarQuxBarBaz',
			$this->render('<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}<% with Qux %>{$Name}<% end_with %>'
				. '{$Name}<% end_with %>{$Name}<% end_with %>', $data));

		// Using $Up.Up, where first $Up points to a previous scope entered using $Up, thereby skipping up to Foo
		$this->assertEquals('Foo',
			$this->render('<% with Foo.Bar.Baz %><% with Up %><% with Qux %>{$Up.Up.Name}<% end_with %><% end_with %>'
				. '<% end_with %>', $data));

		// Using $Up.Up, where first $Up points to an Up used in a local scope lookup, should still skip to Foo
		$this->assertEquals('Foo',
			$this->render('<% with Foo.Bar.Baz.Up.Qux %>{$Up.Up.Name}<% end_with %>', $data));
	}

	/**
	 * Test $Up works when the scope $Up refers to was entered with a "loop" block
	 */
	public function testUpInLoop(){

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
	public function testNestedLoops(){

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
			$this->render('<% loop $Foo %>$Name<% loop Children %>$Name<% end_loop %><% if Last %>last<% end_if %>'
				. '<% end_loop %>', $data
			)
		);
	}

	public function testLayout() {
		$self = $this;

		$this->useTestTheme(dirname(__FILE__), 'layouttest', function() use ($self) {
			$template = new SSViewer(array('Page'));
			$self->assertEquals("Foo\n\n", $template->process(new ArrayData(array())));

			$template = new SSViewer(array('Shortcodes', 'Page'));
			$self->assertEquals("[file_link]\n\n", $template->process(new ArrayData(array())));
		});
	}

	/**
	 * @covers SSViewer::get_templates_by_class()
	 */
	public function testGetTemplatesByClass() {
		$self = $this;
		$this->useTestTheme(dirname(__FILE__), 'layouttest', function() use ($self) {
			// Test passing a string
			$templates = SSViewer::get_templates_by_class('SSViewerTest_Controller', '', 'Controller');
			$self->assertCount(2, $templates);

			// Test to ensure we're stopping at the base class.
			$templates = SSViewer::get_templates_by_class('SSViewerTest_Controller', '', 'SSViewerTest_Controller');
			$self->assertCount(1, $templates);

			// Make sure we can filter our templates by suffix.
			$templates = SSViewer::get_templates_by_class('SSViewerTest', '_Controller');
			$self->assertCount(1, $templates);

			// Test passing a valid object
			$templates = SSViewer::get_templates_by_class("SSViewerTest_Controller", '', 'Controller');

			// Test that templates are returned in the correct order
			$self->assertEquals('SSViewerTest_Controller', array_shift($templates));
			$self->assertEquals('Controller', array_shift($templates));

			// Let's throw something random in there.
			$self->setExpectedException('InvalidArgumentException');
			$templates = SSViewer::get_templates_by_class(array());
		});
	}

	/**
	 * @covers SSViewer::get_themes()
	 */
	public function testThemeRetrieval() {
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
		), SSViewer::get_themes($testThemeBaseDir, true),
			'Our test theme directory contains 2 themes and 2 sub-themes');

		// Remove all the test themes we created
		Filesystem::removeFolder($testThemeBaseDir);
	}

	public function testRewriteHashlinks() {
		$orig = Config::inst()->get('SSViewer', 'rewrite_hash_links');
		Config::inst()->update('SSViewer', 'rewrite_hash_links', true);

		$_SERVER['HTTP_HOST'] = 'www.mysite.com';
		$_SERVER['REQUEST_URI'] = '//file.com?foo"onclick="alert(\'xss\')""';

		// Emulate SSViewer::process()
		// Note that leading double slashes have been rewritten to prevent these being mis-interepreted
		// as protocol-less absolute urls
		$base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinks_' . sha1(rand()) . '.ss';

		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
				$ExternalInsertedLink
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<svg><use xlink:href="#sprite"></use></svg>
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$obj->ExternalInsertedLink = '<a class="external-inserted" href="http://google.com#anchor">ExternalInsertedLink</a>';
		$result = $tmpl->process($obj);
		$this->assertContains(
			'<a class="inserted" href="' . $base . '#anchor">InsertedLink</a>',
			$result
		);
		$this->assertContains(
			'<a class="external-inserted" href="http://google.com#anchor">ExternalInsertedLink</a>',
			$result
		);
		$this->assertContains(
			'<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
			$result
		);
		$this->assertContains(
			'<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>',
			$result
		);
		$this->assertContains(
			'<svg><use xlink:href="#sprite"></use></svg>',
			$result,
			'SSTemplateParser should only rewrite anchor hrefs'
		);

		unlink($tmplFile);

		Config::inst()->update('SSViewer', 'rewrite_hash_links', $orig);
	}

	public function testRewriteHashlinksInPhpMode() {
		$orig = Config::inst()->get('SSViewer', 'rewrite_hash_links');
		Config::inst()->update('SSViewer', 'rewrite_hash_links', 'php');

		$tmplFile = TEMP_FOLDER . '/SSViewerTest_testRewriteHashlinksInPhpMode_' . sha1(rand()) . '.ss';

		// Note: SSViewer_FromString doesn't rewrite hash links.
		file_put_contents($tmplFile, '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<svg><use xlink:href="#sprite"></use></svg>
				<body>
			</html>');
		$tmpl = new SSViewer($tmplFile);
		$obj = new ViewableData();
		$obj->InsertedLink = '<a class="inserted" href="#anchor">InsertedLink</a>';
		$result = $tmpl->process($obj);

		$code = <<<'EOC'
<a class="inserted" href="<?php echo Convert::raw2att(preg_replace("/^(\/)+/", "/", $_SERVER['REQUEST_URI'])); ?>#anchor">InsertedLink</a>
EOC;
		$this->assertContains($code, $result);
		// TODO Fix inline links in PHP mode
		// $this->assertContains(
		// 	'<a class="inline" href="<?php echo str_replace(',
		// 	$result
		// );
		$this->assertContains(
			'<svg><use xlink:href="#sprite"></use></svg>',
			$result,
			'SSTemplateParser should only rewrite anchor hrefs'
		);

		unlink($tmplFile);

		Config::inst()->update('SSViewer', 'rewrite_hash_links', $orig);
	}

	public function testRenderWithSourceFileComments() {
		$origEnv = Config::inst()->get('Director', 'environment_type');
		Config::inst()->update('Director', 'environment_type', 'dev');
		Config::inst()->update('SSViewer', 'source_file_comments', true);
		$f = FRAMEWORK_PATH . '/tests/templates/SSViewerTestComments';
		$templates = array(
			array(
				'name' => 'SSViewerTestCommentsFullSource',
				'expected' => ""
					. "<!doctype html>"
					. "<!-- template $f/SSViewerTestCommentsFullSource.ss -->"
					. "<html>"
					. "\t<head></head>"
					. "\t<body></body>"
					. "</html>"
					. "<!-- end template $f/SSViewerTestCommentsFullSource.ss -->",
			),
			array(
				'name' => 'SSViewerTestCommentsFullSourceHTML4Doctype',
				'expected' => ""
					. "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML "
					. "4.01//EN\"\t\t\"http://www.w3.org/TR/html4/strict.dtd\">"
					. "<!-- template $f/SSViewerTestCommentsFullSourceHTML4Doctype.ss -->"
					. "<html>"
					. "\t<head></head>"
					. "\t<body></body>"
					. "</html>"
					. "<!-- end template $f/SSViewerTestCommentsFullSourceHTML4Doctype.ss -->",
			),
			array(
				'name' => 'SSViewerTestCommentsFullSourceNoDoctype',
				'expected' => ""
					. "<html><!-- template $f/SSViewerTestCommentsFullSourceNoDoctype.ss -->"
					. "\t<head></head>"
					. "\t<body></body>"
					. "<!-- end template $f/SSViewerTestCommentsFullSourceNoDoctype.ss --></html>",
			),
			array(
				'name' => 'SSViewerTestCommentsFullSourceIfIE',
				'expected' => ""
					. "<!doctype html>"
					. "<!-- template $f/SSViewerTestCommentsFullSourceIfIE.ss -->"
					. "<!--[if lte IE 8]> <html class='old-ie'> <![endif]-->"
					. "<!--[if gt IE 8]> <html class='new-ie'> <![endif]-->"
					. "<!--[if !IE]><!--> <html class='no-ie'> <!--<![endif]-->"
					. "\t<head></head>"
					. "\t<body></body>"
					. "</html>"
					. "<!-- end template $f/SSViewerTestCommentsFullSourceIfIE.ss -->",
			),
			array(
				'name' => 'SSViewerTestCommentsFullSourceIfIENoDoctype',
				'expected' => ""
					. "<!--[if lte IE 8]> <html class='old-ie'> <![endif]-->"
					. "<!--[if gt IE 8]> <html class='new-ie'> <![endif]-->"
					. "<!--[if !IE]><!--> <html class='no-ie'>"
					. "<!-- template $f/SSViewerTestCommentsFullSourceIfIENoDoctype.ss -->"
					. " <!--<![endif]-->"
					. "\t<head></head>"
					. "\t<body></body>"
					. "<!-- end template $f/SSViewerTestCommentsFullSourceIfIENoDoctype.ss --></html>",
			),
			array(
				'name' => 'SSViewerTestCommentsPartialSource',
				'expected' =>
				"<!-- template $f/SSViewerTestCommentsPartialSource.ss -->"
					. "<div class='typography'></div>"
					. "<!-- end template $f/SSViewerTestCommentsPartialSource.ss -->",
			),
			array(
				'name' => 'SSViewerTestCommentsWithInclude',
				'expected' =>
				"<!-- template $f/SSViewerTestCommentsWithInclude.ss -->"
					. "<div class='typography'>"
					. "<!-- include 'SSViewerTestCommentsInclude' -->"
					. "<!-- template $f/SSViewerTestCommentsInclude.ss -->"
					. "Included"
					. "<!-- end template $f/SSViewerTestCommentsInclude.ss -->"
					. "<!-- end include 'SSViewerTestCommentsInclude' -->"
					. "</div>"
					. "<!-- end template $f/SSViewerTestCommentsWithInclude.ss -->",
			),
		);
		foreach ($templates as $template) {
			$this->_renderWithSourceFileComments($template['name'], $template['expected']);
		}
		Config::inst()->update('SSViewer', 'source_file_comments', false);
		Config::inst()->update('Director', 'environment_type', $origEnv);
	}
	private function _renderWithSourceFileComments($name, $expected) {
		$viewer = new SSViewer(array($name));
		$data = new ArrayData(array());
		$result = $viewer->process($data);
		$expected = str_replace(array("\r", "\n"), '', $expected);
		$result = str_replace(array("\r", "\n"), '', $result);
		$this->assertEquals($result, $expected);
	}

	public function testLoopIteratorIterator() {
		$list = new PaginatedList(new ArrayList());
		$viewer = new SSViewer_FromString('<% loop List %>$ID - $FirstName<br /><% end_loop %>');
		$result = $viewer->process(new ArrayData(array('List' => $list)));
		$this->assertEquals($result, '');
	}

	public function testProcessOnlyIncludesRequirementsOnce() {
		$template = new SSViewer(array('SSViewerTestProcess'));
		$basePath = dirname($this->getCurrentRelativePath()) . '/forms';

		$backend = new Requirements_Backend;
		$backend->set_combined_files_enabled(false);
		$backend->combine_files(
			'RequirementsTest_ab.css',
			array(
				$basePath . '/RequirementsTest_a.css',
				$basePath . '/RequirementsTest_b.css'
			)
		);

		Requirements::set_backend($backend);

		$this->assertEquals(1, substr_count($template->process(array()), "a.css"));
		$this->assertEquals(1, substr_count($template->process(array()), "b.css"));

		// if we disable the requirements then we should get nothing
		$template->includeRequirements(false);
		$this->assertEquals(0, substr_count($template->process(array()), "a.css"));
		$this->assertEquals(0, substr_count($template->process(array()), "b.css"));
	}

	public function testRequireCallInTemplateInclude() {
		//TODO undo skip test on the event that templates ever obtain the ability to reference MODULE_DIR (or something to that effect)
		if(FRAMEWORK_DIR === 'framework') {
			$template = new SSViewer(array('SSViewerTestProcess'));

			Requirements::set_suffix_requirements(false);

			$this->assertEquals(1, substr_count(
				$template->process(array()),
				"tests/forms/RequirementsTest_a.js"
			));
		}
		else {
			$this->markTestSkipped('Requirement will always fail if the framework dir is not '.
				'named \'framework\', since templates require hard coded paths');
		}
	}

	public function testCallsWithArguments() {
		$data = new ArrayData(array(
			'Set' => new ArrayList(array(
				new SSViewerTest_Object("1"),
				new SSViewerTest_Object("2"),
				new SSViewerTest_Object("3"),
				new SSViewerTest_Object("4"),
				new SSViewerTest_Object("5"),
			)),
			'Level' => new SSViewerTest_LevelTest(1),
			'Nest' => array(
				'Level' => new SSViewerTest_LevelTest(2),
			),
		));

		$tests = array(
			'$Level.output(1)' => '1-1',
			'$Nest.Level.output($Set.First.Number)' => '2-1',
			'<% with $Set %>$Up.Level.output($First.Number)<% end_with %>' => '1-1',
			'<% with $Set %>$Top.Nest.Level.output($First.Number)<% end_with %>' => '2-1',
			'<% loop $Set %>$Up.Nest.Level.output($Number)<% end_loop %>' => '2-12-22-32-42-5',
			'<% loop $Set %>$Top.Level.output($Number)<% end_loop %>' => '1-11-21-31-41-5',
			'<% with $Nest %>$Level.output($Top.Set.First.Number)<% end_with %>' => '2-1',
			'<% with $Level %>$output($Up.Set.Last.Number)<% end_with %>' => '1-5',
			'<% with $Level.forWith($Set.Last.Number) %>$output("hi")<% end_with %>' => '5-hi',
			'<% loop $Level.forLoop($Set.First.Number) %>$Number<% end_loop %>' => '!0',
			'<% with $Nest %>
				<% with $Level.forWith($Up.Set.First.Number) %>$output("hi")<% end_with %>
			<% end_with %>' => '1-hi',
			'<% with $Nest %>
				<% loop $Level.forLoop($Top.Set.Last.Number) %>$Number<% end_loop %>
			<% end_with %>' => '!0!1!2!3!4',
		);

		foreach($tests as $template => $expected) {
			$this->assertEquals($expected, trim($this->render($template, $data)));
		}
	}

	public function testClosedBlockExtension() {
		$count = 0;
		$parser = new SSTemplateParser();
		$parser->addClosedBlock(
			'test',
			function ($res) use (&$count) {
				$count++;
			}
		);

		$template = new SSViewer_FromString("<% test %><% end_test %>", $parser);
		$template->process(new SSViewerTestFixture());

		$this->assertEquals(1, $count);
	}

	public function testOpenBlockExtension() {
		$count = 0;
		$parser = new SSTemplateParser();
		$parser->addOpenBlock(
			'test',
			function ($res) use (&$count) {
				$count++;
			}
		);

		$template = new SSViewer_FromString("<% test %>", $parser);
		$template->process(new SSViewerTestFixture());

		$this->assertEquals(1, $count);
	}

	/**
	 * Tests if caching for SSViewer_FromString is working
	 */
	public function testFromStringCaching() {
		$content = 'Test content';
		$cacheFile = TEMP_FOLDER . '/.cache.' . sha1($content);
		if (file_exists($cacheFile)) {
			unlink($cacheFile);
		}

		// Test global behaviors
		$this->render($content, null, null);
		$this->assertFalse(file_exists($cacheFile), 'Cache file was created when caching was off');

		Config::inst()->update('SSViewer_FromString', 'cache_template', true);
		$this->render($content, null, null);
		$this->assertTrue(file_exists($cacheFile), 'Cache file wasn\'t created when it was meant to');
		unlink($cacheFile);

		// Test instance behaviors
		$this->render($content, null, false);
		$this->assertFalse(file_exists($cacheFile), 'Cache file was created when caching was off');

		$this->render($content, null, true);
		$this->assertTrue(file_exists($cacheFile), 'Cache file wasn\'t created when it was meant to');
		unlink($cacheFile);
	}
}

/**
 * A test fixture that will echo back the template item
 */
class SSViewerTestFixture extends ViewableData {
	protected $name;

	public function __construct($name = null) {
		$this->name = $name;
		parent::__construct();
	}


	private function argedName($fieldName, $arguments) {
		$childName = $this->name ? "$this->name.$fieldName" : $fieldName;
		if($arguments) return $childName . '(' . implode(',', $arguments) . ')';
		else return $childName;
	}
	public function obj($fieldName, $arguments=null, $forceReturnedObject=true, $cache=false, $cacheName=null) {
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


	public function XML_val($fieldName, $arguments = null, $cache = false) {
		if(preg_match('/NotSet/i', $fieldName)) {
			return '';
		} else if(preg_match('/Raw/i', $fieldName)) {
			return $fieldName;
		} else {
			return '[out:' . $this->argedName($fieldName, $arguments) . ']';
		}
	}

	public function hasValue($fieldName, $arguments = null, $cache = true) {
		return (bool)$this->XML_val($fieldName, $arguments);
	}
}

class SSViewerTest_ViewableData extends ViewableData implements TestOnly {

	private static $casting = array(
		'TextValue' => 'Text',
		'HTMLValue' => 'HTMLText'
	);

	public function methodWithOneArgument($arg1) {
		return "arg1:{$arg1}";
	}

	public function methodWithTwoArguments($arg1, $arg2) {
		return "arg1:{$arg1},arg2:{$arg2}";
	}
}


class SSViewerTest_Controller extends Controller {

}

class SSViewerTest_Object extends DataObject implements TestOnly {

	public $number = null;

	private static $casting = array(
		'Link' => 'Text',
	);


	public function __construct($number = null) {
		parent::__construct();
		$this->number = $number;
	}

	public function Number() {
		return $this->number;
	}

	public function absoluteBaseURL() {
		return "testLocalFunctionPriorityCalled";
	}

	public function lotsOfArguments11($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k) {
		return $a. $b. $c. $d. $e. $f. $g. $h. $i. $j. $k;
	}

	public function Link() {
		return 'some/url.html';
	}
}

class SSViewerTest_GlobalProvider implements TemplateGlobalProvider, TestOnly {

	public static function get_template_global_variables() {
		return array(
			'SSViewerTest_GlobalHTMLFragment' => array('method' => 'get_html', 'casting' => 'HTMLText'),
			'SSViewerTest_GlobalHTMLEscaped' => array('method' => 'get_html'),

			'SSViewerTest_GlobalAutomatic',
			'SSViewerTest_GlobalReferencedByString' => 'get_reference',
			'SSViewerTest_GlobalReferencedInArray' => array('method' => 'get_reference'),

			'SSViewerTest_GlobalThatTakesArguments' => array('method' => 'get_argmix', 'casting' => 'HTMLText')

		);
	}

	public static function get_html() {
		return '<div></div>';
	}

	public static function SSViewerTest_GlobalAutomatic() {
		return 'automatic';
	}

	public static function get_reference() {
		return 'reference';
	}

	public static function get_argmix() {
		$args = func_get_args();
		return 'z' . implode(':', $args) . 'z';
	}

}

class SSViewerTest_LevelTest extends ViewableData implements TestOnly {
	protected $depth;

	public function __construct($depth = 1) {
		$this->depth = $depth;
	}

	public function output($val) {
		return "$this->depth-$val";
	}

	public function forLoop($number) {
		$ret = array();
		for($i = 0; $i < (int)$number; ++$i) {
			$ret[] = new SSViewerTest_Object("!$i");
		}
		return new ArrayList($ret);
	}

	public function forWith($number) {
		return new self($number);
	}
}

