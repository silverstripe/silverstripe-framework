<?php

/**
 * Tests for DataObject FormField scaffolding
 * 
 * @package framework
 * @subpackage tests
 *
 */
class FormScaffolderTest extends SapphireTest {
	
	static $fixture_file = 'FormScaffolderTest.yml';

	protected $extraDataObjects = array(
		'FormScaffolderTest_Article',
		'FormScaffolderTest_Tag',
		'FormScaffolderTest_Author',
	);
	
	
	function testGetCMSFieldsSingleton() {
		$article = new FormScaffolderTest_Article;
		$fields = $article->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article);

		$this->assertTrue($fields->hasTabSet(), 'getCMSFields() produces a TabSet');
		$this->assertNotNull($fields->dataFieldByName('Title'), 'getCMSFields() includes db fields');
		$this->assertNotNull($fields->dataFieldByName('Content'), 'getCMSFields() includes db fields');
		$this->assertNotNull($fields->dataFieldByName('AuthorID'), 'getCMSFields() includes has_one fields on singletons');
		$this->assertNull($fields->dataFieldByName('Tags'), 'getCMSFields() doesnt include many_many fields if no ID is present');
	}
	
	function testGetCMSFieldsInstance() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull($fields->dataFieldByName('AuthorID'), 'getCMSFields() includes has_one fields on instances');
		$this->assertNotNull($fields->dataFieldByName('Tags'), 'getCMSFields() includes many_many fields if ID is present on instances');
	}
	
	function testUpdateCMSFields() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');
		
		$fields = $article1->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);
		
		$this->assertNotNull(
			$fields->dataFieldByName('AddedExtensionField'),
			'getCMSFields() includes extended fields'
		);
	}
	
	function testRestrictCMSFields() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->scaffoldFormFields(array(
			'restrictFields' => array('Title')
		));
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull($fields->dataFieldByName('Title'), 'scaffoldCMSFields() includes explitly defined "restrictFields"');
		$this->assertNull($fields->dataFieldByName('Content'), 'getCMSFields() doesnt include fields left out in a "restrictFields" definition');
	}
	
	function testFieldClassesOnGetCMSFields() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->scaffoldFormFields(array(
			'fieldClasses' => array('Title' => 'HtmlEditorField')
		));
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull(
			$fields->dataFieldByName('Title')
		);
		$this->assertEquals(
			get_class($fields->dataFieldByName('Title')),
			'HtmlEditorField',
			'getCMSFields() doesnt include fields left out in a "restrictFields" definition'
		);
	}
	
	function testGetFormFields() {
		$fields = singleton('FormScaffolderTest_Article')->getFrontEndFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom(singleton('FormScaffolderTest_Article'));

		$this->assertFalse($fields->hasTabSet(), 'getFrontEndFields() doesnt produce a TabSet by default');
	}
}

class FormScaffolderTest_Article extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Varchar', 
		'Content' => 'HTMLText'
	);
	static $has_one = array(
		'Author' => 'FormScaffolderTest_Author'
	);
	static $many_many = array(
		'Tags' => 'FormScaffolderTest_Tag', 
	);
}

class FormScaffolderTest_Author extends Member implements TestOnly {
	static $has_one = array(
		'ProfileImage' => 'Image'
	);
	static $has_many = array(
		'Articles' => 'FormScaffolderTest_Article'
	);
}
class FormScaffolderTest_Tag extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Varchar', 
	);
	static $belongs_many_many = array(
		'Articles' => 'FormScaffolderTest_Article'
	);
}
class FormScaffolderTest_ArticleExtension extends DataExtension implements TestOnly {
	static $db = array(
		'ExtendedField' => 'Varchar'
	);

	function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab('Root.Main',
			new TextField('AddedExtensionField')
		);
	}

}

DataObject::add_extension('FormScaffolderTest_Article', 'FormScaffolderTest_ArticleExtension');
