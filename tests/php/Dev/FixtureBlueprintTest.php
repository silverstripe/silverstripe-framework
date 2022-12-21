<?php

namespace SilverStripe\Dev\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\FixtureBlueprint;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\FixtureBlueprintTest\Article;
use SilverStripe\Dev\Tests\FixtureBlueprintTest\TestPage;
use SilverStripe\Dev\Tests\FixtureBlueprintTest\TestSiteTree;
use SilverStripe\Dev\Tests\FixtureFactoryTest\DataObjectRelation;
use SilverStripe\Dev\Tests\FixtureFactoryTest\TestDataObject;

class FixtureBlueprintTest extends SapphireTest
{

    protected $usesDatabase = true;

    private int $_called = 0;

    protected static $extra_dataobjects = [
        TestDataObject::class,
        DataObjectRelation::class,
        TestSiteTree::class,
        TestPage::class,
    ];

    public function testCreateWithRelationshipExtraFields()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);

        $relation1 = new DataObjectRelation();
        $relation1->write();
        $relation2 = new DataObjectRelation();
        $relation2->write();

        // in YAML these look like
        // RelationName:
        //   - =>Relational.obj:
        //     ExtraFieldName: test
        //   - =>..
        $obj = $blueprint->createObject(
            'one',
            [
                'ManyManyRelation' =>
                    [
                        [
                            "=>" . DataObjectRelation::class . ".relation1" => [],
                            "Label" => 'This is a label for relation 1'
                        ],
                        [
                            "=>" . DataObjectRelation::class . ".relation2" => [],
                            "Label" => 'This is a label for relation 2'
                        ]
                    ]
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => $relation1->ID,
                    'relation2' => $relation2->ID
                ]
            ]
        );

        $this->assertEquals(2, $obj->ManyManyRelation()->Count());
        $this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation1->ID));
        $this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation2->ID));

        $this->assertEquals(
            ['Label' => 'This is a label for relation 1'],
            $obj->ManyManyRelation()->getExtraData('ManyManyRelation', $relation1->ID)
        );

        $this->assertEquals(
            ['Label' => 'This is a label for relation 2'],
            $obj->ManyManyRelation()->getExtraData('ManyManyRelation', $relation2->ID)
        );
    }


    public function testCreateWithoutData()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $obj = $blueprint->createObject('one');
        $this->assertNotNull($obj);
        $this->assertGreaterThan(0, $obj->ID);
        $this->assertEquals('', $obj->Name);
    }

    public function testCreateWithData()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $obj = $blueprint->createObject('one', ['Name' => 'My Name']);
        $this->assertNotNull($obj);
        $this->assertGreaterThan(0, $obj->ID);
        $this->assertEquals('My Name', $obj->Name);
    }


    public function testCreateWithRelationship()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);

        $relation1 = new DataObjectRelation();
        $relation1->write();
        $relation2 = new DataObjectRelation();
        $relation2->write();

        $obj = $blueprint->createObject(
            'one',
            [
                'ManyManyRelation' =>
                    '=>' . DataObjectRelation::class . '.relation1,' . '=>' . DataObjectRelation::class . '.relation2'
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => $relation1->ID,
                    'relation2' => $relation2->ID
                ]
            ]
        );

        $this->assertEquals(2, $obj->ManyManyRelation()->Count());
        $this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation1->ID));
        $this->assertNotNull($obj->ManyManyRelation()->find('ID', $relation2->ID));

        $obj2 = $blueprint->createObject(
            'two',
            [
                // Note; using array format here, not comma separated
                'HasManyRelation' => [
                    '=>' . DataObjectRelation::class . '.relation1',
                    '=>' . DataObjectRelation::class . '.relation2'
                ]
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => $relation1->ID,
                    'relation2' => $relation2->ID
                ]
            ]
        );
        $this->assertEquals(2, $obj2->HasManyRelation()->Count());
        $this->assertNotNull($obj2->HasManyRelation()->find('ID', $relation1->ID));
        $this->assertNotNull($obj2->HasManyRelation()->find('ID', $relation2->ID));
    }

    public function testCreateWithInvalidRelationName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No fixture definitions found');
        $blueprint = new FixtureBlueprint(TestDataObject::class);

        $obj = $blueprint->createObject(
            'one',
            [
                'ManyManyRelation' => '=>UnknownClass.relation1'
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => 99
                ]
            ]
        );
    }

    public function testCreateWithInvalidRelationIdentifier()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No fixture definitions found');
        $blueprint = new FixtureBlueprint(TestDataObject::class);

        $obj = $blueprint->createObject(
            'one',
            [
                'ManyManyRelation' => '=>' . DataObjectRelation::class . '.unknown_identifier'
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => 99
                ]
            ]
        );
    }

    public function testCreateWithInvalidRelationFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid format');
        $factory = new FixtureFactory();
        $blueprint = new FixtureBlueprint(TestDataObject::class);

        $relation1 = new DataObjectRelation();
        $relation1->write();

        $obj = $blueprint->createObject(
            'one',
            [
                'ManyManyRelation' => DataObjectRelation::class . '.relation1'
            ],
            [
                DataObjectRelation::class => [
                    'relation1' => $relation1->ID
                ]
            ]
        );
    }

    public function testCreateWithId()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $obj = $blueprint->createObject('ninetynine', ['ID' => 99]);
        $this->assertNotNull($obj);
        $this->assertEquals(99, $obj->ID);
    }

    public function testCreateWithLastEdited()
    {
        $extpectedDate = '2010-12-14 16:18:20';
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $obj = $blueprint->createObject('lastedited', ['LastEdited' => $extpectedDate]);
        $this->assertNotNull($obj);
        $this->assertEquals($extpectedDate, $obj->LastEdited);

        $obj = TestDataObject::get()->byID($obj->ID);
        $this->assertEquals($extpectedDate, $obj->LastEdited);
    }

    public function testCreateWithClassAncestry()
    {
        $data = [
            'Title' => 'My Title',
            'Created' => '2010-12-14 16:18:20',
            'LastEdited' => '2010-12-14 16:18:20',
            'PublishDate' => '2015-12-09 06:03:00'
        ];
        $blueprint = new FixtureBlueprint(Article::class);
        $obj = $blueprint->createObject('home', $data);
        $this->assertNotNull($obj);
        $this->assertEquals($data['Title'], $obj->Title);
        $this->assertEquals($data['Created'], $obj->Created);
        $this->assertEquals($data['LastEdited'], $obj->LastEdited);
        $this->assertEquals($data['PublishDate'], $obj->PublishDate);

        $obj = Article::get()->byID($obj->ID);
        $this->assertNotNull($obj);
        $this->assertEquals($data['Title'], $obj->Title);
        $this->assertEquals($data['Created'], $obj->Created);
        $this->assertEquals($data['LastEdited'], $obj->LastEdited);
        $this->assertEquals($data['PublishDate'], $obj->PublishDate);
    }

    public function testCallbackOnBeforeCreate()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $this->_called = 0;
        $self = $this;
        $cb = function ($identifier, $data, $fixtures) use ($self) {
            $self->_called = $self->_called + 1;
        };
        $blueprint->addCallback('beforeCreate', $cb);
        $this->assertEquals(0, $this->_called);
        $obj1 = $blueprint->createObject('one');
        $this->assertEquals(1, $this->_called);
        $obj2 = $blueprint->createObject('two');
        $this->assertEquals(2, $this->_called);

        $this->_called = 0;
    }

    public function testCallbackOnAfterCreate()
    {
        $blueprint = new FixtureBlueprint(TestDataObject::class);
        $this->_called = 0;
        $self = $this;
        $cb = function ($obj, $identifier, $data, $fixtures) use ($self) {
            $self->_called = $self->_called + 1;
        };
        $blueprint->addCallback('afterCreate', $cb);
        $this->assertEquals(0, $this->_called);
        $obj1 = $blueprint->createObject('one');
        $this->assertEquals(1, $this->_called);
        $obj2 = $blueprint->createObject('two');
        $this->assertEquals(2, $this->_called);

        $this->_called = 0;
    }

    public function testDefineWithDefaultCustomSetters()
    {
        $blueprint = new FixtureBlueprint(
            TestDataObject::class,
            null,
            [
            'Name' => function ($obj, $data, $fixtures) {
                return 'Default Name';
            }
            ]
        );

        $obj1 = $blueprint->createObject('one');
        $this->assertEquals('Default Name', $obj1->Name);

        $obj2 = $blueprint->createObject('one', ['Name' => 'Override Name']);
        $this->assertEquals('Override Name', $obj2->Name);
    }
}
