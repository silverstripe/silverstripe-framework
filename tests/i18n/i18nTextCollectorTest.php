<?php
/**
 * @package framework
 * @subpackage tests
 */
class i18nTextCollectorTest extends SapphireTest {
	
	/**
	 * @var string $tmpBasePath Used to write language files.
	 * We don't want to store them inside framework (or in any web-accessible place)
	 * in case something goes wrong with the file parsing.
	 */
	protected $alternateBaseSavePath;
	
	/**
	 * @var string $alternateBasePath Fake webroot with a single module
	 * /i18ntestmodule which contains some files with _t() calls.
	 */
	protected $alternateBasePath;
	
	protected $manifest;
	
	public function setUp() {
		parent::setUp();
		
		$this->alternateBasePath = $this->getCurrentAbsolutePath() . "/_fakewebroot";
		$this->alternateBaseSavePath = TEMP_FOLDER . '/i18nTextCollectorTest_webroot';
		Filesystem::makeFolder($this->alternateBaseSavePath);

		// Push a class and template loader running from the fake webroot onto
		// the stack.
		$this->manifest = new SS_ClassManifest(
			$this->alternateBasePath, false, true, false
		);
		
		$manifest = new SS_TemplateManifest($this->alternateBasePath, null, false, true);
		$manifest->regenerate(false);
		SS_TemplateLoader::instance()->pushManifest($manifest);
	}
	
	public function tearDown() {
		SS_TemplateLoader::instance()->popManifest();
		parent::tearDown();
	}

	public function testConcatenationInEntityValues() {
		$c = new i18nTextCollector();

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
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.CONCATENATED' => array("Line 1 and Line '2' and Line \"3\"",'Comment'),
				'Test.CONCATENATED2' => array("Line \"4\" and Line 5")
			)
		);
	}

	public function testCollectFromNewTemplateSyntaxUsingParserSubclass() {
			$c = new i18nTextCollector();

			$html = <<<SS
			<% _t('Test.SINGLEQUOTE','Single Quote'); %>
<%t i18nTestModule.NEWMETHODSIG "New _t method signature test" %>
<%t i18nTestModule.INJECTIONS_0 "Hello {name} {greeting}, and {goodbye}" name="Mark" greeting="welcome" goodbye="bye" %>
<%t i18nTestModule.INJECTIONS_1 "Hello {name} {greeting}, and {goodbye}" name="Paul" greeting="welcome" goodbye="cya" %>
<%t i18nTestModule.INJECTIONS_2 "Hello {name} {greeting}" is "context (ignored)" name="Steffen" greeting="Wilkommen" %>
<%t i18nTestModule.INJECTIONS_3 name="Cat" greeting='meow' goodbye="meow" %>
<%t i18nTestModule.INJECTIONS_4 name=\$absoluteBaseURL greeting=\$get_locale goodbye="global calls" %>
SS;
		$c->collectFromTemplate($html, 'mymodule', 'Test');

		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.SINGLEQUOTE' => array('Single Quote'),
				'i18nTestModule.NEWMETHODSIG' => array("New _t method signature test",null,null),
				'i18nTestModule.INJECTIONS_0' => array("Hello {name} {greeting}, and {goodbye}", null, null),
				'i18nTestModule.INJECTIONS_1' => array("Hello {name} {greeting}, and {goodbye}", null, null),
				'i18nTestModule.INJECTIONS_2' => array("Hello {name} {greeting}", null, "context (ignored)"),
				'i18nTestModule.INJECTIONS_3' => array(null, null, null),
				'i18nTestModule.INJECTIONS_4' => array(null, null, null),
			)
		);
	}

	public function testCollectFromTemplateSimple() {
		$c = new i18nTextCollector();

		$html = <<<SS
<% _t('Test.SINGLEQUOTE','Single Quote'); %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.SINGLEQUOTE' => array('Single Quote')
			)
		);

		$html = <<<SS
<% _t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   ); %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.DOUBLEQUOTE' => array("Double Quote and Spaces")
			)
		);
		
		$html = <<<SS
