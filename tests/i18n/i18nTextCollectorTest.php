<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class i18nTextCollectorTest extends SapphireTest {
	
	/**
	 * @var string $tmpBasePath Used to write language files.
	 * We don't want to store them inside sapphire (or in any web-accessible place)
	 * in case something goes wrong with the file parsing.
	 */
	protected $alternateBaseSavePath;
	
	/**
	 * @var string $alternateBasePath Fake webroot with a single module
	 * /i18ntestmodule which contains some files with _t() calls.
	 */
	protected $alternateBasePath;
	
	function setUp() {
		parent::setUp();
		
		$this->alternateBasePath = Director::baseFolder() . "/sapphire/tests/i18n/_fakewebroot";
		$this->alternateBaseSavePath = TEMP_FOLDER . '/i18nTextCollectorTest_webroot';
		FileSystem::makeFolder($this->alternateBaseSavePath);
		
		// SSViewer and ManifestBuilder don't support different webroots, hence we set the paths manually
		global $_CLASS_MANIFEST;
		$_CLASS_MANIFEST['i18nTestModule'] = $this->alternateBasePath . '/i18ntestmodule/code/i18nTestModule.php';
		$_CLASS_MANIFEST['i18nTestModule_Addition'] = $this->alternateBasePath . '/i18ntestmodule/code/i18nTestModule.php';
		$_CLASS_MANIFEST['i18nTestModuleDecorator'] = $this->alternateBasePath . '/i18nothermodule/code/i18nTestModuleDecorator.php';
		
		global $_ALL_CLASSES;
		$_ALL_CLASSES['parents']['i18nTestModule'] = array('DataObject'=>'DataObject','Object'=>'Object');
		$_ALL_CLASSES['parents']['i18nTestModule_Addition'] = array('Object'=>'Object');
		$_ALL_CLASSES['parents']['i18nTestModuleDecorator'] = array('DataObjectDecorator'=>'DataObjectDecorator','Object'=>'Object');

		global $_TEMPLATE_MANIFEST;
		$_TEMPLATE_MANIFEST['i18nTestModule.ss'] = array(
			'main' => $this->alternateBasePath . '/i18ntestmodule/templates/i18nTestModule.ss',
			'Layout' => $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss',
		);
		$_TEMPLATE_MANIFEST['i18nTestModuleInclude.ss'] = array(
			'Includes' => $this->alternateBasePath . '/i18ntestmodule/templates/Includes/i18nTestModuleInclude.ss',
		);
		$_TEMPLATE_MANIFEST['i18nTestModule.ss'] = array(
			'main' => $this->alternateBasePath . '/i18ntestmodule/templates/i18nTestModule.ss',
			'Layout' => $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss',
		);
	}
	
	function tearDown() {
		//FileSystem::removeFolder($this->tmpBasePath);
		
		global $_CLASS_MANIFEST;
		unset($_CLASS_MANIFEST['i18nTestModule']);
		unset($_CLASS_MANIFEST['i18nTestModule_Addition']);
		
		global $_TEMPLATE_MANIFEST;
		unset($_TEMPLATE_MANIFEST['i18nTestModule.ss']);
		unset($_TEMPLATE_MANIFEST['i18nTestModuleInclude.ss']);
	}
	
	function testCollectFromTemplateSimple() {
		$c = new i18nTextCollector();

		$html = <<<SS
<% _t('Test.SINGLEQUOTE','Single Quote'); %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.SINGLEQUOTE' => array('Single Quote',null,null)
			)
		);

		$html = <<<SS
<% _t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   ); %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.DOUBLEQUOTE' => array("Double Quote and Spaces", null, null)
			)
		);
		
		$html = <<<SS
<% _t("Test.NOSEMICOLON","No Semicolon") %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.NOSEMICOLON' => array("No Semicolon", null, null)
			)
		);
	}

	function testCollectFromTemplateAdvanced() {
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
				'Test.NEWLINES' => array("New Lines", null, null)
			)
		);

		$html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	' Prio and Value with "Double Quotes"',
	PR_MEDIUM,
	'Comment with "Double Quotes"'
) %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.PRIOANDCOMMENT' => array(' Prio and Value with "Double Quotes"','PR_MEDIUM','Comment with "Double Quotes"')
			)
		);

		$html = <<<SS
<% _t(
	'Test.PRIOANDCOMMENT',
	" Prio and Value with 'Single Quotes'",
	PR_MEDIUM,
	"Comment with 'Single Quotes'"
) %>
SS;
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'Test'),
			array(
				'Test.PRIOANDCOMMENT' => array(" Prio and Value with \'Single Quotes\'",'PR_MEDIUM',"Comment with 'Single Quotes'")
			)
		);
	}


	function testCollectFromCodeSimple() {
		$c = new i18nTextCollector();
			
		$php = <<<PHP
_t('Test.SINGLEQUOTE','Single Quote');
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.SINGLEQUOTE' => array('Single Quote',null,null)
			)
		);
		
		$php = <<<PHP
