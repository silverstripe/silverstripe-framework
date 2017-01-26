<?php

namespace SilverStripe\i18n\Tests;

use PHPUnit_Framework_Error_Notice;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use SilverStripe\i18n\Messages\YamlWriter;
use SilverStripe\i18n\Tests\i18nTextCollectorTest\Collector;
use SilverStripe\View\SSViewer;

class i18nTextCollectorTest extends SapphireTest
{
    use i18nTestManifest;

    /**
     * @var string
     */
    protected $alternateBaseSavePath = null;

    public function setUp()
    {
        parent::setUp();
        $this->setupManifest();

        $this->alternateBaseSavePath = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'i18nTextCollectorTest_webroot';
        Filesystem::makeFolder($this->alternateBaseSavePath);
    }

    public function tearDown()
    {
        if (is_dir($this->alternateBaseSavePath)) {
            Filesystem::removeFolder($this->alternateBaseSavePath);
        }

        $this->tearDownManifest();
        parent::tearDown();
    }

    public function testConcatenationInEntityValues()
    {
        $c = i18nTextCollector::create();

        $php = <<<PHP
_t(
'Test.CONCATENATED',
'Line 1 and ' .
'Line \'2\' and ' .
'Line "3"',

'Comment'
);

_t(
'Test.CONCATENATED2',
"Line \"4\" and " .
"Line 5");
PHP;
        $this->assertEquals(
            array(
                'Test.CONCATENATED' => [
                    'default' => "Line 1 and Line '2' and Line \"3\"",
                    'comment' => 'Comment'
                ],
                'Test.CONCATENATED2' => "Line \"4\" and Line 5"
            ),
            $c->collectFromCode($php, 'mymodule')
        );
    }

    public function testCollectFromNewTemplateSyntaxUsingParserSubclass()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);

        $html = <<<SS
        <% _t('Test.SINGLEQUOTE','Single Quote'); %>
<%t i18nTestModule.NEWMETHODSIG "New _t method signature test" %>
<%t i18nTestModule.INJECTIONS_0 "Hello {name} {greeting}, and {goodbye}" name="Mark" greeting="welcome" goodbye="bye" %>
<%t i18nTestModule.INJECTIONS_1 "Hello {name} {greeting}, and {goodbye}" name="Paul" greeting="welcome" goodbye="cya" %>
<%t i18nTestModule.INJECTIONS_2 "Hello {name} {greeting}" is "context (ignored)" name="Steffen" greeting="Wilkommen" %>
<%t i18nTestModule.INJECTIONS_3 name="Cat" greeting='meow' goodbye="meow" %>
<%t i18nTestModule.INJECTIONS_4 name=\$absoluteBaseURL greeting=\$get_locale goodbye="global calls" %>
<%t i18nTestModule.INJECTIONS_9 "An item|{count} items" is "Test Pluralisation" count=4 %>
SS;
        $c->collectFromTemplate($html, 'mymodule', 'Test');

        $this->assertEquals(
            [
                'Test.SINGLEQUOTE' => 'Single Quote',
                'i18nTestModule.NEWMETHODSIG' => "New _t method signature test",
                'i18nTestModule.INJECTIONS_0' => "Hello {name} {greeting}, and {goodbye}",
                'i18nTestModule.INJECTIONS_1' => "Hello {name} {greeting}, and {goodbye}",
                'i18nTestModule.INJECTIONS_2' => [
                    'default' => "Hello {name} {greeting}",
                    'comment' => 'context (ignored)',
                ],
                'i18nTestModule.INJECTIONS_9' => [
                    'one' => 'An item',
                    'other' => '{count} items',
                    'comment' => 'Test Pluralisation'
                ],
            ],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        // Test warning is raised on empty default
        $c->setWarnOnEmptyDefault(true);
        $this->setExpectedException(
            PHPUnit_Framework_Error_Notice::class,
            'Missing localisation default for key i18nTestModule.INJECTIONS_3'
        );
        $c->collectFromTemplate($html, 'mymodule', 'Test');
    }

    public function testCollectFromTemplateSimple()
    {
        $c = i18nTextCollector::create();

        $html = <<<SS
<% _t('Test.SINGLEQUOTE','Single Quote'); %>
SS;
        $this->assertEquals(
            [ 'Test.SINGLEQUOTE' => 'Single Quote' ],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        $html = <<<SS
<% _t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   ); %>
SS;
        $this->assertEquals(
            [ 'Test.DOUBLEQUOTE' => "Double Quote and Spaces" ],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        $html = <<<SS
<% _t("Test.NOSEMICOLON","No Semicolon") %>
SS;
        $this->assertEquals(
            [ 'Test.NOSEMICOLON' => "No Semicolon" ],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );
    }

    public function testCollectFromTemplateAdvanced()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);

        $html = <<<SS
