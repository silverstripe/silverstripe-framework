<?php

/**
 * Tests for DataObject FormField scaffolding
 *
 * @package framework
 * @subpackage tests
 *
 */
class FormScaffolderTest extends SapphireTest {

	protected static $fixture_file = 'FormScaffolderTest.yml';

	protected $extraDataObjects = array(
		'FormScaffolderTest_Article',
		'FormScaffolderTest_Tag',
		'FormScaffolderTest_Author',
	);


	public function testGetCMSFieldsSingleton() {
		$article = new FormScaffolderTest_Article;
		$fields = $article->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article);

		$this->assertTrue($fields->hasTabSet(),
			'getCMSFields() produces a TabSet');
		$this->assertNotNull($fields->dataFieldByName('Title'),
			'getCMSFields() includes db fields');
		$this->assertNotNull($fields->dataFieldByName('Content'),
			'getCMSFields() includes db fields');
		$this->assertNotNull($fields->dataFieldByName('AuthorID'),
			'getCMSFields() includes has_one fields on singletons');
		$this->assertNull($fields->dataFieldByName('Tags'),
			"getCMSFields() doesn't include many_many fields if no ID is present");
	}

	public function testGetCMSFieldsInstance() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull($fields->dataFieldByName('AuthorID'),
			'getCMSFields() includes has_one fields on instances');
		$this->assertNotNull($fields->dataFieldByName('Tags'),
			'getCMSFields() includes many_many fields if ID is present on instances');
		$this->assertNotNull($fields->dataFieldByName('SubjectOfArticles'),
			'getCMSFields() includes polymorphic has_many fields if ID is present on instances');
		$this->assertNull($fields->dataFieldByName('Subject'),
			"getCMSFields() doesn't include polymorphic has_one field");
		$this->assertNull($fields->dataFieldByName('SubjectID'),
			"getCMSFields() doesn't include polymorphic has_one id field");
		$this->assertNull($fields->dataFieldByName('SubjectClass'),
			"getCMSFields() doesn't include polymorphic has_one class field");
	}

	public function testUpdateCMSFields() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->getCMSFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull(
			$fields->dataFieldByName('AddedExtensionField'),
			'getCMSFields() includes extended fields'
		);
	}

	public function testRestrictCMSFields() {
		$article1 = $this->objFromFixture('FormScaffolderTest_Article', 'article1');

		$fields = $article1->scaffoldFormFields(array(
			'restrictFields' => array('Title')
		));
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom($article1);

		$this->assertNotNull($fields->dataFieldByName('Title'),
			'scaffoldCMSFields() includes explitly defined "restrictFields"');
		$this->assertNull($fields->dataFieldByName('Content'),
			'getCMSFields() doesnt include fields left out in a "restrictFields" definition');
	}

	public function testFieldClassesOnGetCMSFields() {
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

	public function testGetFormFields() {
		$fields = singleton('FormScaffolderTest_Article')->getFrontEndFields();
		$form = new Form(new Controller(), 'TestForm', $fields, new FieldList());
		$form->loadDataFrom(singleton('FormScaffolderTest_Article'));

		$this->assertFalse($fields->hasTabSet(), 'getFrontEndFields() doesnt produce a TabSet by default');
	}
}

class FormScaffolderTest_Article extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar',
		'Content' => 'HTMLText'
	);
	private static $has_one = array(
		'Author' => 'FormScaffolderTest_Author',
		'Subject' => 'DataObject'
	);
	private static $many_many = array(
		'Tags' => 'FormScaffolderTest_Tag',
	);
	private static $has_many = array(
		'SubjectOfArticles' => 'FormScaffolderTest_Article.Subject'
	);
}

class FormScaffolderTest_Author extends Member implements TestOnly {
	private static $has_one = array(
		'ProfileImage' => 'Image'
	);
	private static $has_many = array(
		'Articles' => 'FormScaffolderTest_Article.Author',
		'SubjectOfArticles' => 'FormScaffolderTest_Article.Subject'
	);
}
class FormScaffolderTest_Tag extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar',
	);
	private static $belongs_many_many = array(
		'Articles' => 'FormScaffolderTest_Article'
	);
	private static $has_many = array(
		'SubjectOfArticles' => 'FormScaffolderTest_Article.Subject'
	);
}
class FormScaffolderTest_ArticleExtension extends DataExtension implements TestOnly {
	private static $db = array(
		'ExtendedField' => 'Varchar'
	);

	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab('Root.Main',
			new TextField('AddedExtensionField')
		);
	}

}

FormScaffolderTest_Article::add_extension('FormScaffolderTest_ArticleExtension');
