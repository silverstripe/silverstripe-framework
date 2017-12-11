<?php

namespace SilverStripe\i18n\Tests;

use PHPUnit_Framework_Error_Notice;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use SilverStripe\i18n\Messages\YamlWriter;
use SilverStripe\i18n\Tests\i18nTextCollectorTest\Collector;

class i18nTextCollectorTest extends SapphireTest
{
    use i18nTestManifest;

    /**
     * @var string
     */
    protected $alternateBaseSavePath = null;

    protected function setUp()
    {
        parent::setUp();
        $this->setupManifest();

        $this->alternateBaseSavePath = TEMP_PATH . DIRECTORY_SEPARATOR . 'i18nTextCollectorTest_webroot';
        Filesystem::makeFolder($this->alternateBaseSavePath);
    }

    protected function tearDown()
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
        $module = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

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
            $c->collectFromCode($php, null, $module)
        );
    }

    public function testCollectFromNewTemplateSyntaxUsingParserSubclass()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $html = <<<SS
        <% _t('Test.SINGLEQUOTE','Single Quote'); %>
<%t i18nTestModule.NEWMETHODSIG "New _t method signature test" %>
<%t i18nTestModule.INJECTIONS_0 "Hello {name} {greeting}, and {goodbye}" name="Mark" greeting="welcome" goodbye="bye" %>
<%t i18nTestModule.INJECTIONS_1 "Hello {name} {greeting}, and {goodbye}" name="Paul" greeting="welcome" goodbye="cya" %>
<%t i18nTestModule.INJECTIONS_2 "Hello {name} {greeting}" is "context (ignored)" name="Steffen" greeting="Wilkommen" %>
<%t i18nTestModule.INJECTIONS_3 name="Cat" greeting='meow' goodbye="meow" %>
<%t i18nTestModule.INJECTIONS_4 name=\$absoluteBaseURL greeting=\$get_locale goodbye="global calls" %>
<%t i18nTestModule.INJECTIONS_9 "An item|{count} items" is "Test Pluralisation" count=4 %>
<%t SilverStripe\\TestModule\\i18nTestModule.INJECTIONS_10 "This string is namespaced" %>
<%t SilverStripe\\\\TestModule\\\\i18nTestModule.INJECTIONS_11 "Escaped namespaced string" %>
SS;
        $c->collectFromTemplate($html, null, $mymodule);

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
                'SilverStripe\\TestModule\\i18nTestModule.INJECTIONS_10' => 'This string is namespaced',
                'SilverStripe\\TestModule\\i18nTestModule.INJECTIONS_11' => 'Escaped namespaced string'
            ],
            $c->collectFromTemplate($html, null, $mymodule)
        );

        // Test warning is raised on empty default
        $c->setWarnOnEmptyDefault(true);
        $this->expectException(PHPUnit_Framework_Error_Notice::class);
        $this->expectExceptionMessage('Missing localisation default for key i18nTestModule.INJECTIONS_3');

        $c->collectFromTemplate($html, null, $mymodule);
    }

    public function testCollectFromTemplateSimple()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $html = <<<SS
<%t Test.SINGLEQUOTE 'Single Quote' %>
SS;
        $this->assertEquals(
            [ 'Test.SINGLEQUOTE' => 'Single Quote' ],
            $c->collectFromTemplate($html, null, $mymodule)
        );

        $html = <<<SS
<%t   Test.DOUBLEQUOTE "Double Quote and Spaces"    %>
SS;
        $this->assertEquals(
            [ 'Test.DOUBLEQUOTE' => "Double Quote and Spaces" ],
            $c->collectFromTemplate($html, null, $mymodule)
        );

        $html = <<<SS