<% _t(
	'NEWLINES',
	'New Lines'
) %>
SS;
        $this->assertEquals(
            [ 'Test.NEWLINES' => "New Lines" ],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        $html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	' Prio and Value with "Double Quotes"',
	'Comment with "Double Quotes"'
) %>
SS;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => [
                'default' => ' Prio and Value with "Double Quotes"',
                'comment' => 'Comment with "Double Quotes"',
            ]],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        $html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	" Prio and Value with 'Single Quotes'",

	"Comment with 'Single Quotes'"
) %>
SS;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => [
                'default' => " Prio and Value with 'Single Quotes'",
                'comment' => "Comment with 'Single Quotes'",
            ]],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        // Test empty
        $html = <<<SS
<% _t('Test.PRIOANDCOMMENT') %>
SS;
        $this->assertEquals(
            [],
            $c->collectFromTemplate($html, 'mymodule', 'Test')
        );

        // Test warning is raised on empty default
        $c->setWarnOnEmptyDefault(true);
        $this->setExpectedException(
            PHPUnit_Framework_Error_Notice::class,
            'Missing localisation default for key Test.PRIOANDCOMMENT'
        );
        $c->collectFromTemplate($html, 'mymodule', 'Test');
    }


    public function testCollectFromCodeSimple()
    {
        $c = i18nTextCollector::create();

        $php = <<<PHP
_t('Test.SINGLEQUOTE','Single Quote');
PHP;
        $this->assertEquals(
            [ 'Test.SINGLEQUOTE' => 'Single Quote' ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   );
PHP;
        $this->assertEquals(
            [ 'Test.DOUBLEQUOTE' => "Double Quote and Spaces" ],
            $c->collectFromCode($php, 'mymodule')
        );
    }

    public function testCollectFromCodeAdvanced()
    {
        $c = i18nTextCollector::create();

        $php = <<<PHP
_t(
	'Test.NEWLINES',
	'New Lines'
);
PHP;
        $this->assertEquals(
            [ 'Test.NEWLINES' => "New Lines" ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	' Value with "Double Quotes"',

	'Comment with "Double Quotes"'
);
PHP;
        $this->assertEquals(
            [
                'Test.PRIOANDCOMMENT' => [
                    'default' => ' Value with "Double Quotes"',
                    'comment' => 'Comment with "Double Quotes"',
                ]
            ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	" Value with 'Single Quotes'",

	"Comment with 'Single Quotes'"
);
PHP;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => [
                'default' => " Value with 'Single Quotes'",
                'comment' => "Comment with 'Single Quotes'"
            ] ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	'Value with \'Escaped Single Quotes\''
);
PHP;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => "Value with 'Escaped Single Quotes'" ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	"Doublequoted Value with 'Unescaped Single Quotes'"
	
	
);
PHP;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => "Doublequoted Value with 'Unescaped Single Quotes'"],
            $c->collectFromCode($php, 'mymodule')
        );
    }


    public function testNewlinesInEntityValues()
    {
        $c = i18nTextCollector::create();

        $php = <<<PHP
_t(
'Test.NEWLINESINGLEQUOTE',
'Line 1
Line 2'
);
PHP;

        $eol = PHP_EOL;
        $this->assertEquals(
            [ 'Test.NEWLINESINGLEQUOTE' => "Line 1{$eol}Line 2" ],
            $c->collectFromCode($php, 'mymodule')
        );

        $php = <<<PHP
_t(
'Test.NEWLINEDOUBLEQUOTE',
"Line 1
Line 2"
);
PHP;
        $this->assertEquals(
            [ 'Test.NEWLINEDOUBLEQUOTE' => "Line 1{$eol}Line 2" ],
            $c->collectFromCode($php, 'mymodule')
        );
    }

    /**
     * Test extracting entities from the new _t method signature
     */
    public function testCollectFromCodeNewSignature()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false); // Disable warnings for tests

        $php = <<<PHP
