<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\Tests\FormScaffolderTest\Article;
use SilverStripe\Forms\Tests\FormScaffolderTest\ArticleExtension;
use SilverStripe\Forms\Tests\FormScaffolderTest\Author;
use SilverStripe\Forms\Tests\FormScaffolderTest\Child;
use SilverStripe\Forms\Tests\FormScaffolderTest\ParentModel;
use SilverStripe\Forms\Tests\FormScaffolderTest\ParentChildJoin;
use SilverStripe\Forms\Tests\FormScaffolderTest\Tag;
use SilverStripe\Forms\TimeField;

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

    protected static $extra_dataobjects = [
        Article::class,
        Tag::class,
        Author::class,
        ParentModel::class,
        Child::class,
        ParentChildJoin::class,
    ];

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
            [
            'restrictFields' => ['Title']
            ]
        );
        $form = new Form(null, 'TestForm', $fields, new FieldList());
        $form->loadDataFrom($article1);

        $this->assertNotNull(
            $fields->dataFieldByName('Title'),
            'scaffoldCMSFields() includes explicitly defined "restrictFields"'
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
            [
            'fieldClasses' => ['Title' => 'SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField']
            ]
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

    public function provideScaffoldRelationFormFields()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider provideScaffoldRelationFormFields
     */
    public function testScaffoldRelationFormFields(bool $includeInOwnTab)
    {
        $parent = $this->objFromFixture(ParentModel::class, 'parent1');
        Child::$includeInOwnTab = $includeInOwnTab;
        $fields = $parent->scaffoldFormFields(['includeRelations' => true, 'tabbed' => true]);

        foreach (array_keys(ParentModel::config()->uninherited('has_one')) as $hasOneName) {
            $scaffoldedFormField = $fields->dataFieldByName($hasOneName . 'ID');
            if ($hasOneName === 'ChildPolymorphic') {
                $this->assertNull($scaffoldedFormField, "$hasOneName should be null");
            } else {
                $this->assertInstanceOf(DateField::class, $scaffoldedFormField, "$hasOneName should be a DateField");
            }
        }
        foreach (array_keys(ParentModel::config()->uninherited('has_many')) as $hasManyName) {
            $this->assertInstanceOf(CurrencyField::class, $fields->dataFieldByName($hasManyName), "$hasManyName should be a CurrencyField");
            if ($includeInOwnTab) {
                $this->assertNotNull($fields->findTab("Root.$hasManyName"));
            } else {
                $this->assertNull($fields->findTab("Root.$hasManyName"));
            }
        }
        foreach (array_keys(ParentModel::config()->uninherited('many_many')) as $manyManyName) {
            $this->assertInstanceOf(TimeField::class, $fields->dataFieldByName($manyManyName), "$manyManyName should be a TimeField");
            if ($includeInOwnTab) {
                $this->assertNotNull($fields->findTab("Root.$manyManyName"));
            } else {
                $this->assertNull($fields->findTab("Root.$manyManyName"));
            }
        }
    }
}