<%t Test.NOSEMICOLON "No Semicolon" %>
SS;
        $this->assertEquals(
            [ 'Test.NOSEMICOLON' => "No Semicolon" ],
            $c->collectFromTemplate($html, null, $mymodule)
        );
    }

    public function testCollectFromTemplateAdvanced()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false);
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $html = <<<SS
<%t Test.PRIOANDCOMMENT ' Prio and Value with "Double Quotes"' is 'Comment with "Double Quotes"' %>
SS;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => [
                'default' => ' Prio and Value with "Double Quotes"',
                'comment' => 'Comment with "Double Quotes"',
            ]],
            $c->collectFromTemplate($html, 'Test', $mymodule)
        );

        $html = <<<SS
<%t Test.PRIOANDCOMMENT " Prio and Value with 'Single Quotes'" is "Comment with 'Single Quotes'" %>
SS;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => [
                'default' => " Prio and Value with 'Single Quotes'",
                'comment' => "Comment with 'Single Quotes'",
            ]],
            $c->collectFromTemplate($html, 'Test', $mymodule)
        );

        // Test empty
        $html = <<<SS
<%t Test.PRIOANDCOMMENT %>
SS;
        $this->assertEquals(
            [],
            $c->collectFromTemplate($html, null, $mymodule)
        );

        // Test warning is raised on empty default
        $c->setWarnOnEmptyDefault(true);
        $this->expectException(PHPUnit_Framework_Error_Notice::class);
        $this->expectExceptionMessage('Missing localisation default for key Test.PRIOANDCOMMENT');

        $c->collectFromTemplate($html, 'Test', $mymodule);
    }


    public function testCollectFromCodeSimple()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $php = <<<PHP
_t('Test.SINGLEQUOTE','Single Quote');
PHP;
        $this->assertEquals(
            [ 'Test.SINGLEQUOTE' => 'Single Quote' ],
            $c->collectFromCode($php, null, $mymodule)
        );

        $php = <<<PHP
_t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   );
PHP;
        $this->assertEquals(
            [ 'Test.DOUBLEQUOTE' => "Double Quote and Spaces" ],
            $c->collectFromCode($php, null, $mymodule)
        );
    }

    public function testCollectFromCodeAdvanced()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $php = <<<PHP
_t(
	'Test.NEWLINES',
	'New Lines'
);
PHP;
        $this->assertEquals(
            [ 'Test.NEWLINES' => "New Lines" ],
            $c->collectFromCode($php, null, $mymodule)
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
            $c->collectFromCode($php, null, $mymodule)
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
            $c->collectFromCode($php, null, $mymodule)
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	'Value with \'Escaped Single Quotes\''
);
PHP;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => "Value with 'Escaped Single Quotes'" ],
            $c->collectFromCode($php, null, $mymodule)
        );

        $php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	"Doublequoted Value with 'Unescaped Single Quotes'"


);
PHP;
        $this->assertEquals(
            [ 'Test.PRIOANDCOMMENT' => "Doublequoted Value with 'Unescaped Single Quotes'"],
            $c->collectFromCode($php, null, $mymodule)
        );
    }

    public function testCollectFromCodeNamespace()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');
        $php = <<<PHP
<?php
namespace SilverStripe\Framework\Core;

