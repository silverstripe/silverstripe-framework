<?php

namespace SilverStripe\View\Tests;

use Exception;
use InvalidArgumentException;
use PHPUnit_Framework_MockObject_MockObject;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\Requirements_Minifier;
use SilverStripe\View\SSTemplateParser;
use SilverStripe\View\SSViewer;
use SilverStripe\View\SSViewer_FromString;
use SilverStripe\View\Tests\SSViewerTest\SSViewerTestModel;
use SilverStripe\View\Tests\SSViewerTest\SSViewerTestModelController;
use SilverStripe\View\ViewableData;

/**
 * @skipUpgrade
 */
class SSViewerTest extends SapphireTest
{

    /**
     * Backup of $_SERVER global
     *
     * @var array
     */
    protected $oldServer = [];

    protected static $extra_dataobjects = [
        SSViewerTest\TestObject::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        SSViewer::config()->update('source_file_comments', false);
        SSViewer_FromString::config()->update('cache_template', false);
        TestAssetStore::activate('SSViewerTest');
        $this->oldServer = $_SERVER;
    }

    protected function tearDown()
    {
        $_SERVER = $this->oldServer;
        TestAssetStore::reset();
        parent::tearDown();
    }

    /**
     * Tests for {@link Config::inst()->get('SSViewer', 'theme')} for different behaviour
     * of user defined themes via {@link SiteConfig} and default theme
     * when no user themes are defined.
     */
    public function testCurrentTheme()
    {
        SSViewer::config()->update('theme', 'mytheme');
        $this->assertEquals(
            'mytheme',
            SSViewer::config()->uninherited('theme'),
            'Current theme is the default - user has not defined one'
        );
    }

    /**
     * Tests for themes helper functions, ensuring they behave as defined in the RFC at
     * https://github.com/silverstripe/silverstripe-framework/issues/5604
     */
    public function testThemesHelpers()
    {
        // Test set_themes()
        SSViewer::set_themes(['mytheme', '$default']);
        $this->assertEquals(['mytheme', '$default'], SSViewer::get_themes());

        // Ensure add_themes() prepends
        SSViewer::add_themes(['my_more_important_theme']);
        $this->assertEquals(['my_more_important_theme', 'mytheme', '$default'], SSViewer::get_themes());

        // Ensure add_themes() on theme already in cascade promotes it to the top
        SSViewer::add_themes(['mytheme']);
        $this->assertEquals(['mytheme', 'my_more_important_theme', '$default'], SSViewer::get_themes());
    }

    /**
     * Test that a template without a <head> tag still renders.
     */
    public function testTemplateWithoutHeadRenders()
    {
        $data = new ArrayData([ 'Var' => 'var value' ]);
        $result = $data->renderWith("SSViewerTestPartialTemplate");
        $this->assertEquals('Test partial template: var value', trim(preg_replace("/<!--.*-->/U", '', $result)));
    }

    /**
     * Ensure global methods aren't executed
     */
    public function testTemplateExecution()
    {
        $data = new ArrayData([ 'Var' => 'phpinfo' ]);
        $result = $data->renderWith("SSViewerTestPartialTemplate");
        $this->assertEquals('Test partial template: phpinfo', trim(preg_replace("/<!--.*-->/U", '', $result)));
    }

    public function testIncludeScopeInheritance()
    {
        $data = $this->getScopeInheritanceTestData();
        $expected = [
        'Item 1 - First-ODD top:Item 1',
        'Item 2 - EVEN top:Item 2',
        'Item 3 - ODD top:Item 3',
        'Item 4 - EVEN top:Item 4',
        'Item 5 - ODD top:Item 5',
        'Item 6 - Last-EVEN top:Item 6',
        ];

        $result = $data->renderWith('SSViewerTestIncludeScopeInheritance');
        $this->assertExpectedStrings($result, $expected);

        // reset results for the tests that include arguments (the title is passed as an arg)
        $expected = [
        'Item 1 _ Item 1 - First-ODD top:Item 1',
        'Item 2 _ Item 2 - EVEN top:Item 2',
        'Item 3 _ Item 3 - ODD top:Item 3',
        'Item 4 _ Item 4 - EVEN top:Item 4',
        'Item 5 _ Item 5 - ODD top:Item 5',
        'Item 6 _ Item 6 - Last-EVEN top:Item 6',
        ];

        $result = $data->renderWith('SSViewerTestIncludeScopeInheritanceWithArgs');
        $this->assertExpectedStrings($result, $expected);
    }

    public function testIncludeTruthyness()
    {
        $data = new ArrayData([
            'Title' => 'TruthyTest',
            'Items' => new ArrayList([
                new ArrayData(['Title' => 'Item 1']),
                new ArrayData(['Title' => '']),
                new ArrayData(['Title' => true]),
                new ArrayData(['Title' => false]),
                new ArrayData(['Title' => null]),
                new ArrayData(['Title' => 0]),
                new ArrayData(['Title' => 7])
            ])
        ]);
        $result = $data->renderWith('SSViewerTestIncludeScopeInheritanceWithArgs');

        // We should not end up with empty values appearing as empty
        $expected = [
            'Item 1 _ Item 1 - First-ODD top:Item 1',
            'Untitled - EVEN top:',
            '1 _ 1 - ODD top:1',
            'Untitled - EVEN top:',
            'Untitled - ODD top:',
            'Untitled - EVEN top:0',
            '7 _ 7 - Last-ODD top:7',
        ];
        $this->assertExpectedStrings($result, $expected);
    }

    private function getScopeInheritanceTestData()
    {
        return new ArrayData([
            'Title' => 'TopTitleValue',
            'Items' => new ArrayList([
                new ArrayData(['Title' => 'Item 1']),
                new ArrayData(['Title' => 'Item 2']),
                new ArrayData(['Title' => 'Item 3']),
                new ArrayData(['Title' => 'Item 4']),
                new ArrayData(['Title' => 'Item 5']),
                new ArrayData(['Title' => 'Item 6'])
            ])
        ]);
    }

    private function assertExpectedStrings($result, $expected)
    {
        foreach ($expected as $expectedStr) {
            $this->assertTrue(
                (boolean) preg_match("/{$expectedStr}/", $result),
                "Didn't find '{$expectedStr}' in:\n{$result}"
            );
        }
    }

    /**
     * Small helper to render templates from strings
     *
     * @param  string $templateString
     * @param  null   $data
     * @param  bool   $cacheTemplate
     * @return string
     */
    public function render($templateString, $data = null, $cacheTemplate = false)
    {
        $t = SSViewer::fromString($templateString, $cacheTemplate);
        if (!$data) {
            $data = new SSViewerTest\TestFixture();
        }
        return trim('' . $t->process($data));
    }

