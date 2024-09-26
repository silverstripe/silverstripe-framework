<?php

namespace SilverStripe\View\Tests;

use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\Control\ContentNegotiator;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Model\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\SSTemplateParseException;
use SilverStripe\View\SSTemplateParser;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Tests\SSTemplateEngineTest\TestModelData;
use SilverStripe\Model\ModelData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use SilverStripe\View\Exception\MissingTemplateException;
use SilverStripe\View\SSTemplateEngine;
use SilverStripe\View\ViewLayerData;
use stdClass;

class SSTemplateEngineTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        SSTemplateEngineTest\TestObject::class,
    ];

    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        SSViewer::config()->set('source_file_comments', false);
        TestAssetStore::activate('SSTemplateEngineTest');
    }

    protected function tearDown(): void
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    /**
     * Test that a template without a <head> tag still renders.
     */
    public function testTemplateWithoutHeadRenders()
    {
        $data = new ArrayData([ 'Var' => 'var value' ]);
        $engine = new SSTemplateEngine('SSTemplateEngineTestPartialTemplate');
        $result = $engine->render(new ViewLayerData($data));
        $this->assertEquals('Test partial template: var value', trim(preg_replace("/<!--.*-->/U", '', $result ?? '') ?? ''));
    }

    /**
     * Ensure global methods aren't executed
     */
    public function testTemplateExecution()
    {
        $data = new ArrayData([ 'Var' => 'phpinfo' ]);
        $engine = new SSTemplateEngine('SSTemplateEngineTestPartialTemplate');
        $result = $engine->render(new ViewLayerData($data));
        $this->assertEquals('Test partial template: phpinfo', trim(preg_replace("/<!--.*-->/U", '', $result ?? '') ?? ''));
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

        $engine = new SSTemplateEngine('SSTemplateEngineTestIncludeScopeInheritance');
        $result = $engine->render(new ViewLayerData($data));
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

        $engine = new SSTemplateEngine('SSTemplateEngineTestIncludeScopeInheritanceWithArgs');
        $result = $engine->render(new ViewLayerData($data));
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
        $engine = new SSTemplateEngine('SSTemplateEngineTestIncludeScopeInheritanceWithArgs');
        $result = $engine->render(new ViewLayerData($data));

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

    public function testRequirements()
    {
        /** @var Requirements_Backend|MockObject $requirements */
        $requirements = $this
            ->getMockBuilder(Requirements_Backend::class)
            ->onlyMethods(['javascript', 'css'])
            ->getMock();
        $jsFile = FRAMEWORK_DIR . '/tests/forms/a.js';
        $cssFile = FRAMEWORK_DIR . '/tests/forms/a.js';

        $requirements->expects($this->once())->method('javascript')->with($jsFile);
        $requirements->expects($this->once())->method('css')->with($cssFile);

        $origReq = Requirements::backend();
        Requirements::set_backend($requirements);
        $result = $this->render(
            "<% require javascript($jsFile) %>
		    <% require css($cssFile) %>"
        );
        Requirements::set_backend($origReq);

        // Injecting the actual requirements is the responsibility of SSViewer, so we shouldn't see it in the result
        $this->assertFalse((bool)trim($result), 'Should be no content in this return.');
    }

    public function testRequireCallInTemplateInclude()
    {
        /** @var Requirements_Backend|MockObject $requirements */
        $requirements = $this
            ->getMockBuilder(Requirements_Backend::class)
            ->onlyMethods(['themedJavascript', 'css'])
            ->getMock();

        $requirements->expects($this->once())->method('themedJavascript')->with('RequirementsTest_a');
        $requirements->expects($this->never())->method('css');

        $engine = new SSTemplateEngine('SSTemplateEngineTestProcess');
        $origReq = Requirements::backend();
        Requirements::set_backend($requirements);
        Requirements::set_suffix_requirements(false);
        $result = $engine->render(new ViewLayerData([]));
        Requirements::set_backend($origReq);

        // Injecting the actual requirements is the responsibility of SSViewer, so we shouldn't see it in the result
        $this->assertEqualIgnoringWhitespace('<html><head></head><body></body></html>', $result);
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
        <%--commentwithoutwhitespace--%>last content
        SS;
        $actual = $this->render($input);
        $expected = <<<SS
        This is my templateThis is some contentFinal content

        Some more content
        Mixing content and  Final final
        content
        last content
        SS;
        $this->assertEquals($expected, $actual);

        $input = <<<SS
        <%--

        --%>empty comment1
        <%-- --%>empty comment2
        <%----%>empty comment3
        SS;
        $actual = $this->render($input);
        $expected = <<<SS
        empty comment1
        empty comment2
        empty comment3
        SS;
        $this->assertEquals($expected, $actual);
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

    public function testBasicInjectionMismatchedBrackets()
    {
        $this->expectException(SSTemplateParseException::class);
        $this->expectExceptionMessageMatches('/Malformed bracket injection {\$Value(.*)/');
        $this->render('A {$Value here');
        $this->fail("Parser didn't error when encountering mismatched brackets in an injection");
    }

    public function testGlobalVariableCalls()
    {
        $this->assertEquals('automatic', $this->render('$SSTemplateEngineTest_GlobalAutomatic'));
        $this->assertEquals('reference', $this->render('$SSTemplateEngineTest_GlobalReferencedByString'));
        $this->assertEquals('reference', $this->render('$SSTemplateEngineTest_GlobalReferencedInArray'));
    }

    public function testGlobalVariableCallsWithArguments()
    {
        $this->assertEquals('zz', $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments'));
        $this->assertEquals('zFooz', $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments("Foo")'));
        $this->assertEquals(
            'zFoo:Bar:Bazz',
            $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments("Foo", "Bar", "Baz")')
        );
        $this->assertEquals(
            'zreferencez',
            $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments($SSTemplateEngineTest_GlobalReferencedByString)')
        );
    }

    public function testGlobalVariablesAreEscaped()
    {
        $this->assertEquals('<div></div>', $this->render('$SSTemplateEngineTest_GlobalHTMLFragment'));
        $this->assertEquals('&lt;div&gt;&lt;/div&gt;', $this->render('$SSTemplateEngineTest_GlobalHTMLEscaped'));

        $this->assertEquals(
            'z<div></div>z',
            $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments($SSTemplateEngineTest_GlobalHTMLFragment)')
        );
        // Don't escape value when passing into a method call
        $this->assertEquals(
            'z<div></div>z',
            $this->render('$SSTemplateEngineTest_GlobalThatTakesArguments($SSTemplateEngineTest_GlobalHTMLEscaped)')
        );
    }

    public function testGlobalVariablesReturnNull()
    {
        $this->assertEquals('<p></p>', $this->render('<p>$SSTemplateEngineTest_GlobalReturnsNull</p>'));
        $this->assertEquals('<p></p>', $this->render('<p>$SSTemplateEngineTest_GlobalReturnsNull.Chained.Properties</p>'));
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
            Security::getCurrentUser()->ID,
            $this->render('{$CurrentMember.ID}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            Security::getCurrentUser()->ID,
            $this->render('{$CurrentUser.ID}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            Security::getCurrentUser()->ID,
            $this->render('{$currentMember.ID}'),
            'Member template functions result correct result'
        );
        $this->assertEquals(
            Security::getCurrentUser()->ID,
            $this->render('{$currentUser.ID}'),
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
            new SSTemplateEngineTest\TestObject()
        );
        $this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if Link %>');

        // check if Link with $ in front of variable
        $result = $this->render(
            'A<% if $Link %>$Link<% end_if %>B',
            new SSTemplateEngineTest\TestObject()
        );
        $this->assertEquals('Asome/url.htmlB', $result, 'casting helper not used for <% if $Link %>');
    }

    public function testLocalFunctionsTakePriorityOverGlobals()
    {
        $data = new ArrayData([
            'Page' => new SSTemplateEngineTest\TestObject()
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

    public function testCurrentScopeLoop(): void
    {
        $data = new ArrayList([['Val' => 'one'], ['Val' => 'two'], ['Val' => 'three']]);
        $this->assertEqualIgnoringWhitespace(
            'one two three',
            $this->render('<% loop %>$Val<% end_loop %>', $data)
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
                new SSTemplateEngineTest\TestObject('Number6')
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

    public static function provideArgumentTypes()
    {
        return [
            [
                'arg0:0,arg1:"string",arg2:true',
                '$methodWithTypedArguments(0, "string", true).RAW',
            ],
            [
                'arg0:false,arg1:"string",arg2:true',
                '$methodWithTypedArguments(false, "string", true).RAW',
            ],
            [
                'arg0:null,arg1:"string",arg2:true',
                '$methodWithTypedArguments(null, "string", true).RAW',
            ],
            [
                'arg0:"",arg1:"string",arg2:true',
                '$methodWithTypedArguments("", "string", true).RAW',
            ],
            [
                'arg0:0,arg1:1,arg2:2',
                '$methodWithTypedArguments(0, 1, 2).RAW',
            ],
        ];
    }

    #[DataProvider('provideArgumentTypes')]
    public function testArgumentTypes(string $expected, string $template)
    {
        $this->assertEquals($expected, $this->render($template, new TestModelData()));
    }

    public static function provideEvaluatedArgumentTypes(): array
    {
        $stdobj = new stdClass();
        $stdobj->key = 'value';
        $scenarios = [
            'null value' => [
                'data' => ['Value' => null],
                'useOverlay' => true,
                'expected' => 'arg0:null',
            ],
            'int value' => [
                'data' => ['Value' => 1],
                'useOverlay' => true,
                'expected' => 'arg0:1',
            ],
            'string value' => [
                'data' => ['Value' => '1'],
                'useOverlay' => true,
                'expected' => 'arg0:"1"',
            ],
            'boolean true' => [
                'data' => ['Value' => true],
                'useOverlay' => true,
                'expected' => 'arg0:true',
            ],
            'boolean false' => [
                'data' => ['Value' => false],
                'useOverlay' => true,
                'expected' => 'arg0:false',
            ],
            'object value' => [
                'data' => ['Value' => $stdobj],
                'useOverlay' => true,
                'expected' => 'arg0:{"key":"value"}',
            ],
        ];
        foreach ($scenarios as $key => $scenario) {
            $scenario['useOverlay'] = false;
            $scenarios[$key . ' no overlay'] = $scenario;
        }
        return $scenarios;
    }

    #[DataProvider('provideEvaluatedArgumentTypes')]
    public function testEvaluatedArgumentTypes(array $data, bool $useOverlay, string $expected): void
    {
        $template = '$methodWithTypedArguments($Value).RAW';
        $model = new TestModelData();
        $overlay = $data;
        if (!$useOverlay) {
            $model = $model->customise($data);
            $overlay = [];
        }
        $this->assertEquals($expected, $this->render($template, $model, $overlay));
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
        $data = new ArrayList([new SSTemplateEngineTest\TestFixture()]);
        $this->assertEquals(
            'before[out:Test]after
				beforeTestafter',
            $this->render(
                'before<% loop %>$Test<% end_loop %>after
				before<% loop %>Test<% end_loop %>after',
                $data
            )
        );

        // The control tags are removed from the output, but no whitespace
        // This is a quirk that could be changed, but included in the test to make the current
        // behaviour explicit
        $this->assertEquals(
            'before

[out:ItemOnItsOwnLine]

after',
            $this->render(
                'before
<% loop %>
$ItemOnItsOwnLine
<% end_loop %>
after',
                $data
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

    public static function typePreservationDataProvider()
    {
        return [
            // Null
            ['NULL:', 'null'],
            ['NULL:', 'NULL'],
            // Booleans
            ['boolean:1', 'true'],
            ['boolean:1', 'TRUE'],
            ['boolean:', 'false'],
            ['boolean:', 'FALSE'],
            // Strings which may look like booleans/null to the parser
            ['string:nullish', 'nullish'],
            ['string:notnull', 'notnull'],
            ['string:truethy', 'truethy'],
            ['string:untrue', 'untrue'],
            ['string:falsey', 'falsey'],
            // Integers
            ['integer:0', '0'],
            ['integer:1', '1'],
            ['integer:15', '15'],
            ['integer:-15', '-15'],
            // Octal integers
            ['integer:83', '0123'],
            ['integer:-83', '-0123'],
            // Hexadecimal integers
            ['integer:26', '0x1A'],
            ['integer:-26', '-0x1A'],
            // Binary integers
            ['integer:255', '0b11111111'],
            ['integer:-255', '-0b11111111'],
            // Floats (aka doubles)
            ['double:0', '0.0'],
            ['double:1', '1.0'],
            ['double:15.25', '15.25'],
            ['double:-15.25', '-15.25'],
            ['double:1200', '1.2e3'],
            ['double:-1200', '-1.2e3'],
            ['double:0.07', '7E-2'],
            ['double:-0.07', '-7E-2'],
            // Explicitly quoted strings
            ['string:0', '"0"'],
            ['string:1', '\'1\''],
            ['string:foobar', '"foobar"'],
            ['string:foo bar baz', '"foo bar baz"'],
            ['string:false', '\'false\''],
            ['string:true', '\'true\''],
            ['string:null', '\'null\''],
            ['string:false', '"false"'],
            ['string:true', '"true"'],
            ['string:null', '"null"'],
            // Implicit strings
            ['string:foobar', 'foobar'],
            ['string:foo bar baz', 'foo bar baz']
        ];
    }

    #[DataProvider('typePreservationDataProvider')]
    public function testTypesArePreserved($expected, $templateArg)
    {
        $data = new ArrayData([
            'Test' => new TestModelData()
        ]);

        $this->assertEquals($expected, $this->render("\$Test.Type({$templateArg})", $data));
    }

    #[DataProvider('typePreservationDataProvider')]
    public function testTypesArePreservedAsIncludeArguments($expected, $templateArg)
    {
        $data = new ArrayData([
            'Test' => new TestModelData()
        ]);

        $this->assertEquals(
            $expected,
            $this->render("<% include SSTemplateEngineTestTypePreservation Argument={$templateArg} %>", $data)
        );
    }

    public function testTypePreservationInConditionals()
    {
        $data = new ArrayData([
            'Test' => new TestModelData()
        ]);

        // Types in conditionals
        $this->assertEquals('pass', $this->render('<% if true %>pass<% else %>fail<% end_if %>', $data));
        $this->assertEquals('pass', $this->render('<% if false %>fail<% else %>pass<% end_if %>', $data));
        $this->assertEquals('pass', $this->render('<% if 1 %>pass<% else %>fail<% end_if %>', $data));
        $this->assertEquals('pass', $this->render('<% if 0 %>fail<% else %>pass<% end_if %>', $data));
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

    public static function provideIfBlockWithIterable(): array
    {
        $scenarios = [
            'empty array' => [
                'iterable' => [],
                'inScope' => false,
            ],
            'array' => [
                'iterable' => [1, 2, 3],
                'inScope' => false,
            ],
            'ArrayList' => [
                'iterable' => new ArrayList([['Val' => 1], ['Val' => 2], ['Val' => 3]]),
                'inScope' => false,
            ],
        ];
        foreach ($scenarios as $name => $scenario) {
            $scenario['inScope'] = true;
            $scenarios[$name . ' in scope'] = $scenario;
        }
        return $scenarios;
    }

    #[DataProvider('provideIfBlockWithIterable')]
    public function testIfBlockWithIterable(iterable $iterable, bool $inScope): void
    {
        $expected = count($iterable) ? 'has value' : 'no value';
        $data = new ArrayData(['Iterable' => $iterable]);
        if ($inScope) {
            $template = '<% with $Iterable %><% if $Me %>has value<% else %>no value<% end_if %><% end_with %>';
        } else {
            $template = '<% if $Iterable %>has value<% else %>no value<% end_if %>';
        }
        $this->assertEqualIgnoringWhitespace($expected, $this->render($template, $data));
    }

    public function testBaseTagGeneration()
    {
        // XHTML will have a closed base tag
        $tmpl1 = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
        $this->assertMatchesRegularExpression('/<head><base href=".*" \/><\/head>/', $this->render($tmpl1));

        // HTML4 and 5 will only have it for IE
        $tmpl2 = '<!DOCTYPE html>
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
        $this->assertMatchesRegularExpression(
            '/<head><base href=".*"><\/head>/',
            $this->render($tmpl2)
        );


        $tmpl3 = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
			<html>
				<head><% base_tag %></head>
				<body><p>test</p><body>
			</html>';
        $this->assertMatchesRegularExpression(
            '/<head><base href=".*"><\/head>/',
            $this->render($tmpl3)
        );

        // Check that the content negotiator converts to the equally legal formats
        $negotiator = new ContentNegotiator();

        $response = new HTTPResponse($this->render($tmpl1));
        $negotiator->html($response);
        $this->assertMatchesRegularExpression(
            '/<head><base href=".*"><\/head>/',
            $response->getBody()
        );

        $response = new HTTPResponse($this->render($tmpl1));
        $negotiator->xhtml($response);
        $this->assertMatchesRegularExpression('/<head><base href=".*" \/><\/head>/', $response->getBody());
    }

    public function testIncludeWithArguments()
    {
        $this->assertEquals(
            '<p>[out:Arg1]</p><p>[out:Arg2]</p><p>[out:Arg2.Count]</p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments %>')
        );

        $this->assertEquals(
            '<p>A</p><p>[out:Arg2]</p><p>[out:Arg2.Count]</p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1=A %>')
        );

        $this->assertEquals(
            '<p>A</p><p>B</p><p></p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1=A, Arg2=B %>')
        );

        $this->assertEquals(
            '<p>A Bare String</p><p>B Bare String</p><p></p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1=A Bare String, Arg2=B Bare String %>')
        );

        $this->assertEquals(
            '<p>A</p><p>Bar</p><p></p>',
            $this->render(
                '<% include SSTemplateEngineTestIncludeWithArguments Arg1="A", Arg2=$B %>',
                new ArrayData(['B' => 'Bar'])
            )
        );

        $this->assertEquals(
            '<p>A</p><p>Bar</p><p></p>',
            $this->render(
                '<% include SSTemplateEngineTestIncludeWithArguments Arg1="A" %>',
                new ArrayData(['Arg1' => 'Foo', 'Arg2' => 'Bar'])
            )
        );

        $this->assertEquals(
            '<p>A</p><p>0</p><p></p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1="A", Arg2=0 %>')
        );

        $this->assertEquals(
            '<p>A</p><p></p><p></p>',
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1="A", Arg2=false %>')
        );

        $this->assertEquals(
            '<p>A</p><p></p><p></p>',
            // Note Arg2 is explicitly overridden with null
            $this->render('<% include SSTemplateEngineTestIncludeWithArguments Arg1="A", Arg2=null %>')
        );

        $this->assertEquals(
            'SomeArg - Foo - Bar - SomeArg',
            $this->render(
                '<% include SSTemplateEngineTestIncludeScopeInheritanceWithArgsInLoop Title="SomeArg" %>',
                new ArrayData(
                    ['Items' => new ArrayList(
                        [
                        new ArrayData(['Title' => 'Foo']),
                        new ArrayData(['Title' => 'Bar'])
                        ]
                    )]
                )
            )
        );

        $this->assertEquals(
            'A - B - A',
            $this->render(
                '<% include SSTemplateEngineTestIncludeScopeInheritanceWithArgsInWith Title="A" %>',
                new ArrayData(['Item' => new ArrayData(['Title' =>'B'])])
            )
        );

        $this->assertEquals(
            'A - B - C - B - A',
            $this->render(
                '<% include SSTemplateEngineTestIncludeScopeInheritanceWithArgsInNestedWith Title="A" %>',
                new ArrayData(
                    [
                    'Item' => new ArrayData(
                        [
                        'Title' =>'B', 'NestedItem' => new ArrayData(['Title' => 'C'])
                        ]
                    )]
                )
            )
        );

        $this->assertEquals(
            'A - A - A',
            $this->render(
                '<% include SSTemplateEngineTestIncludeScopeInheritanceWithUpAndTop Title="A" %>',
                new ArrayData(
                    [
                    'Item' => new ArrayData(
                        [
                        'Title' =>'B', 'NestedItem' => new ArrayData(['Title' => 'C'])
                        ]
                    )]
                )
            )
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

        $res = $this->render('<% include SSTemplateEngineTestIncludeObjectArguments A=$Nested.Object, B=$Object %>', $data);
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

        $engine = new SSTemplateEngine('Includes/SSTemplateEngineTestRecursiveInclude');
        $result = $engine->render(new ViewLayerData($data));
        // We don't care about whitespace
        $rationalisedResult = trim(preg_replace('/\s+/', ' ', $result ?? '') ?? '');

        $this->assertEquals('A A1 A1 i A1 ii A2 A3', $rationalisedResult);
    }

    /**
     * See {@link ModelDataTest} for more extensive casting tests,
     * this test just ensures that basic casting is correctly applied during template parsing.
     */
    public function testCastingHelpers()
    {
        $vd = new SSTemplateEngineTest\TestModelData();
        $vd->TextValue = '<b>html</b>';
        $vd->HTMLValue = '<b>html</b>';
        $vd->UncastedValue = '<b>html</b>';

        // Value casted as "Text"
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $this->render('$TextValue', $vd)
        );
        $this->assertEquals(
            '<b>html</b>',
            $this->render('$TextValue.RAW', $vd)
        );
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $this->render('$TextValue.XML', $vd)
        );

        // Value casted as "HTMLText"
        $this->assertEquals(
            '<b>html</b>',
            $this->render('$HTMLValue', $vd)
        );
        $this->assertEquals(
            '<b>html</b>',
            $this->render('$HTMLValue.RAW', $vd)
        );
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $this->render('$HTMLValue.XML', $vd)
        );

        // Uncasted value (falls back to the relevant DBField class for the data type)
        $vd = new SSTemplateEngineTest\TestModelData();
        $vd->UncastedValue = '<b>html</b>';
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $this->render('$UncastedValue', $vd)
        );
        $this->assertEquals(
            '<b>html</b>',
            $this->render('$UncastedValue.RAW', $vd)
        );
        $this->assertEquals(
            '&lt;b&gt;html&lt;/b&gt;',
            $this->render('$UncastedValue.XML', $vd)
        );
    }

    public static function provideLoop(): array
    {
        return [
            'nested array and iterator' => [
                'iterable' => [
                    [
                        'value 1',
                        'value 2',
                    ],
                    new ArrayList([
                        'value 3',
                        'value 4',
                    ]),
                ],
                'template' => '<% loop $Iterable %><% loop $Me %>$Me<% end_loop %><% end_loop %>',
                'expected' => 'value 1 value 2 value 3 value 4',
            ],
            'nested associative arrays' => [
                'iterable' => [
                    [
                        'Foo' => 'one',
                    ],
                    [
                        'Foo' => 'two',
                    ],
                    [
                        'Foo' => 'three',
                    ],
                ],
                'template' => '<% loop $Iterable %>$Foo<% end_loop %>',
                'expected' => 'one two three',
            ],
        ];
    }

    #[DataProvider('provideLoop')]
    public function testLoop(iterable $iterable, string $template, string $expected): void
    {
        $data = new ArrayData(['Iterable' => $iterable]);
        $this->assertEqualIgnoringWhitespace($expected, $this->render($template, $data));
    }

    public static function provideCountIterable(): array
    {
        $scenarios = [
            'empty array' => [
                'iterable' => [],
                'inScope' => false,
            ],
            'array' => [
                'iterable' => [1, 2, 3],
                'inScope' => false,
            ],
            'ArrayList' => [
                'iterable' => new ArrayList([['Val' => 1], ['Val' => 2], ['Val' => 3]]),
                'inScope' => false,
            ],
        ];
        foreach ($scenarios as $name => $scenario) {
            $scenario['inScope'] = true;
            $scenarios[$name . ' in scope'] = $scenario;
        }
        return $scenarios;
    }

    #[DataProvider('provideCountIterable')]
    public function testCountIterable(iterable $iterable, bool $inScope): void
    {
        $expected = count($iterable);
        $data = new ArrayData(['Iterable' => $iterable]);
        if ($inScope) {
            $template = '<% with $Iterable %>$Count<% end_with %>';
        } else {
            $template = '$Iterable.Count';
        }
        $this->assertEqualIgnoringWhitespace($expected, $this->render($template, $data));
    }

    public function testSSViewerBasicIteratorSupport()
    {
        $data = new ArrayData(
            [
            'Set' => new ArrayList(
                [
                new SSTemplateEngineTest\TestObject("1"),
                new SSTemplateEngineTest\TestObject("2"),
                new SSTemplateEngineTest\TestObject("3"),
                new SSTemplateEngineTest\TestObject("4"),
                new SSTemplateEngineTest\TestObject("5"),
                new SSTemplateEngineTest\TestObject("6"),
                new SSTemplateEngineTest\TestObject("7"),
                new SSTemplateEngineTest\TestObject("8"),
                new SSTemplateEngineTest\TestObject("9"),
                new SSTemplateEngineTest\TestObject("10"),
                ]
            )
            ]
        );

        //base test
        $result = $this->render('<% loop Set %>$Number<% end_loop %>', $data);
        $this->assertEquals("12345678910", $result, "Numbers rendered in order");

        //test First
        $result = $this->render('<% loop Set %><% if $IsFirst %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("1", $result, "Only the first number is rendered");

        //test Last
        $result = $this->render('<% loop Set %><% if $IsLast %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("10", $result, "Only the last number is rendered");

        //test Even
        $result = $this->render('<% loop Set %><% if $Even() %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even with quotes
        $result = $this->render('<% loop Set %><% if $Even("1") %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even without quotes
        $result = $this->render('<% loop Set %><% if $Even(1) %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("246810", $result, "Even numbers rendered in order");

        //test Even with zero-based start index
        $result = $this->render('<% loop Set %><% if $Even("0") %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("13579", $result, "Even (with zero-based index) numbers rendered in order");

        //test Odd
        $result = $this->render('<% loop Set %><% if $Odd %>$Number<% end_if %><% end_loop %>', $data);
        $this->assertEquals("13579", $result, "Odd numbers rendered in order");

        //test FirstLast
        $result = $this->render('<% loop Set %><% if $FirstLast %>$Number$FirstLast<% end_if %><% end_loop %>', $data);
        $this->assertEquals("1first10last", $result, "First and last numbers rendered in order");

        //test Middle
        $result = $this->render('<% loop Set %><% if $Middle %>$Number<% end_if %><% end_loop %>', $data);
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
            'BarTop',
            $this->render('<% with Foo.Bar %>{$Name}{$Up.Name}<% end_with %>', $data)
        );

        // Stepping up & back down the scope tree
        $this->assertEquals(
            'BazFooBar',
            $this->render('<% with Foo.Bar.Baz %>{$Name}{$Up.Foo.Name}{$Up.Foo.Bar.Name}<% end_with %>', $data)
        );

        // Using $Up in a with block
        $this->assertEquals(
            'BazTopBar',
            $this->render(
                '<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}{$Foo.Bar.Name}<% end_with %>'
                . '<% end_with %>',
                $data
            )
        );

        // Stepping up & back down the scope tree with with blocks
        $this->assertEquals(
            'BazTopBarTopBaz',
            $this->render(
                '<% with Foo.Bar.Baz %>{$Name}<% with $Up %>{$Name}<% with Foo.Bar %>{$Name}<% end_with %>'
                . '{$Name}<% end_with %>{$Name}<% end_with %>',
                $data
            )
        );

        // Using $Up.Up, where first $Up points to a previous scope entered using $Up, thereby skipping up to Foo
        $this->assertEquals(
            'Foo',
            $this->render(
                '<% with Foo %><% with Bar %><% with Baz %>{$Up.Up.Name}<% end_with %><% end_with %>'
                . '<% end_with %>',
                $data
            )
        );

        // Using $Up as part of a lookup chain in <% with %>
        $this->assertEquals(
            'Top',
            $this->render('<% with Foo.Bar.Baz.Up.Qux %>{$Up.Name}<% end_with %>', $data)
        );
    }

    public function testTooManyUps()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Up called when we're already at the top of the scope");
        $data = new ArrayData([
            'Foo' => new ArrayData([
                'Name' => 'Foo',
                'Bar' => new ArrayData([
                    'Name' => 'Bar'
                ])
            ])
        ]);

        $this->assertEquals(
            'Foo',
            $this->render('<% with Foo.Bar %>{$Up.Up.Name}<% end_with %>', $data)
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
                '<% loop $Foo %>$Name<% loop Children %>$Name<% end_loop %><% if $IsLast %>last<% end_if %>'
                . '<% end_loop %>',
                $data
            )
        );
    }

    public function testLayout()
    {
        $this->useTestTheme(
            __DIR__ . '/SSTemplateEngineTest',
            'layouttest',
            function () {
                $engine = new SSTemplateEngine('Page');
                $this->assertEquals("Foo\n\n", $engine->render(new ViewLayerData([])));

                $engine = new SSTemplateEngine(['Shortcodes', 'Page']);
                $this->assertEquals("[file_link]\n\n", $engine->render(new ViewLayerData([])));
            }
        );
    }

    public static function provideRenderWithSourceFileComments(): array
    {
        $i = __DIR__ . '/SSTemplateEngineTest/templates/Includes';
        $f = __DIR__ . '/SSTemplateEngineTest/templates/SSTemplateEngineTestComments';
        return [
            [
                'name' => 'SSTemplateEngineTestCommentsFullSource',
                'expected' => ""
                    . "<!doctype html>"
                    . "<!-- template $f/SSTemplateEngineTestCommentsFullSource.ss -->"
                    . "<html>"
                    . "\t<head></head>"
                    . "\t<body></body>"
                    . "</html>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsFullSource.ss -->",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsFullSourceHTML4Doctype',
                'expected' => ""
                    . "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML "
                    . "4.01//EN\"\t\t\"http://www.w3.org/TR/html4/strict.dtd\">"
                    . "<!-- template $f/SSTemplateEngineTestCommentsFullSourceHTML4Doctype.ss -->"
                    . "<html>"
                    . "\t<head></head>"
                    . "\t<body></body>"
                    . "</html>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsFullSourceHTML4Doctype.ss -->",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsFullSourceNoDoctype',
                'expected' => ""
                    . "<html><!-- template $f/SSTemplateEngineTestCommentsFullSourceNoDoctype.ss -->"
                    . "\t<head></head>"
                    . "\t<body></body>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsFullSourceNoDoctype.ss --></html>",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsFullSourceIfIE',
                'expected' => ""
                    . "<!doctype html>"
                    . "<!-- template $f/SSTemplateEngineTestCommentsFullSourceIfIE.ss -->"
                    . "<!--[if lte IE 8]> <html class='old-ie'> <![endif]-->"
                    . "<!--[if gt IE 8]> <html class='new-ie'> <![endif]-->"
                    . "<!--[if !IE]><!--> <html class='no-ie'> <!--<![endif]-->"
                    . "\t<head></head>"
                    . "\t<body></body>"
                    . "</html>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsFullSourceIfIE.ss -->",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsFullSourceIfIENoDoctype',
                'expected' => ""
                    . "<!--[if lte IE 8]> <html class='old-ie'> <![endif]-->"
                    . "<!--[if gt IE 8]> <html class='new-ie'> <![endif]-->"
                    . "<!--[if !IE]><!--> <html class='no-ie'>"
                    . "<!-- template $f/SSTemplateEngineTestCommentsFullSourceIfIENoDoctype.ss -->"
                    . " <!--<![endif]-->"
                    . "\t<head></head>"
                    . "\t<body></body>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsFullSourceIfIENoDoctype.ss --></html>",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsPartialSource',
                'expected' =>
                "<!-- template $f/SSTemplateEngineTestCommentsPartialSource.ss -->"
                    . "<div class='typography'></div>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsPartialSource.ss -->",
            ],
            [
                'name' => 'SSTemplateEngineTestCommentsWithInclude',
                'expected' =>
                "<!-- template $f/SSTemplateEngineTestCommentsWithInclude.ss -->"
                    . "<div class='typography'>"
                    . "<!-- include 'SSTemplateEngineTestCommentsInclude' -->"
                    . "<!-- template $i/SSTemplateEngineTestCommentsInclude.ss -->"
                    . "Included"
                    . "<!-- end template $i/SSTemplateEngineTestCommentsInclude.ss -->"
                    . "<!-- end include 'SSTemplateEngineTestCommentsInclude' -->"
                    . "</div>"
                    . "<!-- end template $f/SSTemplateEngineTestCommentsWithInclude.ss -->",
            ],
        ];
    }

    #[DataProvider('provideRenderWithSourceFileComments')]
    public function testRenderWithSourceFileComments(string $name, string $expected)
    {
        SSViewer::config()->set('source_file_comments', true);
        $this->_renderWithSourceFileComments('SSTemplateEngineTestComments/' . $name, $expected);
    }

    public static function provideRenderWithMissingTemplate(): array
    {
        return [
            [
                'templateCandidates' => [],
            ],
            [
                'templateCandidates' => '',
            ],
            [
                'templateCandidates' => ['noTemplate'],
            ],
            [
                'templateCandidates' => 'noTemplate',
            ],
        ];
    }

    #[DataProvider('provideRenderWithMissingTemplate')]
    public function testRenderWithMissingTemplate(string|array $templateCandidates): void
    {
        if (empty($templateCandidates)) {
            $message = 'No template to render. Try calling setTemplate() or passing template candidates into the constructor.';
        } else {
            $message = 'None of the following templates could be found: '
                . print_r($templateCandidates, true)
                . ' in themes "' . print_r(SSViewer::get_themes(), true) . '"';
        }
        $engine = new SSTemplateEngine($templateCandidates);
        $this->expectException(MissingTemplateException::class);
        $this->expectExceptionMessage($message);
        $engine->render(new ViewLayerData([]));
    }

    public function testLoopIteratorIterator()
    {
        $list = new PaginatedList(new ArrayList());
        $result = $this->render(
            '<% loop List %>$ID - $FirstName<br /><% end_loop %>',
            new ArrayData(['List' => $list])
        );
        $this->assertEquals('', $result);
    }

    public static function provideCallsWithArguments(): array
    {
        return [
            [
                'template' => '$Level.output(1)',
                'expected' => '1-1',
            ],
            [
                'template' => '$Nest.Level.output($Set.First.Number)',
                'expected' => '2-1',
            ],
            [
                'template' => '<% with $Set %>$Up.Level.output($First.Number)<% end_with %>',
                'expected' => '1-1',
            ],
            [
                'template' => '<% with $Set %>$Top.Nest.Level.output($First.Number)<% end_with %>',
                'expected' => '2-1',
            ],
            [
                'template' => '<% loop $Set %>$Up.Nest.Level.output($Number)<% end_loop %>',
                'expected' => '2-12-22-32-42-5',
            ],
            [
                'template' => '<% loop $Set %>$Top.Level.output($Number)<% end_loop %>',
                'expected' => '1-11-21-31-41-5',
            ],
            [
                'template' => '<% with $Nest %>$Level.output($Top.Set.First.Number)<% end_with %>',
                'expected' => '2-1',
            ],
            [
                'template' => '<% with $Level %>$output($Up.Set.Last.Number)<% end_with %>',
                'expected' => '1-5',
            ],
            [
                'template' => '<% with $Level.forWith($Set.Last.Number) %>$output("hi")<% end_with %>',
                'expected' => '5-hi',
            ],
            [
                'template' => '<% loop $Level.forLoop($Set.First.Number) %>$Number<% end_loop %>',
                'expected' => '!0',
            ],
            [
                'template' => '<% with $Nest %>
                        <% with $Level.forWith($Up.Set.First.Number) %>$output("hi")<% end_with %>
                    <% end_with %>',
                'expected' => '1-hi',
            ],
            [
                'template' => '<% with $Nest %>
                        <% loop $Level.forLoop($Top.Set.Last.Number) %>$Number<% end_loop %>
                    <% end_with %>',
                'expected' => '!0!1!2!3!4',
            ],
        ];
    }

    #[DataProvider('provideCallsWithArguments')]
    public function testCallsWithArguments(string $template, string $expected): void
    {
        $data = new ArrayData(
            [
                'Set' => new ArrayList(
                    [
                        new SSTemplateEngineTest\TestObject("1"),
                        new SSTemplateEngineTest\TestObject("2"),
                        new SSTemplateEngineTest\TestObject("3"),
                        new SSTemplateEngineTest\TestObject("4"),
                        new SSTemplateEngineTest\TestObject("5"),
                    ]
                ),
                'Level' => new SSTemplateEngineTest\LevelTestData(1),
                'Nest' => [
                    'Level' => new SSTemplateEngineTest\LevelTestData(2),
                ],
            ]
        );

        $this->assertEquals($expected, trim($this->render($template, $data) ?? ''));
    }

    public function testRepeatedCallsAreCached()
    {
        $data = new SSTemplateEngineTest\CacheTestData();
        $template = '
			<% if $TestWithCall %>
				<% with $TestWithCall %>
					{$Message}
				<% end_with %>

				{$TestWithCall.Message}
			<% end_if %>';

        $this->assertEquals('HiHi', preg_replace('/\s+/', '', $this->render($template, $data) ?? ''));
        $this->assertEquals(
            1,
            $data->testWithCalls,
            'SSTemplateEngineTest_CacheTestData::TestWithCall() should only be called once. Subsequent calls should be cached'
        );

        $data = new SSTemplateEngineTest\CacheTestData();
        $template = '
			<% if $TestLoopCall %>
				<% loop $TestLoopCall %>
					{$Message}
				<% end_loop %>
			<% end_if %>';

        $this->assertEquals('OneTwo', preg_replace('/\s+/', '', $this->render($template, $data) ?? ''));
        $this->assertEquals(
            1,
            $data->testLoopCalls,
            'SSTemplateEngineTest_CacheTestData::TestLoopCall() should only be called once. Subsequent calls should be cached'
        );
    }

    public function testClosedBlockExtension()
    {
        $count = 0;
        $parser = new SSTemplateParser();
        $parser->addClosedBlock(
            'test',
            function () use (&$count) {
                $count++;
            }
        );

        $engine = new SSTemplateEngine('SSTemplateEngineTestRecursiveInclude');
        $engine->setParser($parser);
        $engine->renderString('<% test %><% end_test %>', new ViewLayerData([]));

        $this->assertEquals(1, $count);
    }

    public function testOpenBlockExtension()
    {
        $count = 0;
        $parser = new SSTemplateParser();
        $parser->addOpenBlock(
            'test',
            function () use (&$count) {
                $count++;
            }
        );

        $engine = new SSTemplateEngine('SSTemplateEngineTestRecursiveInclude');
        $engine->setParser($parser);
        $engine->renderString('<% test %>', new ViewLayerData([]));

        $this->assertEquals(1, $count);
    }

    public function testFromStringCaching()
    {
        $content = 'Test content';
        $cacheFile = TEMP_PATH . DIRECTORY_SEPARATOR . '.cache.' . sha1($content ?? '');
        if (file_exists($cacheFile ?? '')) {
            unlink($cacheFile ?? '');
        }

        // Test instance behaviors
        $this->render($content, cache: false);
        $this->assertFileDoesNotExist($cacheFile, 'Cache file was created when caching was off');

        $this->render($content, cache: true);
        $this->assertFileExists($cacheFile, 'Cache file wasn\'t created when it was meant to');
        unlink($cacheFile ?? '');
    }

    public function testPrimitivesConvertedToDBFields()
    {
        $data = new ArrayData([
            // null value should not be rendered, though should also not throw exception
            'Foo' => new ArrayList(['hello', true, 456, 7.89, null])
        ]);
        $this->assertEqualIgnoringWhitespace(
            'hello 1 456 7.89',
            $this->render('<% loop $Foo %>$Me<% end_loop %>', $data)
        );
    }

    #[DoesNotPerformAssertions]
    public function testMe(): void
    {
        $myArrayData = new class extends ArrayData {
            public function forTemplate(): string
            {
                return '';
            }
        };
        $this->render('$Me', $myArrayData);
    }

    public function testLoopingThroughArrayInOverlay(): void
    {
        $modelData = new ModelData();
        $theArray = [
            ['Val' => 'one'],
            ['Val' => 'two'],
            ['Val' => 'red'],
            ['Val' => 'blue'],
        ];
        $engine = new SSTemplateEngine('SSTemplateEngineTestLoopArray');
        $output = $engine->render(new ViewLayerData($modelData), ['MyArray' => $theArray]);
        $this->assertEqualIgnoringWhitespace('one two red blue', $output);
    }

    public static function provideGetterMethod(): array
    {
        return [
            'as property (not getter)' => [
                'template' => '$MyProperty',
                'expected' => 'Nothing passed in',
            ],
            'as method (not getter)' => [
                'template' => '$MyProperty()',
                'expected' => 'Nothing passed in',
            ],
            'as method (not getter), with arg' => [
                'template' => '$MyProperty("Some Value")',
                'expected' => 'Was passed in: Some Value',
            ],
            'as property (getter)' => [
                'template' => '$getMyProperty',
                'expected' => 'Nothing passed in',
            ],
            'as method (getter)' => [
                'template' => '$getMyProperty()',
                'expected' => 'Nothing passed in',
            ],
            'as method (getter), with arg' => [
                'template' => '$getMyProperty("Some Value")',
                'expected' => 'Was passed in: Some Value',
            ],
        ];
    }

    #[DataProvider('provideGetterMethod')]
    public function testGetterMethod(string $template, string $expected): void
    {
        $model = new SSTemplateEngineTest\TestObject();
        $this->assertSame($expected, $this->render($template, $model));
    }

    /**
     * Small helper to render templates from strings
     */
    private function render(string $templateString, mixed $data = null, array $overlay = [], bool $cache = false): string
    {
        $engine = new SSTemplateEngine();
        if ($data === null) {
            $data = new SSTemplateEngineTest\TestFixture();
        }
        $data = new ViewLayerData($data);
        return trim('' . $engine->renderString($templateString, $data, $overlay, $cache));
    }

    private function _renderWithSourceFileComments($name, $expected)
    {
        $viewer = new SSViewer([$name]);
        $data = new ArrayData([]);
        $result = $viewer->process($data);
        $expected = str_replace(["\r", "\n"], '', $expected ?? '');
        $result = str_replace(["\r", "\n"], '', $result ?? '');
        $this->assertEquals($result, $expected);
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
                (boolean) preg_match("/{$expectedStr}/", $result ?? ''),
                "Didn't find '{$expectedStr}' in:\n{$result}"
            );
        }
    }

    private function assertEqualIgnoringWhitespace($a, $b, $message = '')
    {
        $this->assertEquals(preg_replace('/\s+/', '', $a ?? ''), preg_replace('/\s+/', '', $b ?? ''), $message);
    }
}