_t('i18nTestModule.NEWMETHODSIG',"New _t method signature test");
_t('i18nTestModule.INJECTIONS2', "Hello {name} {greeting}. But it is late, {goodbye}",
	array("name"=>"Paul", "greeting"=>"good you are here", "goodbye"=>"see you"));
_t("i18nTestModule.INJECTIONS3", "Hello {name} {greeting}. But it is late, {goodbye}",
		"New context (this should be ignored)",
		array("name"=>"Steffen", "greeting"=>"willkommen", "goodbye"=>"wiedersehen"));
_t('i18nTestModule.INJECTIONS4', array("name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"));
_t('i18nTestModule.INJECTIONS6', "Hello {name} {greeting}. But it is late, {goodbye}",
	["name"=>"Paul", "greeting"=>"good you are here", "goodbye"=>"see you"]);
_t("i18nTestModule.INJECTIONS7", "Hello {name} {greeting}. But it is late, {goodbye}",
		"New context (this should be ignored)",
		["name"=>"Steffen", "greeting"=>"willkommen", "goodbye"=>"wiedersehen"]);
_t('i18nTestModule.INJECTIONS8', ["name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"]);
_t('i18nTestModule.INJECTIONS9', "An item|{count} items", ['count' => 4], "Test Pluralisation");
PHP;

        $collectedTranslatables = $c->collectFromCode($php, 'mymodule');

        $expectedArray = [
            'i18nTestModule.INJECTIONS2' => "Hello {name} {greeting}. But it is late, {goodbye}",
            'i18nTestModule.INJECTIONS3' => [
                'default' => "Hello {name} {greeting}. But it is late, {goodbye}",
                'comment' => 'New context (this should be ignored)'
            ],
            'i18nTestModule.INJECTIONS6' => "Hello {name} {greeting}. But it is late, {goodbye}",
            'i18nTestModule.INJECTIONS7' => [
                'default' => "Hello {name} {greeting}. But it is late, {goodbye}",
                'comment' => "New context (this should be ignored)",
            ],
            'i18nTestModule.INJECTIONS9' => [
                'one' => 'An item',
                'other' => '{count} items',
                'comment' => 'Test Pluralisation',
            ],
            'i18nTestModule.NEWMETHODSIG' => "New _t method signature test",
        ];
        $this->assertEquals($expectedArray, $collectedTranslatables);

        // Test warning is raised on empty default
        $this->setExpectedException(
            PHPUnit_Framework_Error_Notice::class,
            'Missing localisation default for key i18nTestModule.INJECTIONS4'
        );
        $php = <<<PHP