    public function testRequirements()
    {
        /** @var Requirements_Backend|PHPUnit_Framework_MockObject_MockObject $requirements */
        $requirements = $this
            ->getMockBuilder(Requirements_Backend::class)
            ->setMethods(["javascript", "css"])
            ->getMock();
        $jsFile = FRAMEWORK_DIR . '/tests/forms/a.js';
        $cssFile = FRAMEWORK_DIR . '/tests/forms/a.js';

        $requirements->expects($this->once())->method('javascript')->with($jsFile);
        $requirements->expects($this->once())->method('css')->with($cssFile);

        $origReq = Requirements::backend();
        Requirements::set_backend($requirements);
        $template = $this->render(
            "<% require javascript($jsFile) %>
		<% require css($cssFile) %>"
        );
        Requirements::set_backend($origReq);

        $this->assertFalse((bool)trim($template), "Should be no content in this return.");
    }

    public function testRequirementsCombine()
    {
        /** @var Requirements_Backend $testBackend */
        $testBackend = Injector::inst()->create(Requirements_Backend::class);
        $testBackend->setSuffixRequirements(false);
        $testBackend->setCombinedFilesEnabled(true);

        //$combinedTestFilePath = BASE_PATH . '/' . $testBackend->getCombinedFilesFolder() . '/testRequirementsCombine.js';

        $jsFile = $this->getCurrentRelativePath() . '/SSViewerTest/javascript/bad.js';
        $jsFileContents = file_get_contents(BASE_PATH . '/' . $jsFile);
        $testBackend->combineFiles('testRequirementsCombine.js', [$jsFile]);

        // secondly, make sure that requirements is generated, even though minification failed
        $testBackend->processCombinedFiles();
        $js = array_keys($testBackend->getJavascript());
        $combinedTestFilePath = Director::publicFolder() . reset($js);
        $this->assertContains('_combinedfiles/testRequirementsCombine-4c0e97a.js', $combinedTestFilePath);

        // and make sure the combined content matches the input content, i.e. no loss of functionality
        if (!file_exists($combinedTestFilePath)) {
            $this->fail('No combined file was created at expected path: ' . $combinedTestFilePath);
        }
        $combinedTestFileContents = file_get_contents($combinedTestFilePath);
        $this->assertContains($jsFileContents, $combinedTestFileContents);
    }

    public function testRequirementsMinification()
    {
        /** @var Requirements_Backend $testBackend */
        $testBackend = Injector::inst()->create(Requirements_Backend::class);
        $testBackend->setSuffixRequirements(false);
        $testBackend->setMinifyCombinedFiles(true);
        $testBackend->setCombinedFilesEnabled(true);

        $testFile = $this->getCurrentRelativePath() . '/SSViewerTest/javascript/RequirementsTest_a.js';
        $testFileContent = file_get_contents($testFile);

        $mockMinifier = $this->getMockBuilder(Requirements_Minifier::class)
        ->setMethods(['minify'])
        ->getMock();

        $mockMinifier->expects($this->once())
        ->method('minify')
        ->with(
            $testFileContent,
            'js',
            $testFile
        );
        $testBackend->setMinifier($mockMinifier);
        $testBackend->combineFiles('testRequirementsMinified.js', [$testFile]);
        $testBackend->processCombinedFiles();

        $testBackend->setMinifyCombinedFiles(false);
        $mockMinifier->expects($this->never())
        ->method('minify');
        $testBackend->processCombinedFiles();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/^Cannot minify files without a minification service defined./');

        $testBackend->setMinifyCombinedFiles(true);
        $testBackend->setMinifier(null);
        $testBackend->processCombinedFiles();
    }



    public function testComments()
    {
        $input = <<<SS
This is my template<%-- this is a comment --%>This is some content<%-- this is another comment --%>Final content
<%-- Alone multi
	line comment --%>
Some more content
Mixing content and <%-- multi
	line comment --%> Final final
content
SS;
        $output = $this->render($input);
        $shouldbe = <<<SS
This is my templateThis is some contentFinal content

Some more content
Mixing content and  Final final
content
SS;
        $this->assertEquals($shouldbe, $output);
    }

    public function testBasicText()
    {
        $this->assertEquals('"', $this->render('"'), 'Double-quotes are left alone');
        $this->assertEquals("'", $this->render("'"), 'Single-quotes are left alone');
        $this->assertEquals('A', $this->render('\\A'), 'Escaped characters are unescaped');
        $this->assertEquals('\\A', $this->render('\\\\A'), 'Escaped back-slashed are correctly unescaped');
    }

    public function testBasicInjection()
    {
        $this->assertEquals('[out:Test]', $this->render('$Test'), 'Basic stand-alone injection');
        $this->assertEquals('[out:Test]', $this->render('{$Test}'), 'Basic stand-alone wrapped injection');
        $this->assertEquals('A[out:Test]!', $this->render('A$Test!'), 'Basic surrounded injection');
        $this->assertEquals('A[out:Test]B', $this->render('A{$Test}B'), 'Basic surrounded wrapped injection');

        $this->assertEquals('A$B', $this->render('A\\$B'), 'No injection as $ escaped');
        $this->assertEquals('A$ B', $this->render('A$ B'), 'No injection as $ not followed by word character');
        $this->assertEquals('A{$ B', $this->render('A{$ B'), 'No injection as {$ not followed by word character');

        $this->assertEquals('{$Test}', $this->render('{\\$Test}'), 'Escapes can be used to avoid injection');
        $this->assertEquals(
            '{\\[out:Test]}',
            $this->render('{\\\\$Test}'),
            'Escapes before injections are correctly unescaped'
        );
    }


    public function testGlobalVariableCalls()
    {
        $this->assertEquals('automatic', $this->render('$SSViewerTest_GlobalAutomatic'));
        $this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedByString'));
        $this->assertEquals('reference', $this->render('$SSViewerTest_GlobalReferencedInArray'));
    }

