<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Tests\FormScaffolderTest\Article;
use SilverStripe\Forms\Tests\FormScaffolderTest\ArticleExtension;
use SilverStripe\Forms\Tests\FormScaffolderTest\Author;
use SilverStripe\Forms\Tests\FormScaffolderTest\Tag;

/**
 * Tests for DataObject FormField scaffolding
 */
class FormScaffolderTest extends SapphireTest
{

    protected static $fixture_file = 'FormScaffolderTest.yml';

    protected static $required_extensions = [
        Article::class => [
            ArticleExtension::class
        ]
    ];

    protected static $extra_dataobjects = array(
        Article::class,
        Tag::class,
        Author::class,
    );


    public function testGetCMSFieldsSingleton()
    {
        $article = new Article;
        $fields = $article->getCMSFields();
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article);

        $this->assertTrue(
            $fields->hasTabSet(),
            'getCMSFields() produces a TabSet'
        );
        $this->assertNotNull(
            $fields->dataFieldByName('Title'),
            'getCMSFields() includes db fields'
        );
        $this->assertNotNull(
            $fields->dataFieldByName('Content'),
            'getCMSFields() includes db fields'
        );
        $this->assertNotNull(
            $fields->dataFieldByName('AuthorID'),
            'getCMSFields() includes has_one fields on singletons'
        );
        $this->assertNull(
            $fields->dataFieldByName('Tags'),
            "getCMSFields() doesn't include many_many fields if no ID is present"
        );
    }

    public function testGetCMSFieldsInstance()
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');

        $fields = $article1->getCMSFields();
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article1);

        $this->assertNotNull(
            $fields->dataFieldByName('AuthorID'),
            'getCMSFields() includes has_one fields on instances'
        );
        $this->assertNotNull(
            $fields->dataFieldByName('Tags'),
            'getCMSFields() includes many_many fields if ID is present on instances'
        );
        $this->assertNotNull(
            $fields->dataFieldByName('SubjectOfArticles'),
            'getCMSFields() includes polymorphic has_many fields if ID is present on instances'
        );
        $this->assertNull(
            $fields->dataFieldByName('Subject'),
            "getCMSFields() doesn't include polymorphic has_one field"
        );
        $this->assertNull(
            $fields->dataFieldByName('SubjectID'),
            "getCMSFields() doesn't include polymorphic has_one id field"
        );
        $this->assertNull(
            $fields->dataFieldByName('SubjectClass'),
            "getCMSFields() doesn't include polymorphic has_one class field"
        );
    }

    public function testUpdateCMSFields()
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');

        $fields = $article1->getCMSFields();
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article1);

        $this->assertNotNull(
            $fields->dataFieldByName('AddedExtensionField'),
            'getCMSFields() includes extended fields'
        );
    }

    public function testRestrictCMSFields()
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');

        $fields = $article1->scaffoldFormFields(
            array(
            'restrictFields' => array('Title')
            )
        );
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article1);

        $this->assertNotNull(
            $fields->dataFieldByName('Title'),
            'scaffoldCMSFields() includes explitly defined "restrictFields"'
        );
        $this->assertNull(
            $fields->dataFieldByName('Content'),
            'getCMSFields() doesnt include fields left out in a "restrictFields" definition'
        );
    }

    public function testFieldClassesOnGetCMSFields()
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');

        $fields = $article1->scaffoldFormFields(
            array(
            'fieldClasses' => array('Title' => 'SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField')
            )
        );
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article1);

        $this->assertNotNull(
            $fields->dataFieldByName('Title')
        );
        $this->assertInstanceOf(
            HTMLEditorField::class,
            $fields->dataFieldByName('Title'),
            'getCMSFields() doesnt include fields left out in a "restrictFields" definition'
        );
    }

    public function testGetFormFields()
    {
        $fields = Article::singleton()->getFrontEndFields();
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom(singleton(Article::class));

        $this->assertFalse($fields->hasTabSet(), 'getFrontEndFields() doesnt produce a TabSet by default');
    }
}