_t(  "Test.DOUBLEQUOTE", "Double Quote and Spaces"   );
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.DOUBLEQUOTE' => array("Double Quote and Spaces", null, null)
			)
		);
	}
	
	function testCollectFromCodeAdvanced() {
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
				'Test.NEWLINES' => array("New Lines", null, null)
			)
		);
		
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	' Value with "Double Quotes"',
	PR_MEDIUM,
	'Comment with "Double Quotes"'
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array(' Value with "Double Quotes"','PR_MEDIUM','Comment with "Double Quotes"')
			)
		);
		
		$php = <<<PHP
_t(
	'Test.PRIOANDCOMMENT',
	" Value with 'Single Quotes'",
	PR_MEDIUM,
	"Comment with 'Single Quotes'"
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.PRIOANDCOMMENT' => array(" Value with \'Single Quotes\'",'PR_MEDIUM',"Comment with 'Single Quotes'")
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
				'Test.PRIOANDCOMMENT' => array("Value with \'Escaped Single Quotes\'",null,null)
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
				'Test.PRIOANDCOMMENT' => array("Doublequoted Value with \'Unescaped Single Quotes\'",null,null)
			)
		);
	}
	
	
	function testNewlinesInEntityValues() {
		$c = new i18nTextCollector();

		$php = <<<PHP
_t(
'Test.NEWLINESINGLEQUOTE',
'Line 1
Line 2'
);
PHP;
		$this->assertEquals(
			$c->collectFromCode($php, 'mymodule'),
			array(
				'Test.NEWLINESINGLEQUOTE' => array("Line 1\nLine 2",null,null)
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
				'Test.NEWLINEDOUBLEQUOTE' => array("Line 1\nLine 2",null,null)
			)
		);
	}
	
	/**
	 * Input for langArrayCodeForEntitySpec() should be suitable for insertion
	 * into single-quoted strings, so needs to be escaped already.
	 */
	function testLangArrayCodeForEntity() {
		$c = new i18nTextCollector();
		$locale = $c->getDefaultLocale();
		
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.SIMPLE', array('Simple Value')),
			"\$lang['{$locale}']['Test']['SIMPLE'] = 'Simple Value';\n"
		);
		
		$this->assertEquals(
			// single quotes should be properly escaped by the parser already
			$c->langArrayCodeForEntitySpec('Test.ESCAPEDSINGLEQUOTES', array("Value with \'Escaped Single Quotes\'")),
			"\$lang['{$locale}']['Test']['ESCAPEDSINGLEQUOTES'] = 'Value with \'Escaped Single Quotes\'';\n"
		);
		
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.DOUBLEQUOTES', array('Value with "Double Quotes"')),
			"\$lang['{$locale}']['Test']['DOUBLEQUOTES'] = 'Value with \"Double Quotes\"';\n"
		);
		
		$php = <<<PHP
\$lang['$locale']['Test']['PRIOANDCOMMENT'] = array(
	'Value with \'Single Quotes\'',
	PR_MEDIUM,
	'Comment with \'Single Quotes\''
);

PHP;
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.PRIOANDCOMMENT', array("Value with \'Single Quotes\'",'PR_MEDIUM',"Comment with 'Single Quotes'")),
			$php
		);
		
		$php = <<<PHP
\$lang['$locale']['Test']['PRIOANDCOMMENT'] = array(
	'Value with "Double Quotes"',
	PR_MEDIUM,
	'Comment with "Double Quotes"'
);