_t('i18nTestModule.INJECTIONS4', array("name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"));
PHP;
        $c->setWarnOnEmptyDefault(true);
        $c->collectFromCode($php, 'mymodule');
    }

    public function testUncollectableCode()
    {
        $c = i18nTextCollector::create();

        $php = <<<PHP
_t(static::class.'.KEY1', 'Default');
_t(self::class.'.KEY2', 'Default');
_t(__CLASS__.'.KEY3', 'Default');
_t('Collectable.KEY4', 'Default');
PHP;

        $collectedTranslatables = $c->collectFromCode($php, 'mymodule');

        // Only one item is collectable
        $expectedArray = [ 'Collectable.KEY4' => 'Default' ];
        $this->assertEquals($expectedArray, $collectedTranslatables);
    }

    public function testCollectFromIncludedTemplates()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false); // Disable warnings for tests

        $templateFilePath = $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss';
        $html = file_get_contents($templateFilePath);
        $matches = $c->collectFromTemplate($html, 'mymodule', 'RandomNamespace');

        $this->assertArrayHasKey('RandomNamespace.LAYOUTTEMPLATENONAMESPACE', $matches);
        $this->assertEquals(
            'Layout Template no namespace',
            $matches['RandomNamespace.LAYOUTTEMPLATENONAMESPACE']
        );
        $this->assertArrayHasKey('RandomNamespace.SPRINTFNONAMESPACE', $matches);
        $this->assertEquals(
            'My replacement no namespace: %s',
            $matches['RandomNamespace.SPRINTFNONAMESPACE']
        );
        $this->assertArrayHasKey('i18nTestModule.LAYOUTTEMPLATE', $matches);
        $this->assertEquals(
            'Layout Template',
            $matches['i18nTestModule.LAYOUTTEMPLATE']
        );
        $this->assertArrayHasKey('i18nTestModule.SPRINTFNAMESPACE', $matches);
        $this->assertEquals(
            'My replacement: %s',
            $matches['i18nTestModule.SPRINTFNAMESPACE']
        );

        // Includes should not automatically inject translations into parent templates
        $this->assertArrayNotHasKey('i18nTestModule.WITHNAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude.ss.NONAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude.ss.SPRINTFINCLUDENONAMESPACE', $matches);
    }

    public function testCollectFromThemesTemplates()
    {
        $c = i18nTextCollector::create();
        SSViewer::set_themes([ 'testtheme1' ]);

        // Collect from layout
        $layoutFilePath = $this->alternateBasePath . '/themes/testtheme1/templates/Layout/i18nTestTheme1.ss';
        $layoutHTML = file_get_contents($layoutFilePath);
        $layoutMatches = $c->collectFromTemplate($layoutHTML, 'themes/testtheme1', 'i18nTestTheme1.ss');

        // all entities from i18nTestTheme1.ss
        $this->assertEquals(
            [
                'i18nTestTheme1.LAYOUTTEMPLATE' => 'Theme1 Layout Template',
                'i18nTestTheme1.SPRINTFNAMESPACE' => 'Theme1 My replacement: %s',
                'i18nTestTheme1.ss.LAYOUTTEMPLATENONAMESPACE' => 'Theme1 Layout Template no namespace',
                'i18nTestTheme1.ss.SPRINTFNONAMESPACE' => 'Theme1 My replacement no namespace: %s',
            ],
            $layoutMatches
        );

        // Collect from include
        $includeFilePath = $this->alternateBasePath . '/themes/testtheme1/templates/Includes/i18nTestTheme1Include.ss';
        $includeHTML = file_get_contents($includeFilePath);
        $includeMatches = $c->collectFromTemplate($includeHTML, 'themes/testtheme1', 'i18nTestTheme1Include.ss');

        // all entities from i18nTestTheme1Include.ss
        $this->assertEquals(
            [
                'i18nTestTheme1Include.SPRINTFINCLUDENAMESPACE' => 'Theme1 My include replacement: %s',
                'i18nTestTheme1Include.WITHNAMESPACE' => 'Theme1 Include Entity with Namespace',
                'i18nTestTheme1Include.ss.NONAMESPACE' => 'Theme1 Include Entity without Namespace',
                'i18nTestTheme1Include.ss.SPRINTFINCLUDENONAMESPACE' => 'Theme1 My include replacement no namespace: %s'
            ],
            $includeMatches
        );
    }

    public function testCollectMergesWithExisting()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);
        $c->setWriter(new YamlWriter());
        $c->basePath = $this->alternateBasePath;
        $c->baseSavePath = $this->alternateBaseSavePath;

        $entitiesByModule = $c->collect(null, true /* merge */);
        $this->assertArrayHasKey(
            'i18nTestModule.ENTITY',
            $entitiesByModule['i18ntestmodule'],
            'Retains existing entities'
        );
        $this->assertArrayHasKey(
            'i18nTestModule.NEWENTITY',
            $entitiesByModule['i18ntestmodule'],
            'Adds new entities'
        );

        // Test cross-module strings are set correctly
        $this->assertArrayHasKey(
            'i18nProviderClass.OTHER_MODULE',
            $entitiesByModule['i18ntestmodule']
        );
        $this->assertEquals(
            [
                'comment' => 'Test string in another module',
                'default' => 'i18ntestmodule string defined in i18nothermodule',
            ],
            $entitiesByModule['i18ntestmodule']['i18nProviderClass.OTHER_MODULE']
        );
    }

    public function testCollectFromFilesystemAndWriteMasterTables()
    {
        i18n::set_locale('en_US');  //set the locale to the US locale expected in the asserts
        i18n::config()->update('default_locale', 'en_US');
        i18n::config()->update('missing_default_warning', false);

        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);
        $c->setWriter(new YamlWriter());
        $c->basePath = $this->alternateBasePath;
        $c->baseSavePath = $this->alternateBaseSavePath;

        $c->run();

        // i18ntestmodule
        $moduleLangFile = "{$this->alternateBaseSavePath}/i18ntestmodule/lang/" . $c->getDefaultLocale() . '.yml';
        $this->assertTrue(
            file_exists($moduleLangFile),
            'Master language file can be written to modules /lang folder'
        );

        $moduleLangFileContent = file_get_contents($moduleLangFile);
        $this->assertContains(
            "    ADDITION: Addition\n",
            $moduleLangFileContent
        );
        $this->assertContains(
            "    ENTITY: 'Entity with \"Double Quotes\"'\n",
            $moduleLangFileContent
        );
        $this->assertContains(
            "    MAINTEMPLATE: 'Main Template'\n",
            $moduleLangFileContent
        );
        $this->assertContains(
            "    OTHERENTITY: 'Other Entity'\n",
            $moduleLangFileContent
        );
        $this->assertContains(
            "    WITHNAMESPACE: 'Include Entity with Namespace'\n",
            $moduleLangFileContent
        );
        $this->assertContains(
            "    NONAMESPACE: 'Include Entity without Namespace'\n",
            $moduleLangFileContent
        );

        // i18nothermodule
        $otherModuleLangFile = "{$this->alternateBaseSavePath}/i18nothermodule/lang/" . $c->getDefaultLocale() . '.yml';
        $this->assertTrue(
            file_exists($otherModuleLangFile),
            'Master language file can be written to modules /lang folder'
        );
        $otherModuleLangFileContent = file_get_contents($otherModuleLangFile);
        $this->assertContains(
            "    ENTITY: 'Other Module Entity'\n",
            $otherModuleLangFileContent
        );
        $this->assertContains(
            "    MAINTEMPLATE: 'Main Template Other Module'\n",
            $otherModuleLangFileContent
        );

        // testtheme1
        $theme1LangFile = "{$this->alternateBaseSavePath}/themes/testtheme1/lang/" . $c->getDefaultLocale() . '.yml';
        $this->assertTrue(
            file_exists($theme1LangFile),
            'Master theme language file can be written to themes/testtheme1 /lang folder'
        );
        $theme1LangFileContent = file_get_contents($theme1LangFile);
        $this->assertContains(
            "    MAINTEMPLATE: 'Theme1 Main Template'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    LAYOUTTEMPLATE: 'Theme1 Layout Template'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    SPRINTFNAMESPACE: 'Theme1 My replacement: %s'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    LAYOUTTEMPLATENONAMESPACE: 'Theme1 Layout Template no namespace'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    SPRINTFNONAMESPACE: 'Theme1 My replacement no namespace: %s'\n",
            $theme1LangFileContent
        );

        $this->assertContains(
            "    SPRINTFINCLUDENAMESPACE: 'Theme1 My include replacement: %s'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    WITHNAMESPACE: 'Theme1 Include Entity with Namespace'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    NONAMESPACE: 'Theme1 Include Entity without Namespace'\n",
            $theme1LangFileContent
        );
        $this->assertContains(
            "    SPRINTFINCLUDENONAMESPACE: 'Theme1 My include replacement no namespace: %s'\n",
            $theme1LangFileContent
        );

        // testtheme2
        $theme2LangFile = "{$this->alternateBaseSavePath}/themes/testtheme2/lang/" . $c->getDefaultLocale() . '.yml';
        $this->assertTrue(
            file_exists($theme2LangFile),
            'Master theme language file can be written to themes/testtheme2 /lang folder'
        );
        $theme2LangFileContent = file_get_contents($theme2LangFile);
        $this->assertContains(
            "    MAINTEMPLATE: 'Theme2 Main Template'\n",
            $theme2LangFileContent
        );
    }

    public function testCollectFromEntityProvidersInCustomObject()
    {
        // note: Disable _fakewebroot manifest for this test
        $this->popManifests();

        $c = i18nTextCollector::create();

        // Collect from MyObject.php
        $filePath = __DIR__ . '/i18nTest/MyObject.php';
        $matches = $c->collectFromEntityProviders($filePath);
        $this->assertEquals(
            [
                'SilverStripe\Admin\LeftAndMain.OTHER_TITLE' => [
                    'default' => 'Other title',
                    'module' => 'admin',
                ],
                'SilverStripe\i18n\Tests\i18nTest\MyObject.PLURALNAME' => 'My Objects',
                'SilverStripe\i18n\Tests\i18nTest\MyObject.PLURALS' => [
                    'one' => 'A My Object',
                    'other' => '{count} My Objects',
                ],
                'SilverStripe\i18n\Tests\i18nTest\MyObject.SINGULARNAME' => 'My Object',
            ],
            $matches
        );
    }

    public function testCollectFromEntityProvidersInWebRoot()
    {
        // Collect from i18nProviderClass
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);
        $c->setWriter(new YamlWriter());
        $c->basePath = $this->alternateBasePath;
        $c->baseSavePath = $this->alternateBaseSavePath;
        $entitiesByModule = $c->collect(null, false);
        $this->assertEquals(
            [
                'comment' => 'Plural forms for the test class',
                'one' => 'A class',
                'other' => '{count} classes',
            ],
            $entitiesByModule['i18nothermodule']['i18nProviderClass.PLURALS']
        );
        $this->assertEquals(
            'My Provider Class',
            $entitiesByModule['i18nothermodule']['i18nProviderClass.TITLE']
        );
        $this->assertEquals(
            [
                'comment' => 'Test string in another module',
                'default' => 'i18ntestmodule string defined in i18nothermodule',
            ],
            $entitiesByModule['i18ntestmodule']['i18nProviderClass.OTHER_MODULE']
        );
    }

    /**
     * Test that duplicate keys are resolved to the appropriate modules
     */
    public function testResolveDuplicates()
    {
        $collector = new Collector();

        // Dummy data as collected
        $data1 = [
            'i18ntestmodule' => [
                'i18nTestModule.PLURALNAME' => 'Data Objects',
                'i18nTestModule.SINGULARNAME' => 'Data Object',
            ],
            'mymodule' => [
                'i18nTestModule.PLURALNAME' => 'Ignored String',
                'i18nTestModule.STREETNAME' => 'Shortland Street',
            ],
        ];
        $expected = [
            'i18ntestmodule' => [
                'i18nTestModule.PLURALNAME' => 'Data Objects',
                'i18nTestModule.SINGULARNAME' => 'Data Object',
            ],
            'mymodule' => [
                // Removed PLURALNAME because this key doesn't exist in i18ntestmodule strings
                'i18nTestModule.STREETNAME' => 'Shortland Street'
            ]
        ];

        $resolved = $collector->resolveDuplicateConflicts_Test($data1);
        $this->assertEquals($expected, $resolved);

        // Test getConflicts
        $data2 = [
            'module1' => [
                'i18ntestmodule.ONE' => 'One',
                'i18ntestmodule.TWO' => 'Two',
                'i18ntestmodule.THREE' => 'Three',
            ],
            'module2' => [
                'i18ntestmodule.THREE' => 'Three',
            ],
            'module3' => [
                'i18ntestmodule.TWO' => 'Two',
                'i18ntestmodule.THREE' => 'Three',
            ],
        ];
        $conflictsA = $collector->getConflicts_Test($data2);
        sort($conflictsA);
        $this->assertEquals(
            array('i18ntestmodule.THREE', 'i18ntestmodule.TWO'),
            $conflictsA
        );

        // Removing module3 should remove a conflict
        unset($data2['module3']);
        $conflictsB = $collector->getConflicts_Test($data2);
        $this->assertEquals(
            array('i18ntestmodule.THREE'),
            $conflictsB
        );
    }

    /**
     * Test ability for textcollector to detect modules
     */
    public function testModuleDetection()
    {
        $collector = new Collector();
        $modules = $collector->getModules_Test($this->alternateBasePath);
        $this->assertEquals(
            array(
                'i18nnonstandardmodule',
                'i18nothermodule',
                'i18ntestmodule',
                'themes/testtheme1',
                'themes/testtheme2'
            ),
            $modules
        );

        $this->assertEquals('i18ntestmodule', $collector->findModuleForClass_Test('i18nTestNamespacedClass'));
        $this->assertEquals(
            'i18ntestmodule',
            $collector->findModuleForClass_Test('i18nTest\\i18nTestNamespacedClass')
        );
        $this->assertEquals('i18ntestmodule', $collector->findModuleForClass_Test('i18nTestSubModule'));
    }

    /**
     * Test that text collector can detect module file lists properly
     */
    public function testModuleFileList()
    {
        $collector = new Collector();
        $collector->basePath = $this->alternateBasePath;
        $collector->baseSavePath = $this->alternateBaseSavePath;

        // Non-standard modules can't be safely filtered, so just index everything
        $nonStandardFiles = $collector->getFileListForModule_Test('i18nnonstandardmodule');
        $nonStandardRoot = $this->alternateBasePath . '/i18nnonstandardmodule';
        $this->assertEquals(3, count($nonStandardFiles));
        $this->assertArrayHasKey("{$nonStandardRoot}/_config.php", $nonStandardFiles);
        $this->assertArrayHasKey("{$nonStandardRoot}/phpfile.php", $nonStandardFiles);
        $this->assertArrayHasKey("{$nonStandardRoot}/template.ss", $nonStandardFiles);

        // Normal module should have predictable dir structure
        $testFiles = $collector->getFileListForModule_Test('i18ntestmodule');
        $testRoot = $this->alternateBasePath . '/i18ntestmodule';
        $this->assertEquals(7, count($testFiles));
        // Code in code folder is detected
        $this->assertArrayHasKey("{$testRoot}/code/i18nTestModule.php", $testFiles);
        $this->assertArrayHasKey("{$testRoot}/code/subfolder/_config.php", $testFiles);
        $this->assertArrayHasKey("{$testRoot}/code/subfolder/i18nTestSubModule.php", $testFiles);
        $this->assertArrayHasKey("{$testRoot}/code/subfolder/i18nTestNamespacedClass.php", $testFiles);
        // Templates in templates folder is detected
        $this->assertArrayHasKey("{$testRoot}/templates/Includes/i18nTestModuleInclude.ss", $testFiles);
        $this->assertArrayHasKey("{$testRoot}/templates/Layout/i18nTestModule.ss", $testFiles);
        $this->assertArrayHasKey("{$testRoot}/templates/i18nTestModule.ss", $testFiles);

        // Standard modules with code in odd places should only have code in those directories detected
        $otherFiles = $collector->getFileListForModule_Test('i18nothermodule');
        $otherRoot = $this->alternateBasePath . '/i18nothermodule';
        $this->assertEquals(4, count($otherFiles));
        // Only detect well-behaved files
        $this->assertArrayHasKey("{$otherRoot}/code/i18nOtherModule.php", $otherFiles);
        $this->assertArrayHasKey("{$otherRoot}/code/i18nProviderClass.php", $otherFiles);
        $this->assertArrayHasKey("{$otherRoot}/code/i18nTestModuleDecorator.php", $otherFiles);
        $this->assertArrayHasKey("{$otherRoot}/templates/i18nOtherModule.ss", $otherFiles);

        // Themes should detect all ss files only
        $theme1Files = $collector->getFileListForModule_Test('themes/testtheme1');
        $theme1Root = $this->alternateBasePath . '/themes/testtheme1/templates';
        $this->assertEquals(3, count($theme1Files));
        // Find only ss files
        $this->assertArrayHasKey("{$theme1Root}/Includes/i18nTestTheme1Include.ss", $theme1Files);
        $this->assertArrayHasKey("{$theme1Root}/Layout/i18nTestTheme1.ss", $theme1Files);
        $this->assertArrayHasKey("{$theme1Root}/i18nTestTheme1Main.ss", $theme1Files);

        // Only 1 file here
        $theme2Files = $collector->getFileListForModule_Test('themes/testtheme2');
        $this->assertEquals(1, count($theme2Files));
        $this->assertArrayHasKey(
            $this->alternateBasePath . '/themes/testtheme2/templates/i18nTestTheme2.ss',
            $theme2Files
        );
    }
}