<% _t("Test.NOSEMICOLON","No Semicolon") %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.NOSEMICOLON' => array("No Semicolon")
			)
		);
	}

	public function testCollectFromTemplateAdvanced() {
		$c = new i18nTextCollector();

		$html = <<<SS
<% _t(
	'NEWLINES',
	'New Lines'
) %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.NEWLINES' => array("New Lines")
			)
		);

		$html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	' Prio and Value with "Double Quotes"',
	'Comment with "Double Quotes"'
) %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.PRIOANDCOMMENT' => array(' Prio and Value with "Double Quotes"','Comment with "Double Quotes"')
			)
		);

		$html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	" Prio and Value with 'Single Quotes'",
	
	"Comment with 'Single Quotes'"
) %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.PRIOANDCOMMENT' => array(" Prio and Value with 'Single Quotes'","Comment with 'Single Quotes'")
			)
		);
	}


	public function testCollectFromCodeSimple() {
		$c = new i18nTextCollector();
			
		$php = <<<PHP
_t('Test.SINGLEQUOTE','Single Quote');
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.SINGLEQUOTE' => array('Single Quote')
			)
		);
		
		$php = <<<PHP
_t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   );
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.DOUBLEQUOTE' => array("Double Quote and Spaces")
			)
		);
	}
	
	public function testCollectFromCodeAdvanced() {
		$c = new i18nTextCollector();

		$php = <<<PHP
_t(
	'Test.NEWLINES',
	'New Lines'
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.NEWLINES' => array("New Lines")
			)
		);
		
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	' Value with "Double Quotes"',
	
	'Comment with "Double Quotes"'
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array(' Value with "Double Quotes"','Comment with "Double Quotes"')
			)
		);
		
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	" Value with 'Single Quotes'",
	
	"Comment with 'Single Quotes'"
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array(" Value with 'Single Quotes'","Comment with 'Single Quotes'")
			)
		);
		
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	'Value with \'Escaped Single Quotes\''
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array("Value with 'Escaped Single Quotes'")
			)
		);
	
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	"Doublequoted Value with 'Unescaped Single Quotes'"
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array("Doublequoted Value with 'Unescaped Single Quotes'")
			)
		);
	}
	
	
	public function testNewlinesInEntityValues() {
		$c = new i18nTextCollector();

		$php = <<<PHP
_t(
'Test.NEWLINESINGLEQUOTE',
'Line 1
Line 2'
);
PHP;

		$eol = PHP_EOL;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.NEWLINESINGLEQUOTE' => array("Line 1{$eol}Line 2")
			)
		);

		$php = <<<PHP
_t(
'Test.NEWLINEDOUBLEQUOTE',
"Line 1
Line 2"
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.NEWLINEDOUBLEQUOTE' => array("Line 1{$eol}Line 2")
			)
		);
	}

	/**
	 * Test extracting entities from the new _t method signature
	 */
	public function testCollectFromCodeNewSignature() {
		$c = new i18nTextCollector();

		$php = <<<PHP
_t('i18nTestModule.NEWMETHODSIG',"New _t method signature test");
_t('i18nTestModule.INJECTIONS1','_DOES_NOT_EXIST', "Hello {name} {greeting}. But it is late, {goodbye}",
	array("name"=>"Mark", "greeting"=>"welcome", "goodbye"=>"bye"));
_t('i18nTestModule.INJECTIONS2', "Hello {name} {greeting}. But it is late, {goodbye}",
	array("name"=>"Paul", "greeting"=>"good you are here", "goodbye"=>"see you"));
_t("i18nTestModule.INJECTIONS3", "Hello {name} {greeting}. But it is late, {goodbye}",
		"New context (this should be ignored)",
		array("name"=>"Steffen", "greeting"=>"willkommen", "goodbye"=>"wiedersehen"));
_t('i18nTestModule.INJECTIONS4', array("name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"));
_t('i18nTestModule.INJECTIONS5','_DOES_NOT_EXIST', "Hello {name} {greeting}. But it is late, {goodbye}",
	["name"=>"Mark", "greeting"=>"welcome", "goodbye"=>"bye"]);
_t('i18nTestModule.INJECTIONS6', "Hello {name} {greeting}. But it is late, {goodbye}",
	["name"=>"Paul", "greeting"=>"good you are here", "goodbye"=>"see you"]);
_t("i18nTestModule.INJECTIONS7", "Hello {name} {greeting}. But it is late, {goodbye}",
		"New context (this should be ignored)",
		["name"=>"Steffen", "greeting"=>"willkommen", "goodbye"=>"wiedersehen"]);
_t('i18nTestModule.INJECTIONS8', ["name"=>"Cat", "greeting"=>"meow", "goodbye"=>"meow"]);
PHP;

		$collectedTranslatables = $c->collectFromCode($php, 'mymodule');

		$expectedArray = (array(
			'i18nTestModule.NEWMETHODSIG' => array("New _t method signature test"),
			'i18nTestModule.INJECTIONS1' => array("_DOES_NOT_EXIST",
				"Hello {name} {greeting}. But it is late, {goodbye}"),
			'i18nTestModule.INJECTIONS2' => array("Hello {name} {greeting}. But it is late, {goodbye}"),
			'i18nTestModule.INJECTIONS3' => array("Hello {name} {greeting}. But it is late, {goodbye}",
				"New context (this should be ignored)"),
			'i18nTestModule.INJECTIONS5' => array("_DOES_NOT_EXIST",
				"Hello {name} {greeting}. But it is late, {goodbye}"),
			'i18nTestModule.INJECTIONS6' => array("Hello {name} {greeting}. But it is late, {goodbye}"),
			'i18nTestModule.INJECTIONS7' => array("Hello {name} {greeting}. But it is late, {goodbye}",
				"New context (this should be ignored)"),
		));

		ksort($expectedArray);

		$this->assertEquals($collectedTranslatables, $expectedArray);
	}

	/**
	 * Input for langArrayCodeForEntitySpec() should be suitable for insertion
	 * into single-quoted strings, so needs to be escaped already.
	 */
	public function testPhpWriterLangArrayCodeForEntity() {
		$c = new i18nTextCollector_Writer_Php();
		
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.SIMPLE', array('Simple Value'), 'en_US'),
			"\$lang['en_US']['Test']['SIMPLE'] = 'Simple Value';" . PHP_EOL
		);
		
		$this->assertEquals(
			// single quotes should be properly escaped by the parser already
			$c->langArrayCodeForEntitySpec('Test.ESCAPEDSINGLEQUOTES',
				array("Value with 'Escaped Single Quotes'"), 'en_US'),
			"\$lang['en_US']['Test']['ESCAPEDSINGLEQUOTES'] = 'Value with \'Escaped Single Quotes\'';" . PHP_EOL
		);
		
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.DOUBLEQUOTES', array('Value with "Double Quotes"'), 'en_US'),
			"\$lang['en_US']['Test']['DOUBLEQUOTES'] = 'Value with \"Double Quotes\"';" . PHP_EOL
		);
		
		$php = <<<PHP
