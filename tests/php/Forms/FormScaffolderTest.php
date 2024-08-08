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
        $scenarios = [
            'ignore no relations' => [
                'includeInOwnTab' => true,
                'ignoreRelations' => [],
            ],
            'ignore some relations' => [
                'includeInOwnTab' => true,
                'ignoreRelations' => [
                    'ChildrenHasMany',
                    'ChildrenManyManyThrough',
                ],
            ],
        ];
        foreach ($scenarios as $name => $scenario) {
            $scenario['includeInOwnTab'] = false;
            $scenarios[$name . ' - not in own tab'] = $scenario;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideScaffoldRelationFormFields
     */
    public function testScaffoldRelationFormFields(bool $includeInOwnTab, array $ignoreRelations)
    {
        $parent = $this->objFromFixture(ParentModel::class, 'parent1');
        Child::$includeInOwnTab = $includeInOwnTab;
        $fields = $parent->scaffoldFormFields([
            'includeRelations' => true,
            'tabbed' => true,
            'ignoreRelations' => $ignoreRelations,
        ]);

        // has_one
        foreach (array_keys(ParentModel::config()->uninherited('has_one')) as $hasOneName) {
            $scaffoldedFormField = $fields->dataFieldByName($hasOneName . 'ID');
            if ($hasOneName === 'ChildPolymorphic') {
                $this->assertNull($scaffoldedFormField, "$hasOneName should be null");
            } else {
                $this->assertInstanceOf(DateField::class, $scaffoldedFormField, "$hasOneName should be a DateField");
            }
        }
        // has_many
        foreach (array_keys(ParentModel::config()->uninherited('has_many')) as $hasManyName) {
            if (in_array($hasManyName, $ignoreRelations)) {
                $this->assertNull($fields->dataFieldByName($hasManyName));
            } else {
                $this->assertInstanceOf(CurrencyField::class, $fields->dataFieldByName($hasManyName), "$hasManyName should be a CurrencyField");
                if ($includeInOwnTab) {
                    $this->assertNotNull($fields->findTab("Root.$hasManyName"));
                } else {
                    $this->assertNull($fields->findTab("Root.$hasManyName"));
                }
            }
        }
        // many_many
        foreach (array_keys(ParentModel::config()->uninherited('many_many')) as $manyManyName) {
            if (in_array($hasManyName, $ignoreRelations)) {
                $this->assertNull($fields->dataFieldByName($hasManyName));
            } else {
                $this->assertInstanceOf(TimeField::class, $fields->dataFieldByName($manyManyName), "$manyManyName should be a TimeField");
                if ($includeInOwnTab) {
                    $this->assertNotNull($fields->findTab("Root.$manyManyName"));
                } else {
                    $this->assertNull($fields->findTab("Root.$manyManyName"));
                }
            }
        }
    }

    public function testScaffoldIgnoreFields(): void
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');
        $fields = $article1->scaffoldFormFields([
            'ignoreFields' => [
                'Content',
                'Author',
            ],
        ]);
        $this->assertSame(['ExtendedField', 'Title'], $fields->column('Name'));
    }

    public function testScaffoldRestrictRelations(): void
    {
        $article1 = $this->objFromFixture(Article::class, 'article1');
        $fields = $article1->scaffoldFormFields([
            'includeRelations' => true,
            'restrictRelations' => [
                'Tags',
            ],
            // Ensure no db or has_one fields get scaffolded
            'restrictFields' => [
                'non-existent',
            ],
        ]);
        $this->assertSame(['Tags'], $fields->column('Name'));
    }

    public function provideTabs(): array
    {
        return [
            'only main tab' => [
                'tabs' => true,
                'mainTabOnly' => true,
            ],
            'all tabs, all fields' => [
                'tabs' => true,
                'mainTabOnly' => false,
            ],
            'no tabs, no fields' => [
                'tabs' => false,
                'mainTabOnly' => true,
            ],
            'no tabs, all fields' => [
                'tabs' => false,
                'mainTabOnly' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideTabs
     */
    public function testTabs(bool $tabbed, bool $mainTabOnly): void
    {
        $parent = $this->objFromFixture(ParentModel::class, 'parent1');
        Child::$includeInOwnTab = true;
        $fields = $parent->scaffoldFormFields([
            'tabbed' => $tabbed,
            'mainTabOnly' => $mainTabOnly,
            'includeRelations' => true,
        ]);

        $fieldsToExpect = [
            ['Name' => 'Title'],
            ['Name' => 'ChildID'],
            ['Name' => 'ChildrenHasMany'],
            ['Name' => 'ChildrenManyMany'],
            ['Name' => 'ChildrenManyManyThrough'],
        ];
        $relationTabs = [
            'Root.ChildrenHasMany',
            'Root.ChildrenManyMany',
            'Root.ChildrenManyManyThrough',
        ];

        if ($tabbed) {
            $this->assertNotNull($fields->findTab('Root.Main'));
            if ($mainTabOnly) {
                // Only Root.Main with no fields
                $this->assertListNotContains($fieldsToExpect, $fields->flattenFields());
                foreach ($relationTabs as $tabName) {
                    $this->assertNull($fields->findTab($tabName));
                }
            } else {
                // All fields in all tabs
                $this->assertListContains($fieldsToExpect, $fields->flattenFields());
                foreach ($relationTabs as $tabName) {
                    $this->assertNotNull($fields->findTab($tabName));
                }
            }
        } else {
            if ($mainTabOnly) {
                // Empty list
                $this->assertEmpty($fields);
            } else {
                // All fields, no tabs
                $this->assertNull($fields->findTab('Root.Main'));
                foreach ($relationTabs as $tabName) {
                    $this->assertNull($fields->findTab($tabName));
                }
                $this->assertListContains($fieldsToExpect, $fields->flattenFields());
            }
        }
    }
}