    public function testGlobalVariableCallsWithArguments()
    {
        $this->assertEquals('zz', $this->render('$SSViewerTest_GlobalThatTakesArguments'));
        $this->assertEquals('zFooz', $this->render('$SSViewerTest_GlobalThatTakesArguments("Foo")'));
        $this->assertEquals(
            'zFoo:Bar:Bazz',
            $this->render('$SSViewerTest_GlobalThatTakesArguments("Foo", "Bar", "Baz")')
        );
        $this->assertEquals(
            'zreferencez',
            $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalReferencedByString)')
        );
    }

    public function testGlobalVariablesAreEscaped()
    {
        $this->assertEquals('<div></div>', $this->render('$SSViewerTest_GlobalHTMLFragment'));
        $this->assertEquals('&lt;div&gt;&lt;/div&gt;', $this->render('$SSViewerTest_GlobalHTMLEscaped'));

        $this->assertEquals(
            'z<div></div>z',
            $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLFragment)')
        );
        $this->assertEquals(
            'z&lt;div&gt;&lt;/div&gt;z',
            $this->render('$SSViewerTest_GlobalThatTakesArguments($SSViewerTest_GlobalHTMLEscaped)')
        );
    }

    public function testCoreGlobalVariableCalls()
    {
        $this->assertEquals(
            Director::absoluteBaseURL(),
            $this->render('{$absoluteBaseURL}'),
            'Director::absoluteBaseURL can be called from within template'
        );
        $this->assertEquals(
            Director::absoluteBaseURL(),
            $this->render('{$AbsoluteBaseURL}'),
            'Upper-case %AbsoluteBaseURL can be called from within template'
        );

        $this->assertEquals(
            Director::is_ajax(),
            $this->render('{$isAjax}'),
            'All variations of is_ajax result in the correct call'
        );
        $this->assertEquals(
            Director::is_ajax(),
            $this->render('{$IsAjax}'),
            'All variations of is_ajax result in the correct call'
        );
        $this->assertEquals(
            Director::is_ajax(),
            $this->render('{$is_ajax}'),
            'All variations of is_ajax result in the correct call'
        );
        $this->assertEquals(
            Director::is_ajax(),
            $this->render('{$Is_ajax}'),
            'All variations of is_ajax result in the correct call'
        );

        $this->assertEquals(
            i18n::get_locale(),
            $this->render('{$i18nLocale}'),
            'i18n template functions result correct result'
        );
        $this->assertEquals(
            i18n::get_locale(),
            $this->render('{$get_locale}'),
            'i18n template functions result correct result'
        );

        $this->assertEquals(
            (string)Security::getCurrentUser(),
            $this->render('{$CurrentMember}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            (string)Security::getCurrentUser(),
            $this->render('{$CurrentUser}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            (string)Security::getCurrentUser(),
            $this->render('{$currentMember}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            (string)Security::getCurrentUser(),
            $this->render('{$currentUser}'),
            'Member template functions result correct result'
        );

        $this->assertEquals(
            SecurityToken::getSecurityID(),
            $this->render('{$getSecurityID}'),
            'SecurityToken template functions result correct result'
        );
        $this->assertEquals(
            SecurityToken::getSecurityID(),
            $this->render('{$SecurityID}'),
            'SecurityToken template functions result correct result'
        );

        $this->assertEquals(
            Permission::check("ADMIN"),
            (bool)$this->render('{$HasPerm(\'ADMIN\')}'),
            'Permissions template functions result correct result'
        );
        $this->assertEquals(
            Permission::check("ADMIN"),
            (bool)$this->render('{$hasPerm(\'ADMIN\')}'),
            'Permissions template functions result correct result'
        );
    }

    public function testNonFieldCastingHelpersNotUsedInHasValue()
    {
        // check if Link without $ in front of variable
        $result = $this->render(
            'A<% if Link %>$Link<% end_if %>B',
            new SSViewerTest\TestObject()
        );
        $this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if Link %>');

        // check if Link with $ in front of variable
        $result = $this->render(
            'A<% if $Link %>$Link<% end_if %>B',
            new SSViewerTest\TestObject()
        );
        $this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if $Link %>');
    }

    public function testLocalFunctionsTakePriorityOverGlobals()
    {
        $data = new ArrayData([
            'Page' => new SSViewerTest\TestObject()
        ]);

        //call method with lots of arguments
        $result = $this->render(
            '<% with Page %>$lotsOfArguments11("a","b","c","d","e","f","g","h","i","j","k")<% end_with %>',
            $data
        );
        $this->assertEquals("abcdefghijk", $result, "public function can accept up to 11 arguments");

        //call method that does not exist
        $result = $this->render('<% with Page %><% if IDoNotExist %>hello<% end_if %><% end_with %>', $data);
        $this->assertEquals("", $result, "Method does not exist - empty result");

        //call if that does not exist
        $result = $this->render('<% with Page %>$IDoNotExist("hello")<% end_with %>', $data);
        $this->assertEquals("", $result, "Method does not exist - empty result");

        //call method with same name as a global method (local call should take priority)
        $result = $this->render('<% with Page %>$absoluteBaseURL<% end_with %>', $data);
        $this->assertEquals(
            "testLocalFunctionPriorityCalled",
            $result,
            "Local Object's public function called. Did not return the actual baseURL of the current site"
        );
    }

    public function testCurrentScopeLoopWith()
    {
        // Data to run the loop tests on - one sequence of three items, each with a subitem
        $data = new ArrayData([
            'Foo' => new ArrayList([
                'Subocean' => new ArrayData([
                    'Name' => 'Higher'
                ]),
                new ArrayData([
                    'Sub' => new ArrayData([
                        'Name' => 'SubKid1'
                    ])
                ]),
                new ArrayData([
                    'Sub' => new ArrayData([
                        'Name' => 'SubKid2'
                    ])
                ]),
                new SSViewerTest\TestObject('Number6')
            ])
        ]);

        $result = $this->render(
            '<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',
            $data
        );
        $this->assertEquals("SubKid1SubKid2Number6", $result, "Loop works");

        $result = $this->render(
            '<% loop Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %><% end_if %><% end_loop %>',
            $data
        );
        $this->assertEquals("SubKid1SubKid2Number6", $result, "Loop works");

        $result = $this->render('<% with Foo %>$Count<% end_with %>', $data);
        $this->assertEquals("4", $result, "4 items in the DataObjectSet");

        $result = $this->render(
            '<% with Foo %><% loop Up.Foo %>$Number<% if Sub %><% with Sub %>$Name<% end_with %>'
            . '<% end_if %><% end_loop %><% end_with %>',
            $data
        );
        $this->assertEquals("SubKid1SubKid2Number6", $result, "Loop in with Up.Foo scope works");

        $result = $this->render(
            '<% with Foo %><% loop %>$Number<% if Sub %><% with Sub %>$Name<% end_with %>'
            . '<% end_if %><% end_loop %><% end_with %>',
            $data
        );
        $this->assertEquals("SubKid1SubKid2Number6", $result, "Loop in current scope works");
    }

    public function testObjectDotArguments()
    {
        $this->assertEquals(
            '[out:TestObject.methodWithOneArgument(one)]
				[out:TestObject.methodWithTwoArguments(one,two)]
				[out:TestMethod(Arg1,Arg2).Bar.Val]
				[out:TestMethod(Arg1,Arg2).Bar]
				[out:TestMethod(Arg1,Arg2)]
				[out:TestMethod(Arg1).Bar.Val]
				[out:TestMethod(Arg1).Bar]
				[out:TestMethod(Arg1)]',
            $this->render(
                '$TestObject.methodWithOneArgument(one)
				$TestObject.methodWithTwoArguments(one,two)
				$TestMethod(Arg1, Arg2).Bar.Val
				$TestMethod(Arg1, Arg2).Bar
				$TestMethod(Arg1, Arg2)
				$TestMethod(Arg1).Bar.Val
				$TestMethod(Arg1).Bar
				$TestMethod(Arg1)'
            )
        );
    }

    public function testEscapedArguments()
    {
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
            $this->render(
                '{$Foo(Arg1, Arg2).Bar.Val}.Suffix
				{$Foo(Arg1, Arg2).Val}_Suffix
				{$Foo(Arg1, Arg2)}/Suffix
				{$Foo(Arg1).Bar.Val}textSuffix
				{$Foo(Arg1).Bar}.Suffix
				{$Foo(Arg1)}.Suffix
				{$Foo.Bar.Val}.Suffix
				{$Foo.Bar}.Suffix
				{$Foo}.Suffix'
            )
        );
    }

    public function testLoopWhitespace()
    {
        $this->assertEquals(
            'before[out:SingleItem.Test]after
				beforeTestafter',
            $this->render(
                'before<% loop SingleItem %>$Test<% end_loop %>after
				before<% loop SingleItem %>Test<% end_loop %>after'
            )
        );

        // The control tags are removed from the output, but no whitespace
        // This is a quirk that could be changed, but included in the test to make the current
        // behaviour explicit
        $this->assertEquals(
            'before

[out:SingleItem.ItemOnItsOwnLine]

after',
            $this->render(
                'before
<% loop SingleItem %>
$ItemOnItsOwnLine
<% end_loop %>
after'
            )
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
            $this->render(
                'before
<% loop Loop3 %>
$ItemOnItsOwnLine
<% end_loop %>
after'
            )
        );
    }

    public function testControls()
    {
        // Single item controls
        $this->assertEquals(
            'a[out:Foo.Bar.Item]b
				[out:Foo.Bar(Arg1).Item]
				[out:Foo(Arg1).Item]
				[out:Foo(Arg1,Arg2).Item]
				[out:Foo(Arg1,Arg2,Arg3).Item]',
            $this->render(
                '<% with Foo.Bar %>a{$Item}b<% end_with %>
				<% with Foo.Bar(Arg1) %>$Item<% end_with %>
				<% with Foo(Arg1) %>$Item<% end_with %>
				<% with Foo(Arg1, Arg2) %>$Item<% end_with %>
				<% with Foo(Arg1, Arg2, Arg3) %>$Item<% end_with %>'
            )
        );

        // Loop controls
        $this->assertEquals(
            'a[out:Foo.Loop2.Item]ba[out:Foo.Loop2.Item]b',
            $this->render('<% loop Foo.Loop2 %>a{$Item}b<% end_loop %>')
        );

        $this->assertEquals(
            '[out:Foo.Loop2(Arg1).Item][out:Foo.Loop2(Arg1).Item]',
            $this->render('<% loop Foo.Loop2(Arg1) %>$Item<% end_loop %>')
        );

        $this->assertEquals(
            '[out:Loop2(Arg1).Item][out:Loop2(Arg1).Item]',
            $this->render('<% loop Loop2(Arg1) %>$Item<% end_loop %>')
        );

        $this->assertEquals(
            '[out:Loop2(Arg1,Arg2).Item][out:Loop2(Arg1,Arg2).Item]',
            $this->render('<% loop Loop2(Arg1, Arg2) %>$Item<% end_loop %>')
        );

        $this->assertEquals(
            '[out:Loop2(Arg1,Arg2,Arg3).Item][out:Loop2(Arg1,Arg2,Arg3).Item]',
            $this->render('<% loop Loop2(Arg1, Arg2, Arg3) %>$Item<% end_loop %>')
        );
    }

    public function testIfBlocks()
    {
        // Basic test
        $this->assertEquals(
            'AC',
            $this->render('A<% if NotSet %>B$NotSet<% end_if %>C')
        );

        // Nested test
        $this->assertEquals(
            'AB1C',
            $this->render('A<% if IsSet %>B$NotSet<% if IsSet %>1<% else %>2<% end_if %><% end_if %>C')
        );

        // else_if
        $this->assertEquals(
            'ACD',
            $this->render('A<% if NotSet %>B<% else_if IsSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ADE',
            $this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% else_if IsSet %>D<% end_if %>E')
        );

        $this->assertEquals(
            'ADE',
            $this->render('A<% if NotSet %>B<% else_if AlsoNotset %>C<% else_if IsSet %>D<% end_if %>E')
        );

        // Dot syntax
        $this->assertEquals(
            'ACD',
            $this->render('A<% if Foo.NotSet %>B<% else_if Foo.IsSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if Foo.Bar.NotSet %>B<% else_if Foo.Bar.IsSet %>C<% end_if %>D')
        );

        // Params
        $this->assertEquals(
            'ACD',
            $this->render('A<% if NotSet(Param) %>B<% else %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ABD',
            $this->render('A<% if IsSet(Param) %>B<% else %>C<% end_if %>D')
        );

        // Negation
        $this->assertEquals(
            'AC',
            $this->render('A<% if not IsSet %>B<% end_if %>C')
        );
        $this->assertEquals(
            'ABC',
            $this->render('A<% if not NotSet %>B<% end_if %>C')
        );

        // Or
        $this->assertEquals(
            'ABD',
            $this->render('A<% if IsSet || NotSet %>B<% else_if A %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if NotSet || AlsoNotSet %>B<% else_if IsSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet || AlsoNotSet %>B<% else_if NotSet3 %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if NotSet || AlsoNotSet %>B<% else_if IsSet || NotSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet || AlsoNotSet %>B<% else_if NotSet2 || NotSet3 %>C<% end_if %>D')
        );

        // Negated Or
        $this->assertEquals(
            'ACD',
            $this->render('A<% if not IsSet || AlsoNotSet %>B<% else_if A %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ABD',
            $this->render('A<% if not NotSet || AlsoNotSet %>B<% else_if A %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ABD',
            $this->render('A<% if NotSet || not AlsoNotSet %>B<% else_if A %>C<% end_if %>D')
        );

        // And
        $this->assertEquals(
            'ABD',
            $this->render('A<% if IsSet && AlsoSet %>B<% else_if A %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if IsSet && NotSet %>B<% else_if IsSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet && NotSet2 %>B<% else_if NotSet3 %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if IsSet && NotSet %>B<% else_if IsSet && AlsoSet %>C<% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet && NotSet2 %>B<% else_if IsSet && NotSet3 %>C<% end_if %>D')
        );

        // Equality
        $this->assertEquals(
            'ABC',
            $this->render('A<% if RawVal == RawVal %>B<% end_if %>C')
        );
        $this->assertEquals(
            'ACD',
            $this->render('A<% if Right == Wrong %>B<% else_if RawVal == RawVal %>C<% end_if %>D')
        );
        $this->assertEquals(
            'ABC',
            $this->render('A<% if Right != Wrong %>B<% end_if %>C')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if Right == Wrong %>B<% else_if RawVal != RawVal %>C<% end_if %>D')
        );

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
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet %><% else_if IsSet %><% else %><% end_if %>D')
        );
        $this->assertEquals(
            'AD',
            $this->render('A<% if NotSet %><% else_if AlsoNotSet %><% else %><% end_if %>D')
        );

        // Bare words with ending space
        $this->assertEquals(
            'ABC',
            $this->render('A<% if "RawVal" == RawVal %>B<% end_if %>C')
        );

        // Else
        $this->assertEquals(
            'ADE',
            $this->render('A<% if Right == Wrong %>B<% else_if RawVal != RawVal %>C<% else %>D<% end_if %>E')
        );

        // Empty if with else
        $this->assertEquals(
            'ABC',
            $this->render('A<% if NotSet %><% else %>B<% end_if %>C')
        );
    }

    public function testBaseTagGeneration()
    {
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
        $this->assertRegExp(
            '/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
            $this->render($tmpl2)
        );


        $tmpl3 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
        $this->assertRegExp(
            '/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
            $this->render($tmpl3)
        );

        // Check that the content negotiator converts to the equally legal formats
        $negotiator = new ContentNegotiator();

        $response = new HTTPResponse($this->render($tmpl1));
        $negotiator->html($response);
        $this->assertRegExp(
            '/<head><base href=".*"><!--\[if lte IE 6\]><\/base><!\[endif\]--><\/head>/',
            $response->getBody()
        );

        $response = new HTTPResponse($this->render($tmpl1));
        $negotiator->xhtml($response);
        $this->assertRegExp('/<head><base href=".*" \/><\/head>/', $response->getBody());
    }

    public function testIncludeWithArguments()
    {
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
            $this->render(
                '<% include SSViewerTestIncludeWithArguments Arg1="A", Arg2=$B %>',
                new ArrayData(['B' => 'Bar'])
            ),
            '<p>A</p><p>Bar</p>'
        );

        $this->assertEquals(
            $this->render(
                '<% include SSViewerTestIncludeWithArguments Arg1="A" %>',
                new ArrayData(['Arg1' => 'Foo', 'Arg2' => 'Bar'])
            ),
            '<p>A</p><p>Bar</p>'
        );

        $this->assertEquals(
            $this->render(
                '<% include SSViewerTestIncludeScopeInheritanceWithArgsInLoop Title="SomeArg" %>',
                new ArrayData(
                    ['Items' => new ArrayList(
                        [
                        new ArrayData(['Title' => 'Foo']),
                        new ArrayData(['Title' => 'Bar'])
                        ]
                    )]
                )
            ),
            'SomeArg - Foo - Bar - SomeArg'
        );

        $this->assertEquals(
            $this->render(
                '<% include SSViewerTestIncludeScopeInheritanceWithArgsInWith Title="A" %>',
                new ArrayData(['Item' => new ArrayData(['Title' =>'B'])])
            ),
            'A - B - A'
        );

        $this->assertEquals(
            $this->render(
                '<% include SSViewerTestIncludeScopeInheritanceWithArgsInNestedWith Title="A" %>',
                new ArrayData(
                    [
                    'Item' => new ArrayData(
                        [
                        'Title' =>'B', 'NestedItem' => new ArrayData(['Title' => 'C'])
                        ]
                    )]
                )
            ),
            'A - B - C - B - A'
        );

        $this->assertEquals(
            $this->render(
                '<% include SSViewerTestIncludeScopeInheritanceWithUpAndTop Title="A" %>',
                new ArrayData(
                    [
                    'Item' => new ArrayData(
                        [
                        'Title' =>'B', 'NestedItem' => new ArrayData(['Title' => 'C'])
                        ]
                    )]
                )
            ),
            'A - A - A'
        );

        $data = new ArrayData(
            [
            'Nested' => new ArrayData(
                [
                'Object' => new ArrayData(['Key' => 'A'])
                ]
            ),
            'Object' => new ArrayData(['Key' => 'B'])
            ]
        );

        $tmpl = SSViewer::fromString('<% include SSViewerTestIncludeObjectArguments A=$Nested.Object, B=$Object %>');
        $res  = $tmpl->process($data);
        $this->assertEqualIgnoringWhitespace('A B', $res, 'Objects can be passed as named arguments');
    }

    public function testNamespaceInclude()
    {
        $data = new ArrayData([]);

        $this->assertEquals(
            "tests:( NamespaceInclude\n )",
            $this->render('tests:( <% include Namespace\NamespaceInclude %> )', $data),
            'Backslashes work for namespace references in includes'
        );

        $this->assertEquals(
            "tests:( NamespaceInclude\n )",
            $this->render('tests:( <% include Namespace\\NamespaceInclude %> )', $data),
            'Escaped backslashes work for namespace references in includes'
        );

        $this->assertEquals(
            "tests:( NamespaceInclude\n )",
            $this->render('tests:( <% include Namespace/NamespaceInclude %> )', $data),
            'Forward slashes work for namespace references in includes'
        );
    }

    /**
     * Test search for includes fallback to non-includes folder
     */
    public function testIncludeFallbacks()
    {
        $data = new ArrayData([]);

        $this->assertEquals(
            "tests:( Namespace/Includes/IncludedTwice.ss\n )",
            $this->render('tests:( <% include Namespace\\IncludedTwice %> )', $data),
            'Prefer Includes in the Includes folder'
        );

        $this->assertEquals(
            "tests:( Namespace/Includes/IncludedOnceSub.ss\n )",
            $this->render('tests:( <% include Namespace\\IncludedOnceSub %> )', $data),
            'Includes in only Includes folder can be found'
        );

        $this->assertEquals(
            "tests:( Namespace/IncludedOnceBase.ss\n )",
            $this->render('tests:( <% include Namespace\\IncludedOnceBase %> )', $data),
            'Includes outside of Includes folder can be found'
        );
    }

    public function testRecursiveInclude()
    {
        $view = new SSViewer(['Includes/SSViewerTestRecursiveInclude']);

        $data = new ArrayData(
            [
            'Title' => 'A',
            'Children' => new ArrayList(
                [
                new ArrayData(
                    [
                    'Title' => 'A1',
                    'Children' => new ArrayList(
                        [
                        new ArrayData([ 'Title' => 'A1 i', ]),
                        new ArrayData([ 'Title' => 'A1 ii', ]),
                        ]
                    ),
                    ]
                ),
                new ArrayData([ 'Title' => 'A2', ]),
                new ArrayData([ 'Title' => 'A3', ]),
                ]
            ),
            ]
        );

        $result = $view->process($data);
        // We don't care about whitespace
        $rationalisedResult = trim(preg_replace('/\s+/', ' ', $result));

        $this->assertEquals('A A1 A1 i A1 ii A2 A3', $rationalisedResult);
    }

    public function assertEqualIgnoringWhitespace($a, $b, $message = '')
    {
        $this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b), $message);
    }

    /**
     * See {@link ViewableDataTest} for more extensive casting tests,
     * this test just ensures that basic casting is correctly applied during template parsing.
     */
    public function testCastingHelpers()
    {
        $vd = new SSViewerTest\TestViewableData();
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

        // Uncasted value (falls back to ViewableData::$default_cast="Text")
        $vd = new SSViewerTest\TestViewableData();
        $vd->UncastedValue = '<b>html</b>';
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $t = SSViewer::fromString('$UncastedValue')->process($vd)
        );
        $this->assertEquals(
            '<b>html</b>',
            $t = SSViewer::fromString('$UncastedValue.RAW')->process($vd)
        );
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $t = SSViewer::fromString('$UncastedValue.XML')->process($vd)
        );
    }

    public function testSSViewerBasicIteratorSupport()
    {
        $data = new ArrayData(
            [
            'Set' => new ArrayList(
                [
                new SSViewerTest\TestObject("1"),
                new SSViewerTest\TestObject("2"),
                new SSViewerTest\TestObject("3"),
                new SSViewerTest\TestObject("4"),
                new SSViewerTest\TestObject("5"),
                new SSViewerTest\TestObject("6"),
                new SSViewerTest\TestObject("7"),
                new SSViewerTest\TestObject("8"),
                new SSViewerTest\TestObject("9"),
                new SSViewerTest\TestObject("10"),
                ]
            )
            ]
        );

        //base test
        $result = $this->render('<% loop Set %>$Number<% end_loop %>', $data);
        $this->assertEquals("12345678910", $result, "Numbers rendered in order");

        //test First
        $result = $this->render('<% loop Set %><% if First %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("1", $result, "Only the first number is rendered");

        //test Last
        $result = $this->render('<% loop Set %><% if Last %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("10", $result, "Only the last number is rendered");

        //test Even
        $result = $this->render('<% loop Set %><% if Even() %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even with quotes
        $result = $this->render('<% loop Set %><% if Even("1") %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even without quotes
        $result = $this->render('<% loop Set %><% if Even(1) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even with zero-based start index
        $result = $this->render('<% loop Set %><% if Even("0") %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("13579", $result, "Even (with zero-based index) numbers rendered in order");

        //test Odd
        $result = $this->render('<% loop Set %><% if Odd %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("13579", $result, "Odd numbers rendered in order");

        //test FirstLast
        $result = $this->render('<% loop Set %><% if FirstLast %>$Number$FirstLast<% end_if %><% end_loop %>', $data);
        $this->assertEquals("1first10last", $result, "First and last numbers rendered in order");

        //test Middle
        $result = $this->render('<% loop Set %><% if Middle %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("23456789", $result, "Middle numbers rendered in order");

        //test MiddleString
        $result = $this->render(
            '<% loop Set %><% if MiddleString == "middle" %>$Number$MiddleString<% end_if %>'
            . '<% end_loop %>',
            $data
        );
        $this->assertEquals(
            "2middle3middle4middle5middle6middle7middle8middle9middle",
            $result,
            "Middle numbers rendered in order"
        );

        //test EvenOdd
        $result = $this->render('<% loop Set %>$EvenOdd<% end_loop %>', $data);
        $this->assertEquals(
            "oddevenoddevenoddevenoddevenoddeven",
            $result,
            "Even and Odd is returned in sequence numbers rendered in order"
        );

        //test Pos
        $result = $this->render('<% loop Set %>$Pos<% end_loop %>', $data);
        $this->assertEquals("12345678910", $result, '$Pos is rendered in order');

        //test Pos
        $result = $this->render('<% loop Set %>$Pos(0)<% end_loop %>', $data);
        $this->assertEquals("0123456789", $result, '$Pos(0) is rendered in order');

        //test FromEnd
        $result = $this->render('<% loop Set %>$FromEnd<% end_loop %>', $data);
        $this->assertEquals("10987654321", $result, '$FromEnd is rendered in order');

        //test FromEnd
        $result = $this->render('<% loop Set %>$FromEnd(0)<% end_loop %>', $data);
        $this->assertEquals("9876543210", $result, '$FromEnd(0) rendered in order');

        //test Total
        $result = $this->render('<% loop Set %>$TotalItems<% end_loop %>', $data);
        $this->assertEquals("10101010101010101010", $result, "10 total items X 10 are returned");

        //test Modulus
        $result = $this->render('<% loop Set %>$Modulus(2,1)<% end_loop %>', $data);
        $this->assertEquals("1010101010", $result, "1-indexed pos modular divided by 2 rendered in order");

        //test MultipleOf 3
        $result = $this->render('<% loop Set %><% if MultipleOf(3) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("369", $result, "Only numbers that are multiples of 3 are returned");

        //test MultipleOf 4
        $result = $this->render('<% loop Set %><% if MultipleOf(4) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("48", $result, "Only numbers that are multiples of 4 are returned");

        //test MultipleOf 5
        $result = $this->render('<% loop Set %><% if MultipleOf(5) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("510", $result, "Only numbers that are multiples of 5 are returned");

        //test MultipleOf 10
        $result = $this->render('<% loop Set %><% if MultipleOf(10,1) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("10", $result, "Only numbers that are multiples of 10 (with 1-based indexing) are returned");

        //test MultipleOf 9 zero-based
        $result = $this->render('<% loop Set %><% if MultipleOf(9,0) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals(
            "110",
            $result,
            "Only numbers that are multiples of 9 with zero-based indexing are returned. (The first and last item)"
        );

        //test MultipleOf 11
        $result = $this->render('<% loop Set %><% if MultipleOf(11) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("", $result, "Only numbers that are multiples of 11 are returned. I.e. nothing returned");
    }

    /**
     * Test $Up works when the scope $Up refers to was entered with a "with" block
     */
    public function testUpInWith()
    {

        // Data to run the loop tests on - three levels deep
        $data = new ArrayData(
            [
            'Name' => 'Top',
            'Foo' => new ArrayData(
                [
                'Name' => 'Foo',
                'Bar' => new ArrayData(
                    [
                    'Name' => 'Bar',
                    'Baz' => new ArrayData(
                        [
                        'Name' => 'Baz'
                        ]
                    ),
                    'Qux' => new ArrayData(
                        [
                        'Name' => 'Qux'
                        ]
                    )
                    ]
                )
                ]
            )
            ]
        );

        // Basic functionality
        $this->assertEquals(
            'BarFoo',
            $this->render('<% with Foo %><% with Bar %>{$Name}{$Up.Name}<% end_with %><% end_with %>', $data)
        );

        // Two level with block, up refers to internally referenced Bar
        $this->assertEquals(
            'BarFoo',
            $this->render('<% with Foo.Bar %>{$Name}{$Up.Name}<% end_with %>', $data)
        );

        // Stepping up & back down the scope tree
        $this->assertEquals(
            'BazBarQux',
            $this->render('<% with Foo.Bar.Baz %>{$Name}{$Up.Name}{$Up.Qux.Name}<% end_with %>', $data)
        );

        // Using $Up in a with block
        $this->assertEquals(
            'BazBarQux',
            $this->render(
                '<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}{$Qux.Name}<% end_with %>'
                . '<% end_with %>',
                $data
            )
        );

        // Stepping up & back down the scope tree with with blocks
        $this->assertEquals(
            'BazBarQuxBarBaz',
            $this->render(
                '<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}<% with Qux %>{$Name}<% end_with %>'
                . '{$Name}<% end_with %>{$Name}<% end_with %>',
                $data
            )
        );

        // Using $Up.Up, where first $Up points to a previous scope entered using $Up, thereby skipping up to Foo
        $this->assertEquals(
            'Foo',
            $this->render(
                '<% with Foo.Bar.Baz %><% with Up %><% with Qux %>{$Up.Up.Name}<% end_with %><% end_with %>'
                . '<% end_with %>',
                $data
            )
        );

        // Using $Up.Up, where first $Up points to an Up used in a local scope lookup, should still skip to Foo
        $this->assertEquals(
            'Foo',
            $this->render('<% with Foo.Bar.Baz.Up.Qux %>{$Up.Up.Name}<% end_with %>', $data)
        );
    }

    /**
     * Test $Up works when the scope $Up refers to was entered with a "loop" block
     */
    public function testUpInLoop()
    {

        // Data to run the loop tests on - one sequence of three items, each with a subitem
        $data = new ArrayData(
            [
            'Name' => 'Top',
            'Foo' => new ArrayList(
                [
                new ArrayData(
                    [
                    'Name' => '1',
                    'Sub' => new ArrayData(
                        [
                        'Name' => 'Bar'
                        ]
                    )
                    ]
                ),
                new ArrayData(
                    [
                    'Name' => '2',
                    'Sub' => new ArrayData(
                        [
                        'Name' => 'Baz'
                        ]
                    )
                    ]
                ),
                new ArrayData(
                    [
                    'Name' => '3',
                    'Sub' => new ArrayData(
                        [
                        'Name' => 'Qux'
                        ]
                    )
                    ]
                )
                ]
            )
            ]
        );

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
    public function testNestedLoops()
    {

        // Data to run the loop tests on - one sequence of three items, one with child elements
        // (of a different size to the main sequence)
        $data = new ArrayData(
            [
            'Foo' => new ArrayList(
                [
                new ArrayData(
                    [
                    'Name' => '1',
                    'Children' => new ArrayList(
                        [
                        new ArrayData(
                            [
                            'Name' => 'a'
                            ]
                        ),
                        new ArrayData(
                            [
                            'Name' => 'b'
                            ]
                        ),
                        ]
                    ),
                    ]
                ),
                new ArrayData(
                    [
                    'Name' => '2',
                    'Children' => new ArrayList(),
                    ]
                ),
                new ArrayData(
                    [
                    'Name' => '3',
                    'Children' => new ArrayList(),
                    ]
                ),
                ]
            ),
            ]
        );

        // Make sure that including a loop inside a loop will not destroy the internal count of
        // items, checked by using "Last"
        $this->assertEqualIgnoringWhitespace(
            '1ab23last',
            $this->render(
                '<% loop $Foo %>$Name<% loop Children %>$Name<% end_loop %><% if Last %>last<% end_if %>'
                . '<% end_loop %>',
                $data
            )
        );
    }

    public function testLayout()
    {
        $this->useTestTheme(
            __DIR__ . '/SSViewerTest',
            'layouttest',
            function () {
                $template = new SSViewer(['Page']);
                $this->assertEquals("Foo\n\n", $template->process(new ArrayData([])));

                $template = new SSViewer(['Shortcodes', 'Page']);
                $this->assertEquals("[file_link]\n\n", $template->process(new ArrayData([])));
            }
        );
    }

    /**
     * @covers \SilverStripe\View\SSViewer::get_templates_by_class()
     */
    public function testGetTemplatesByClass()
    {
        $this->useTestTheme(
            __DIR__ . '/SSViewerTest',
            'layouttest',
            function () {
            // Test passing a string
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModelController::class,
                    '',
                    Controller::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    SSViewerTestModel::class,
                    Controller::class,
                    [
                        'type' => 'Includes',
                        Controller::class,
                    ],
                    ],
                    $templates
                );

            // Test to ensure we're stopping at the base class.
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModelController::class,
                    '',
                    SSViewerTestModelController::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    SSViewerTestModel::class,
                    ],
                    $templates
                );

            // Make sure we can search templates by suffix.
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModel::class,
                    'Controller',
                    DataObject::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    DataObject::class . 'Controller',
                    [
                        'type' => 'Includes',
                        DataObject::class . 'Controller',
                    ],
                    ],
                    $templates
                );

                // Let's throw something random in there.
                $this->expectException(InvalidArgumentException::class);
                SSViewer::get_templates_by_class(null);
            }
        );
    }

    public function testRewriteHashlinks()
    {
        SSViewer::setRewriteHashLinksDefault(true);

        $_SERVER['HTTP_HOST'] = 'www.mysite.com';
        $_SERVER['REQUEST_URI'] = '//file.com?foo"onclick="alert(\'xss\')""';

        // Emulate SSViewer::process()
        // Note that leading double slashes have been rewritten to prevent these being mis-interepreted
        // as protocol-less absolute urls
        $base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

        $tmplFile = TEMP_PATH . DIRECTORY_SEPARATOR . 'SSViewerTest_testRewriteHashlinks_' . sha1(rand()) . '.ss';

        // Note: SSViewer_FromString doesn't rewrite hash links.
        file_put_contents(
            $tmplFile,
            '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
				$ExternalInsertedLink
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<svg><use xlink:href="#sprite"></use></svg>
				<body>
			</html>'
        );
        $tmpl = new SSViewer($tmplFile);
        $obj = new ViewableData();
        $obj->InsertedLink = DBField::create_field(
            'HTMLFragment',
            '<a class="inserted" href="#anchor">InsertedLink</a>'
        );
        $obj->ExternalInsertedLink = DBField::create_field(
            'HTMLFragment',
            '<a class="external-inserted" href="http://google.com#anchor">ExternalInsertedLink</a>'
        );
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
    }

    public function testRewriteHashlinksInPhpMode()
    {
        SSViewer::setRewriteHashLinksDefault('php');

        $tmplFile = TEMP_PATH . DIRECTORY_SEPARATOR . 'SSViewerTest_testRewriteHashlinksInPhpMode_' . sha1(rand()) . '.ss';

        // Note: SSViewer_FromString doesn't rewrite hash links.
        file_put_contents(
            $tmplFile,
            '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body>
				<a class="inline" href="#anchor">InlineLink</a>
				$InsertedLink
				<svg><use xlink:href="#sprite"></use></svg>
				<body>
			</html>'
        );
        $tmpl = new SSViewer($tmplFile);
        $obj = new ViewableData();
        $obj->InsertedLink = DBField::create_field(
            'HTMLFragment',
            '<a class="inserted" href="#anchor">InsertedLink</a>'
        );
        $result = $tmpl->process($obj);

        $code = <<<'EOC'
<a class="inserted" href="<?php echo \SilverStripe\Core\Convert::raw2att(preg_replace("/^(\/)+/", "/", $_SERVER['REQUEST_URI'])); ?>#anchor">InsertedLink</a>
EOC;
        $this->assertContains($code, $result);
        // TODO Fix inline links in PHP mode
        // $this->assertContains(
        //  '<a class="inline" href="<?php echo str_replace(',
        //  $result
        // );
        $this->assertContains(
            '<svg><use xlink:href="#sprite"></use></svg>',
            $result,
            'SSTemplateParser should only rewrite anchor hrefs'
        );

        unlink($tmplFile);
    }

    public function testRenderWithSourceFileComments()
    {
        SSViewer::config()->update('source_file_comments', true);
        $i = __DIR__ . '/SSViewerTest/templates/Includes';
        $f = __DIR__ . '/SSViewerTest/templates/SSViewerTestComments';
        $templates = [
        [
            'name' => 'SSViewerTestCommentsFullSource',
            'expected' => ""
                . "<!doctype html>"
                . "<!-- template $f/SSViewerTestCommentsFullSource.ss -->"
                . "<html>"
                . "\t<head></head>"
                . "\t<body></body>"
                . "</html>"
                . "<!-- end template $f/SSViewerTestCommentsFullSource.ss -->",
        ],
        [
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
        ],
        [
            'name' => 'SSViewerTestCommentsFullSourceNoDoctype',
            'expected' => ""
                . "<html><!-- template $f/SSViewerTestCommentsFullSourceNoDoctype.ss -->"
                . "\t<head></head>"
                . "\t<body></body>"
                . "<!-- end template $f/SSViewerTestCommentsFullSourceNoDoctype.ss --></html>",
        ],
        [
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
        ],
        [
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
        ],
        [
            'name' => 'SSViewerTestCommentsPartialSource',
            'expected' =>
            "<!-- template $f/SSViewerTestCommentsPartialSource.ss -->"
                . "<div class='typography'></div>"
                . "<!-- end template $f/SSViewerTestCommentsPartialSource.ss -->",
        ],
        [
            'name' => 'SSViewerTestCommentsWithInclude',
            'expected' =>
            "<!-- template $f/SSViewerTestCommentsWithInclude.ss -->"
                . "<div class='typography'>"
                . "<!-- include 'SSViewerTestCommentsInclude' -->"
                . "<!-- template $i/SSViewerTestCommentsInclude.ss -->"
                . "Included"
                . "<!-- end template $i/SSViewerTestCommentsInclude.ss -->"
                . "<!-- end include 'SSViewerTestCommentsInclude' -->"
                . "</div>"
                . "<!-- end template $f/SSViewerTestCommentsWithInclude.ss -->",
        ],
        ];
        foreach ($templates as $template) {
            $this->_renderWithSourceFileComments('SSViewerTestComments/' . $template['name'], $template['expected']);
        }
    }
    private function _renderWithSourceFileComments($name, $expected)
    {
        $viewer = new SSViewer([$name]);
        $data = new ArrayData([]);
        $result = $viewer->process($data);
        $expected = str_replace(["\r", "\n"], '', $expected);
        $result = str_replace(["\r", "\n"], '', $result);
        $this->assertEquals($result, $expected);
    }

    public function testLoopIteratorIterator()
    {
        $list = new PaginatedList(new ArrayList());
        $viewer = new SSViewer_FromString('<% loop List %>$ID - $FirstName<br /><% end_loop %>');
        $result = $viewer->process(new ArrayData(['List' => $list]));
        $this->assertEquals($result, '');
    }

    public function testProcessOnlyIncludesRequirementsOnce()
    {
        $template = new SSViewer(['SSViewerTestProcess']);
        $basePath = $this->getCurrentRelativePath() . '/SSViewerTest';

        $backend = Injector::inst()->create(Requirements_Backend::class);
        $backend->setCombinedFilesEnabled(false);
        $backend->combineFiles(
            'RequirementsTest_ab.css',
            [
            $basePath . '/css/RequirementsTest_a.css',
            $basePath . '/css/RequirementsTest_b.css'
            ]
        );

        Requirements::set_backend($backend);

        $this->assertEquals(1, substr_count($template->process(new ViewableData()), "a.css"));
        $this->assertEquals(1, substr_count($template->process(new ViewableData()), "b.css"));

        // if we disable the requirements then we should get nothing
        $template->includeRequirements(false);
        $this->assertEquals(0, substr_count($template->process(new ViewableData()), "a.css"));
        $this->assertEquals(0, substr_count($template->process(new ViewableData()), "b.css"));
    }

    public function testRequireCallInTemplateInclude()
    {
        //TODO undo skip test on the event that templates ever obtain the ability to reference MODULE_DIR (or something to that effect)
        if (FRAMEWORK_DIR === 'framework') {
            $template = new SSViewer(['SSViewerTestProcess']);

            Requirements::set_suffix_requirements(false);

            $this->assertEquals(
                1,
                substr_count(
                    $template->process(new ViewableData()),
                    "tests/php/View/SSViewerTest/javascript/RequirementsTest_a.js"
                )
            );
        } else {
            $this->markTestSkipped(
                'Requirement will always fail if the framework dir is not ' .
                'named \'framework\', since templates require hard coded paths'
            );
        }
    }

    public function testCallsWithArguments()
    {
        $data = new ArrayData(
            [
            'Set' => new ArrayList(
                [
                new SSViewerTest\TestObject("1"),
                new SSViewerTest\TestObject("2"),
                new SSViewerTest\TestObject("3"),
                new SSViewerTest\TestObject("4"),
                new SSViewerTest\TestObject("5"),
                ]
            ),
            'Level' => new SSViewerTest\LevelTestData(1),
            'Nest' => [
            'Level' => new SSViewerTest\LevelTestData(2),
            ],
            ]
        );

        $tests = [
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
        ];

        foreach ($tests as $template => $expected) {
            $this->assertEquals($expected, trim($this->render($template, $data)));
        }
    }

    public function testRepeatedCallsAreCached()
    {
        $data = new SSViewerTest\CacheTestData();
        $template = '
			<% if $TestWithCall %>
				<% with $TestWithCall %>
					{$Message}
				<% end_with %>

				{$TestWithCall.Message}
			<% end_if %>';

        $this->assertEquals('HiHi', preg_replace('/\s+/', '', $this->render($template, $data)));
        $this->assertEquals(
            1,
            $data->testWithCalls,
            'SSViewerTest_CacheTestData::TestWithCall() should only be called once. Subsequent calls should be cached'
        );

        $data = new SSViewerTest\CacheTestData();
        $template = '
			<% if $TestLoopCall %>
				<% loop $TestLoopCall %>
					{$Message}
				<% end_loop %>
			<% end_if %>';

        $this->assertEquals('OneTwo', preg_replace('/\s+/', '', $this->render($template, $data)));
        $this->assertEquals(
            1,
            $data->testLoopCalls,
            'SSViewerTest_CacheTestData::TestLoopCall() should only be called once. Subsequent calls should be cached'
        );
    }

    public function testClosedBlockExtension()
    {
        $count = 0;
        $parser = new SSTemplateParser();
        $parser->addClosedBlock(
            'test',
            function ($res) use (&$count) {
                $count++;
            }
        );

        $template = new SSViewer_FromString("<% test %><% end_test %>", $parser);
        $template->process(new SSViewerTest\TestFixture());

        $this->assertEquals(1, $count);
    }

    public function testOpenBlockExtension()
    {
        $count = 0;
        $parser = new SSTemplateParser();
        $parser->addOpenBlock(
            'test',
            function ($res) use (&$count) {
                $count++;
            }
        );

        $template = new SSViewer_FromString("<% test %>", $parser);
        $template->process(new SSViewerTest\TestFixture());

        $this->assertEquals(1, $count);
    }

    /**
     * Tests if caching for SSViewer_FromString is working
     */
    public function testFromStringCaching()
    {
        $content = 'Test content';
        $cacheFile = TEMP_PATH . DIRECTORY_SEPARATOR . '.cache.' . sha1($content);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        // Test global behaviors
        $this->render($content, null, null);
        $this->assertFalse(file_exists($cacheFile), 'Cache file was created when caching was off');

        SSViewer_FromString::config()->update('cache_template', true);
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