\$lang['en_US']['Test']['PRIOANDCOMMENT'] = array (
  0 => 'Value with \'Single Quotes\'',
  1 => 'Comment with \'Single Quotes\'',
);

PHP;
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.PRIOANDCOMMENT',
				array("Value with 'Single Quotes'","Comment with 'Single Quotes'"), 'en_US'),
			$php
		);
		
		$php = <<<PHP
\$lang['en_US']['Test']['PRIOANDCOMMENT'] = array (
  0 => 'Value with "Double Quotes"',
  1 => 'Comment with "Double Quotes"',
);

PHP;
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.PRIOANDCOMMENT',
				array('Value with "Double Quotes"','Comment with "Double Quotes"'), 'en_US'),
			$php
		);
	}

	/**
	 * @todo Should be in a separate test suite, but don't want to duplicate setup logic
	 */
	public function testYamlWriter() {
		$writer = new i18nTextCollector_Writer_RailsYaml();
		$entities = array(
			'Level1.Level2.EntityName' => array('Text', 'Context'),
			'Level1.OtherEntityName' => array('Other Text', 'Other Context'),
			'Level1.BoolTest' => array('True'),
			'Level1.FlagTest' => array('No'),
			'Level1.TextTest' => array('Maybe')
		);
		$yaml = <<<YAML
de:
  Level1:
    Level2:
      EntityName: Text
    OtherEntityName: 'Other Text'
    BoolTest: 'True'
    FlagTest: 'No'
    TextTest: Maybe

YAML;
		$this->assertEquals($yaml, Convert::nl2os($writer->getYaml($entities, 'de')));
	}
	
	public function testCollectFromIncludedTemplates() {
		$c = new i18nTextCollector();
		
		$templateFilePath = $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss';
		$html = file_get_contents($templateFilePath);
		$matches = $c->collectFromTemplate($html, 'mymodule', 'RandomNamespace');
		
		/*
		$this->assertArrayHasKey('i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModule.ss.LAYOUTTEMPLATENONAMESPACE'],
			array('Layout Template no namespace')
		);
		*/
		$this->assertArrayHasKey('RandomNamespace.SPRINTFNONAMESPACE', $matches);
		$this->assertEquals(
			$matches['RandomNamespace.SPRINTFNONAMESPACE'],
			array('My replacement no namespace: %s')
		);
		$this->assertArrayHasKey('i18nTestModule.LAYOUTTEMPLATE', $matches);
		$this->assertEquals(
			$matches['i18nTestModule.LAYOUTTEMPLATE'],
			array('Layout Template')
		);
		$this->assertArrayHasKey('i18nTestModule.SPRINTFNAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModule.SPRINTFNAMESPACE'],
			array('My replacement: %s')
		);
		$this->assertArrayHasKey('i18nTestModule.WITHNAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModule.WITHNAMESPACE'],
			array('Include Entity with Namespace')
		);
		$this->assertArrayHasKey('i18nTestModuleInclude.ss.NONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModuleInclude.ss.NONAMESPACE'],
			array('Include Entity without Namespace')
		);
		$this->assertArrayHasKey('i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModuleInclude.ss.SPRINTFINCLUDENAMESPACE'],
			array('My include replacement: %s')
		);
		$this->assertArrayHasKey('i18nTestModuleInclude.ss.SPRINTFINCLUDENONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestModuleInclude.ss.SPRINTFINCLUDENONAMESPACE'],
			array('My include replacement no namespace: %s')
		);
	}
	
	public function testCollectFromThemesTemplates() {
		$c = new i18nTextCollector();
		
		$theme = Config::inst()->get('SSViewer', 'theme');
		Config::inst()->update('SSViewer', 'theme', 'testtheme1');
		
		$templateFilePath = $this->alternateBasePath . '/themes/testtheme1/templates/Layout/i18nTestTheme1.ss';
		$html = file_get_contents($templateFilePath);
		$matches = $c->collectFromTemplate($html, 'themes/testtheme1', 'i18nTestTheme1.ss');
		// all entities from i18nTestTheme1.ss
		$this->assertEquals(
			$matches['i18nTestTheme1.LAYOUTTEMPLATE'],
			array('Theme1 Layout Template')
		);
		
		$this->assertArrayHasKey('i18nTestTheme1.ss.LAYOUTTEMPLATENONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestTheme1.ss.LAYOUTTEMPLATENONAMESPACE'],
			array('Theme1 Layout Template no namespace')
		);
		
		$this->assertEquals(
			$matches['i18nTestTheme1.SPRINTFNAMESPACE'],
			array('Theme1 My replacement: %s')
		);
		
		$this->assertArrayHasKey('i18nTestTheme1.ss.SPRINTFNONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestTheme1.ss.SPRINTFNONAMESPACE'],
			array('Theme1 My replacement no namespace: %s')
		);

		// all entities from i18nTestTheme1Include.ss	
		$this->assertEquals(
			$matches['i18nTestTheme1Include.WITHNAMESPACE'],
			array('Theme1 Include Entity with Namespace')
		);
		
		$this->assertArrayHasKey('i18nTestTheme1Include.ss.NONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestTheme1Include.ss.NONAMESPACE'],
			array('Theme1 Include Entity without Namespace')
		);
		
		
		$this->assertEquals(
			$matches['i18nTestTheme1Include.SPRINTFINCLUDENAMESPACE'],
			array('Theme1 My include replacement: %s')
		);
		
		$this->assertArrayHasKey('i18nTestTheme1Include.ss.SPRINTFINCLUDENONAMESPACE', $matches);
		$this->assertEquals(
			$matches['i18nTestTheme1Include.ss.SPRINTFINCLUDENONAMESPACE'],
			array('Theme1 My include replacement no namespace: %s')
		);
		
		Config::inst()->update('SSViewer', 'theme', $theme);
	}

	public function testCollectMergesWithExisting() {
		$defaultlocal = i18n::default_locale();
		$local = i18n::get_locale();
		i18n::set_locale('en_US'); 
		i18n::set_default_locale('en_US');

		$c = new i18nTextCollector();
		$c->setWriter(new i18nTextCollector_Writer_Php());
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
	}
	
	public function testCollectFromFilesystemAndWriteMasterTables() {
		$defaultlocal = i18n::default_locale();
		$local = i18n::get_locale();
		i18n::set_locale('en_US');  //set the locale to the US locale expected in the asserts
		i18n::set_default_locale('en_US');

		$c = new i18nTextCollector();
		$c->setWriter(new i18nTextCollector_Writer_Php());
		$c->basePath = $this->alternateBasePath;
		$c->baseSavePath = $this->alternateBaseSavePath;
		
		$c->run();
		
		// i18ntestmodule
		$moduleLangFile = "{$this->alternateBaseSavePath}/i18ntestmodule/lang/" . $c->getDefaultLocale() . '.php';
		$this->assertTrue(
			file_exists($moduleLangFile),
			'Master language file can be written to modules /lang folder'
		);
		
		$moduleLangFileContent = file_get_contents($moduleLangFile);
		$this->assertContains(
			"\$lang['en']['i18nTestModule']['ADDITION'] = 'Addition';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestModule']['ENTITY'] = array (
  0 => 'Entity with \"Double Quotes\"',
  1 => 'Comment for entity',
);",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestModule']['MAINTEMPLATE'] = 'Main Template';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestModule']['OTHERENTITY'] = 'Other Entity';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestModule']['WITHNAMESPACE'] = 'Include Entity with Namespace';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestModuleInclude.ss']['NONAMESPACE'] = 'Include Entity without Namespace';",
			$moduleLangFileContent
		);
		
		// i18nothermodule
		$otherModuleLangFile = "{$this->alternateBaseSavePath}/i18nothermodule/lang/" . $c->getDefaultLocale() . '.php';
		$this->assertTrue(
			file_exists($otherModuleLangFile),
			'Master language file can be written to modules /lang folder'
		);
		$otherModuleLangFileContent = file_get_contents($otherModuleLangFile);
		$this->assertContains(
			"\$lang['en']['i18nOtherModule']['ENTITY'] = 'Other Module Entity';",
			$otherModuleLangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nOtherModule']['MAINTEMPLATE'] = 'Main Template Other Module';",
			$otherModuleLangFileContent
		);
		
		// testtheme1
		$theme1LangFile = "{$this->alternateBaseSavePath}/themes/testtheme1/lang/" . $c->getDefaultLocale() . '.php';
		$this->assertTrue(
			file_exists($theme1LangFile),
			'Master theme language file can be written to themes/testtheme1 /lang folder'
		);
		$theme1LangFileContent = file_get_contents($theme1LangFile);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1']['MAINTEMPLATE'] = 'Theme1 Main Template';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1']['LAYOUTTEMPLATE'] = 'Theme1 Layout Template';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1']['SPRINTFNAMESPACE'] = 'Theme1 My replacement: %s';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1.ss']['LAYOUTTEMPLATENONAMESPACE'] = 'Theme1 Layout Template no namespace';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1.ss']['SPRINTFNONAMESPACE'] = 'Theme1 My replacement no namespace: %s';",
			$theme1LangFileContent
		);
		
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1Include']['SPRINTFINCLUDENAMESPACE'] = 'Theme1 My include replacement: %s';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1Include']['WITHNAMESPACE'] = 'Theme1 Include Entity with Namespace';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1Include.ss']['NONAMESPACE'] = 'Theme1 Include Entity without Namespace';",
			$theme1LangFileContent
		);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme1Include.ss']['SPRINTFINCLUDENONAMESPACE'] ="
				. " 'Theme1 My include replacement no namespace: %s';",
			$theme1LangFileContent
		);
		
		// testtheme2
		$theme2LangFile = "{$this->alternateBaseSavePath}/themes/testtheme2/lang/" . $c->getDefaultLocale() . '.php';
		$this->assertTrue(
			file_exists($theme2LangFile),
			'Master theme language file can be written to themes/testtheme2 /lang folder'
		);
		$theme2LangFileContent = file_get_contents($theme2LangFile);
		$this->assertContains(
			"\$lang['en']['i18nTestTheme2']['MAINTEMPLATE'] = 'Theme2 Main Template';",
			$theme2LangFileContent
		);

		i18n::set_locale($local);  //set the locale to the US locale expected in the asserts
		i18n::set_default_locale($defaultlocal);
	}
	
	public function testCollectFromEntityProvidersInCustomObject() {
		$c = new i18nTextCollector();

		$filePath = $this->getCurrentAbsolutePath() . '/i18nTextCollectorTestMyObject.php';
		$matches = $c->collectFromEntityProviders($filePath);
		$this->assertEquals(
			array_keys($matches),
			array(
				'i18nTextCollectorTestMyObject.PLURALNAME',
				'i18nTextCollectorTestMyObject.SINGULARNAME',
			)
		);
		$this->assertEquals(
			'My Object',
			$matches['i18nTextCollectorTestMyObject.SINGULARNAME'][0]
		);
	}

}