PHP;
		$this->assertEquals(
			$c->langArrayCodeForEntitySpec('Test.PRIOANDCOMMENT', array('Value with "Double Quotes"','PR_MEDIUM','Comment with "Double Quotes"')),
			$php
		);
	}
	
	function testCollectFromIncludedTemplates() {
		$c = new i18nTextCollector();
		
		$templateFilePath = $this->alternateBasePath . '/i18ntestmodule/templates/Layout/i18nTestModule.ss';
		$html = file_get_contents($templateFilePath);
		$this->assertEquals(
			$c->collectFromTemplate($html, 'mymodule', 'RandomNamespace'),
			array(
				'i18nTestModule.WITHNAMESPACE' => array('Include Entity with Namespace', null, null),
				'i18nTestModuleInclude.ss.NONAMESPACE' => array('Include Entity without Namespace', null, null),
				'i18nTestModule.LAYOUTTEMPLATE' => array('Layout Template', null, null),
			)
		);
	}
	
	function testCollectFromFilesystemAndWriteMasterTables() {
		$c = new i18nTextCollector();
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
			"\$lang['en_US']['i18nTestModule']['ADDITION'] = 'Addition';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModule']['ENTITY'] = array(
	'Entity with \"Double Quotes\"',
	PR_LOW,
	'Comment for entity'
);",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModule']['MAINTEMPLATE'] = 'Main Template';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModule']['OTHERENTITY'] = 'Other Entity';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModule']['WITHNAMESPACE'] = 'Include Entity with Namespace';",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModule']['db_MyField']",
			$moduleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModuleInclude.ss']['NONAMESPACE'] = 'Include Entity without Namespace';",
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
			"\$lang['en_US']['i18nOtherModule']['ENTITY'] = 'Other Module Entity';",
			$otherModuleLangFileContent
		);
		$this->assertContains(
			"\$lang['en_US']['i18nOtherModule']['MAINTEMPLATE'] = 'Main Template Other Module';",
			$otherModuleLangFileContent
		);
	}
	
	function testCollectFromEntityProvidersInCustomObject() {
		$c = new i18nTextCollector();
		
		$filePath = Director::baseFolder() . '/sapphire/tests/i18n/i18nTextCollectorTestMyObject.php';
		$matches = $c->collectFromEntityProviders($filePath);
		$this->assertEquals(
			array_keys($matches),
			array(
				'i18nTextCollectorTestMyObject.PLURALNAME',
				'i18nTextCollectorTestMyObject.SINGULARNAME',
				'i18nTextCollectorTestMyObject.db_FirstProperty',
				'i18nTextCollectorTestMyObject.db_SecondProperty',
				'i18nTextCollectorTestMyObject.has_many_Relation',
			)
		);
		$this->assertEquals(
			'FirstProperty',
			$matches['i18nTextCollectorTestMyObject.db_FirstProperty'][0]
		);
		$this->assertEquals(
			'My Object',
			$matches['i18nTextCollectorTestMyObject.SINGULARNAME'][0]
		);
	}
	
	function testCollectFromEntityProvidersInCustomSubClass() {
		$c = new i18nTextCollector();
		
		$filePath = Director::baseFolder() . '/sapphire/tests/i18n/i18nTextCollectorTestMySubObject.php';
		$matches = $c->collectFromEntityProviders($filePath);
		$this->assertEquals(
			array_keys($matches),
			array(
				'i18nTextCollectorTestMySubObject.PLURALNAME',
				'i18nTextCollectorTestMySubObject.SINGULARNAME',
				'i18nTextCollectorTestMySubObject.db_SubProperty',
				'i18nTextCollectorTestMySubObject.has_many_SubRelation',
			)
		);
		$this->assertEquals(
			'SubProperty',
			$matches['i18nTextCollectorTestMySubObject.db_SubProperty'][0]
		);
		$this->assertEquals(
			'My Sub Object',
			$matches['i18nTextCollectorTestMySubObject.SINGULARNAME'][0]
		);
	}
	
	function testCollectDecoratedFields() {
		$c = new i18nTextCollector();
		$c->basePath = $this->alternateBasePath;
		$c->baseSavePath = $this->alternateBaseSavePath;
		$c->run();
		
		$moduleLangFile = "{$this->alternateBaseSavePath}/i18ntestmodule/lang/" . $c->getDefaultLocale() . '.php';
		$moduleLangFileContent = file_get_contents($moduleLangFile);
		$this->assertNotContains(
			"\$lang['en_US']['i18nTestModuleDecorator']['db_MyExtraField'] = 'MyExtraField';",
			$moduleLangFileContent,
			'Decorated fields are not stored in the module of the decorated file if the decorator is located in another module'
		);
		$this->assertNotContains(
			"\$lang['en_US']['i18nTestModuleDecorator']['has_one_Page'] = 'Page';",
			$moduleLangFileContent,
			'Decorated fields are not stored in the module of the decorated file if the decorator is located in another module'
		);
		
		$otherModuleLangFile = "{$this->alternateBaseSavePath}/i18nothermodule/lang/" . $c->getDefaultLocale() . '.php';
		$otherModuleLangFileContent = file_get_contents($otherModuleLangFile);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModuleDecorator']['db_MyExtraField'] = 'MyExtraField';",
			$otherModuleLangFileContent,
			'Decorated fields are stored in the module in which the decorator is placed'
		);
		$this->assertContains(
			"\$lang['en_US']['i18nTestModuleDecorator']['has_one_Page'] = 'Page';",
			$otherModuleLangFileContent,
			'Decorated fields are stored in the module in which the decorator is placed'
		);
	}

}
?>