class MyClass extends Base implements SomeService {
    public function getNewLines(\$class) {
        if (!is_subclass_of(\$class, DataObject::class) || !Object::has_extension(\$class, Versioned::class)) {
            return null;
        }
        return _t(
            __CLASS__.'.NEWLINES',
            'New Lines'
        );
    }
    public function getAnotherString() {
        return _t(
            'SilverStripe\\\\Framework\\\\MyClass.ANOTHER_STRING',
            'Slash=\\\\, Quote=\\''
        );
    }
    public function getDoubleQuotedString() {
        return _t(
            "SilverStripe\\\\Framework\\\\MyClass.DOUBLE_STRING",
            "Slash=\\\\, Quote=\\""
        );
    }
    public function getMagicConstantStringFromSelf()
    {
        return _t(
            self::class . '.SELF_CLASS',
            'Self Class'
        );
    }
}
PHP;
        $this->assertEquals(
            [
                'SilverStripe\\Framework\\Core\\MyClass.NEWLINES' => "New Lines",
                'SilverStripe\\Framework\\MyClass.ANOTHER_STRING' => 'Slash=\\, Quote=\'',
                'SilverStripe\\Framework\\MyClass.DOUBLE_STRING' => 'Slash=\\, Quote="',
                'SilverStripe\\Framework\\Core\\MyClass.SELF_CLASS' => 'Self Class',
            ],
            $c->collectFromCode($php, null, $mymodule)
        );
    }


    public function testNewlinesInEntityValues()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

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
            $c->collectFromCode($php, null, $mymodule)
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
            $c->collectFromCode($php, null, $mymodule)
        );
    }

    /**
     * Test extracting entities from the new _t method signature
     */
    public function testCollectFromCodeNewSignature()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false); // Disable warnings for tests
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

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

        $collectedTranslatables = $c->collectFromCode($php, null, $mymodule);

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
        $this->expectException(PHPUnit_Framework_Error_Notice::class);
        $this->expectExceptionMessage('Missing localisation default for key i18nTestModule.INJECTIONS4');

        $php = <<<PHP
_t('i18nTestModule.INJECTIONS4', array("name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"));
PHP;
        $c->setWarnOnEmptyDefault(true);
        $c->collectFromCode($php, null, $mymodule);
    }

    public function testUncollectableCode()
    {
        $c = i18nTextCollector::create();
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $php = <<<PHP
_t(static::class.'.KEY1', 'Default');
_t(parent::class.'.KEY1', 'Default');
_t('Collectable.KEY4', 'Default');
PHP;

        $collectedTranslatables = $c->collectFromCode($php, null, $mymodule);

        // Only one item is collectable
        $expectedArray = [ 'Collectable.KEY4' => 'Default' ];
        $this->assertEquals($expectedArray, $collectedTranslatables);
    }

    public function testCollectFromIncludedTemplates()
    {
        $c = i18nTextCollector::create();
        $c->setWarnOnEmptyDefault(false); // Disable warnings for tests
        $mymodule = ModuleLoader::inst()->getManifest()->getModule('i18ntestmodule');

        $templateFilePath = $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss';
        $html = file_get_contents($templateFilePath);
        $matches = $c->collectFromTemplate($html, $templateFilePath, $mymodule);

        $this->assertArrayHasKey('i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE', $matches);
        $this->assertEquals(
            'Layout Template no namespace',
            $matches['i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE']
        );
        $this->assertArrayHasKey('i18nTestModule.ss.REPLACEMENTNONAMESPACE', $matches);
        $this->assertEquals(
            'My replacement no namespace: {replacement}',
            $matches['i18nTestModule.ss.REPLACEMENTNONAMESPACE']
        );
        $this->assertArrayHasKey('i18nTestModule.LAYOUTTEMPLATE', $matches);
        $this->assertEquals(
            'Layout Template',
            $matches['i18nTestModule.LAYOUTTEMPLATE']
        );
        $this->assertArrayHasKey('i18nTestModule.REPLACEMENTNAMESPACE', $matches);
        $this->assertEquals(
            'My replacement: {replacement}',
            $matches['i18nTestModule.REPLACEMENTNAMESPACE']
        );

        // Includes should not automatically inject translations into parent templates
        $this->assertArrayNotHasKey('i18nTestModule.WITHNAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude_ss.NONAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude_ss.REPLACEMENTINCLUDENAMESPACE', $matches);
        $this->assertArrayNotHasKey('i18nTestModuleInclude_ss.REPLACEMENTINCLUDENONAMESPACE', $matches);
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
        $modules = ModuleLoader::inst()->getManifest()->getModules();
        $this->assertEquals(
            [
                'i18nnonstandardmodule',
                'i18nothermodule',
                'i18ntestmodule',
            ],
            array_keys($modules)
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
    }
}
