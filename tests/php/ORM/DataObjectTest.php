<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBPolymorphicForeignKey;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\View\ViewableData;
use stdClass;

class DataObjectTest extends SapphireTest
{

    protected static $fixture_file = 'DataObjectTest.yml';

    /**
     * Standard set of dataobject test classes
     *
     * @var array
     */
    public static $extra_data_objects = array(
        DataObjectTest\Team::class,
        DataObjectTest\Fixture::class,
        DataObjectTest\SubTeam::class,
        DataObjectTest\OtherSubclassWithSameField::class,
        DataObjectTest\FieldlessTable::class,
        DataObjectTest\FieldlessSubTable::class,
        DataObjectTest\ValidatedObject::class,
        DataObjectTest\Player::class,
        DataObjectTest\TeamComment::class,
        DataObjectTest\EquipmentCompany::class,
        DataObjectTest\SubEquipmentCompany::class,
        DataObjectTest\ExtendedTeamComment::class,
        DataObjectTest\Company::class,
        DataObjectTest\Staff::class,
        DataObjectTest\CEO::class,
        DataObjectTest\Fan::class,
        DataObjectTest\Play::class,
        DataObjectTest\Ploy::class,
        DataObjectTest\Bogey::class,
        DataObjectTest\Sortable::class,
        DataObjectTest\Bracket::class,
        DataObjectTest\RelationParent::class,
        DataObjectTest\RelationChildFirst::class,
        DataObjectTest\RelationChildSecond::class,
    );

    public static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }

    public function testDb()
    {
        $schema = DataObject::getSchema();
        $dbFields = $schema->fieldSpecs(DataObjectTest\TeamComment::class);

        // Assert fields are included
        $this->assertArrayHasKey('Name', $dbFields);

        // Assert the base fields are included
        $this->assertArrayHasKey('Created', $dbFields);
        $this->assertArrayHasKey('LastEdited', $dbFields);
        $this->assertArrayHasKey('ClassName', $dbFields);
        $this->assertArrayHasKey('ID', $dbFields);

        // Assert that the correct field type is returned when passing a field
        $this->assertEquals('Varchar', $schema->fieldSpec(DataObjectTest\TeamComment::class, 'Name'));
        $this->assertEquals('Text', $schema->fieldSpec(DataObjectTest\TeamComment::class, 'Comment'));

        // Test with table required
        $this->assertEquals(
            DataObjectTest\TeamComment::class . '.Varchar',
            $schema->fieldSpec(DataObjectTest\TeamComment::class, 'Name', DataObjectSchema::INCLUDE_CLASS)
        );
        $this->assertEquals(
            DataObjectTest\TeamComment::class . '.Text',
            $schema->fieldSpec(DataObjectTest\TeamComment::class, 'Comment', DataObjectSchema::INCLUDE_CLASS)
        );
        $dbFields = $schema->fieldSpecs(DataObjectTest\ExtendedTeamComment::class);

        // fixed fields are still included in extended classes
        $this->assertArrayHasKey('Created', $dbFields);
        $this->assertArrayHasKey('LastEdited', $dbFields);
        $this->assertArrayHasKey('ClassName', $dbFields);
        $this->assertArrayHasKey('ID', $dbFields);

        // Assert overloaded fields have correct data type
        $this->assertEquals('HTMLText', $schema->fieldSpec(DataObjectTest\ExtendedTeamComment::class, 'Comment'));
        $this->assertEquals(
            'HTMLText',
            $dbFields['Comment'],
            'Calls to DataObject::db without a field specified return correct data types'
        );

        // assertEquals doesn't verify the order of array elements, so access keys manually to check order:
        // expected: array('Name' => 'Varchar', 'Comment' => 'HTMLText')
        $this->assertEquals(
            array(
                'Name',
                'Comment'
            ),
            array_slice(array_keys($dbFields), 4, 2),
            'DataObject::db returns fields in correct order'
        );
    }

    public function testConstructAcceptsValues()
    {
        // Values can be an array...
        $player = new DataObjectTest\Player(
            array(
                'FirstName' => 'James',
                'Surname' => 'Smith'
            )
        );

        $this->assertEquals('James', $player->FirstName);
        $this->assertEquals('Smith', $player->Surname);

        // ... or a stdClass inst
        $data = new stdClass();
        $data->FirstName = 'John';
        $data->Surname = 'Doe';
        $player = new DataObjectTest\Player($data);

        $this->assertEquals('John', $player->FirstName);
        $this->assertEquals('Doe', $player->Surname);

        // IDs should be stored as integers, not strings
        $player = new DataObjectTest\Player(array('ID' => '5'));
        $this->assertSame(5, $player->ID);
    }

    public function testValidObjectsForBaseFields()
    {
        $obj = new DataObjectTest\ValidatedObject();

        foreach (array('Created', 'LastEdited', 'ClassName', 'ID') as $field) {
            $helper = $obj->dbObject($field);
            $this->assertTrue(
                ($helper instanceof DBField),
                "for {$field} expected helper to be DBField, but was " . (is_object($helper) ? get_class($helper) : "null")
            );
        }
    }

    public function testDataIntegrityWhenTwoSubclassesHaveSameField()
    {
        // Save data into DataObjectTest_SubTeam.SubclassDatabaseField
        $obj = new DataObjectTest\SubTeam();
        $obj->SubclassDatabaseField = "obj-SubTeam";
        $obj->write();

        // Change the class
        $obj->ClassName = DataObjectTest\OtherSubclassWithSameField::class;
        $obj->write();
        $obj->flushCache();

        // Re-fetch from the database and confirm that the data is sourced from
        // OtherSubclassWithSameField.SubclassDatabaseField
        $obj = DataObject::get_by_id(DataObjectTest\Team::class, $obj->ID);
        $this->assertNull($obj->SubclassDatabaseField);

        // Confirm that save the object in the other direction.
        $obj->SubclassDatabaseField = 'obj-Other';
        $obj->write();

        $obj->ClassName = DataObjectTest\SubTeam::class;
        $obj->write();
        $obj->flushCache();

        // If we restore the class, the old value has been lying dormant and will be available again.
        // NOTE: This behaviour is volatile; we may change this in the future to clear fields that
        // are no longer relevant when changing ClassName
        $obj = DataObject::get_by_id(DataObjectTest\Team::class, $obj->ID);
        $this->assertEquals('obj-SubTeam', $obj->SubclassDatabaseField);
    }

    /**
     * Test deletion of DataObjects
     *   - Deleting using delete() on the DataObject
     *   - Deleting using DataObject::delete_by_id()
     */
    public function testDelete()
    {
        // Test deleting using delete() on the DataObject
        // Get the first page
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $objID = $obj->ID;
        // Check the page exists before deleting
        $this->assertTrue(is_object($obj) && $obj->exists());
        // Delete the page
        $obj->delete();
        // Check that page does not exist after deleting
        $obj = DataObject::get_by_id(DataObjectTest\Player::class, $objID);
        $this->assertTrue(!$obj || !$obj->exists());


        // Test deleting using DataObject::delete_by_id()
        // Get the second page
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain2');
        $objID = $obj->ID;
        // Check the page exists before deleting
        $this->assertTrue(is_object($obj) && $obj->exists());
        // Delete the page
        DataObject::delete_by_id(DataObjectTest\Player::class, $obj->ID);
        // Check that page does not exist after deleting
        $obj = DataObject::get_by_id(DataObjectTest\Player::class, $objID);
        $this->assertTrue(!$obj || !$obj->exists());
    }

    /**
     * Test methods that get DataObjects
     *   - DataObject::get()
     *       - All records of a DataObject
     *       - Filtering
     *       - Sorting
     *       - Joins
     *       - Limit
     *       - Container class
     *   - DataObject::get_by_id()
     *   - DataObject::get_one()
     *        - With and without caching
     *        - With and without ordering
     */
    public function testGet()
    {
        // Test getting all records of a DataObject
        $comments = DataObject::get(DataObjectTest\TeamComment::class);
        $this->assertEquals(3, $comments->count());

        // Test WHERE clause
        $comments = DataObject::get(DataObjectTest\TeamComment::class, "\"Name\"='Bob'");
        $this->assertEquals(1, $comments->count());
        foreach ($comments as $comment) {
            $this->assertEquals('Bob', $comment->Name);
        }

        // Test sorting
        $comments = DataObject::get(DataObjectTest\TeamComment::class, '', "\"Name\" ASC");
        $this->assertEquals(3, $comments->count());
        $this->assertEquals('Bob', $comments->first()->Name);
        $comments = DataObject::get(DataObjectTest\TeamComment::class, '', "\"Name\" DESC");
        $this->assertEquals(3, $comments->count());
        $this->assertEquals('Phil', $comments->first()->Name);

        // Test limit
        $comments = DataObject::get(DataObjectTest\TeamComment::class, '', "\"Name\" ASC", '', '1,2');
        $this->assertEquals(2, $comments->count());
        $this->assertEquals('Joe', $comments->first()->Name);
        $this->assertEquals('Phil', $comments->last()->Name);

        // Test get_by_id()
        $captain1ID = $this->idFromFixture(DataObjectTest\Player::class, 'captain1');
        $captain1 = DataObject::get_by_id(DataObjectTest\Player::class, $captain1ID);
        $this->assertEquals('Captain', $captain1->FirstName);

        // Test get_one() without caching
        $comment1 = DataObject::get_one(
            DataObjectTest\TeamComment::class,
            array(
                '"DataObjectTest_TeamComment"."Name"' => 'Joe'
            ),
            false
        );
        $comment1->Comment = "Something Else";

        $comment2 = DataObject::get_one(
            DataObjectTest\TeamComment::class,
            array(
                '"DataObjectTest_TeamComment"."Name"' => 'Joe'
            ),
            false
        );
        $this->assertNotEquals($comment1->Comment, $comment2->Comment);

        // Test get_one() with caching
        $comment1 = DataObject::get_one(
            DataObjectTest\TeamComment::class,
            array(
                '"DataObjectTest_TeamComment"."Name"' => 'Bob'
            ),
            true
        );
        $comment1->Comment = "Something Else";

        $comment2 = DataObject::get_one(
            DataObjectTest\TeamComment::class,
            array(
                '"DataObjectTest_TeamComment"."Name"' => 'Bob'
            ),
            true
        );
        $this->assertEquals((string)$comment1->Comment, (string)$comment2->Comment);

        // Test get_one() with order by without caching
        $comment = DataObject::get_one(DataObjectTest\TeamComment::class, '', false, "\"Name\" ASC");
        $this->assertEquals('Bob', $comment->Name);

        $comment = DataObject::get_one(DataObjectTest\TeamComment::class, '', false, "\"Name\" DESC");
        $this->assertEquals('Phil', $comment->Name);

        // Test get_one() with order by with caching
        $comment = DataObject::get_one(DataObjectTest\TeamComment::class, '', true, '"Name" ASC');
        $this->assertEquals('Bob', $comment->Name);
        $comment = DataObject::get_one(DataObjectTest\TeamComment::class, '', true, '"Name" DESC');
        $this->assertEquals('Phil', $comment->Name);
    }

    public function testGetCaseInsensitive()
    {
        // Test get_one() with bad case on the classname
        // Note: This will succeed only if the underlying DB server supports case-insensitive
        // table names (e.g. such as MySQL, but not SQLite3)
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            $this->markTestSkipped('MySQL only');
        }

        $subteam1 = DataObject::get_one(
            strtolower(DataObjectTest\SubTeam::class),
            array(
                '"DataObjectTest_Team"."Title"' => 'Subteam 1'
            ),
            true
        );
        $this->assertNotEmpty($subteam1);
        $this->assertEquals($subteam1->Title, "Subteam 1");
    }

    public function testGetSubclassFields()
    {
        /* Test that fields / has_one relations from the parent table and the subclass tables are extracted */
        $captain1 = $this->objFromFixture(DataObjectTest\Player::class, "captain1");
        // Base field
        $this->assertEquals('Captain', $captain1->FirstName);
        // Subclass field
        $this->assertEquals('007', $captain1->ShirtNumber);
        // Subclass has_one relation
        $this->assertEquals($this->idFromFixture(DataObjectTest\Team::class, 'team1'), $captain1->FavouriteTeamID);
    }

    public function testGetRelationClass()
    {
        $obj = new DataObjectTest\Player();
        $this->assertEquals(
            singleton(DataObjectTest\Player::class)->getRelationClass('FavouriteTeam'),
            DataObjectTest\Team::class,
            'has_one is properly inspected'
        );
        $this->assertEquals(
            singleton(DataObjectTest\Company::class)->getRelationClass('CurrentStaff'),
            DataObjectTest\Staff::class,
            'has_many is properly inspected'
        );
        $this->assertEquals(
            singleton(DataObjectTest\Team::class)->getRelationClass('Players'),
            DataObjectTest\Player::class,
            'many_many is properly inspected'
        );
        $this->assertEquals(
            singleton(DataObjectTest\Player::class)->getRelationClass('Teams'),
            DataObjectTest\Team::class,
            'belongs_many_many is properly inspected'
        );
        $this->assertEquals(
            singleton(DataObjectTest\CEO::class)->getRelationClass('Company'),
            DataObjectTest\Company::class,
            'belongs_to is properly inspected'
        );
        $this->assertEquals(
            singleton(DataObjectTest\Fan::class)->getRelationClass('Favourite'),
            DataObject::class,
            'polymorphic has_one is properly inspected'
        );
    }

    /**
     * Test that has_one relations can be retrieved
     */
    public function testGetHasOneRelations()
    {
        $captain1 = $this->objFromFixture(DataObjectTest\Player::class, "captain1");
        $team1ID = $this->idFromFixture(DataObjectTest\Team::class, 'team1');

        // There will be a field called (relname)ID that contains the ID of the
        // object linked to via the has_one relation
        $this->assertEquals($team1ID, $captain1->FavouriteTeamID);

        // There will be a method called $obj->relname() that returns the object itself
        $this->assertEquals($team1ID, $captain1->FavouriteTeam()->ID);

        // Test that getNonReciprocalComponent can find has_one from the has_many end
        $this->assertEquals(
            $team1ID,
            $captain1->inferReciprocalComponent(DataObjectTest\Team::class, 'PlayerFans')->ID
        );

        // Check entity with polymorphic has-one
        $fan1 = $this->objFromFixture(DataObjectTest\Fan::class, "fan1");
        $this->assertTrue((bool)$fan1->hasValue('Favourite'));

        // There will be fields named (relname)ID and (relname)Class for polymorphic
        // entities
        $this->assertEquals($team1ID, $fan1->FavouriteID);
        $this->assertEquals(DataObjectTest\Team::class, $fan1->FavouriteClass);

        // There will be a method called $obj->relname() that returns the object itself
        $favourite = $fan1->Favourite();
        $this->assertEquals($team1ID, $favourite->ID);
        $this->assertInstanceOf(DataObjectTest\Team::class, $favourite);

        // check behaviour of dbObject with polymorphic relations
        $favouriteDBObject = $fan1->dbObject('Favourite');
        $favouriteValue = $favouriteDBObject->getValue();
        $this->assertInstanceOf(DBPolymorphicForeignKey::class, $favouriteDBObject);
        $this->assertEquals($favourite->ID, $favouriteValue->ID);
        $this->assertEquals($favourite->ClassName, $favouriteValue->ClassName);
    }

    public function testLimitAndCount()
    {
        $players = DataObject::get(DataObjectTest\Player::class);

        // There's 4 records in total
        $this->assertEquals(4, $players->count());

        // Testing "##, ##" syntax
        $this->assertEquals(4, $players->limit(20)->count());
        $this->assertEquals(4, $players->limit(20, 0)->count());
        $this->assertEquals(0, $players->limit(20, 20)->count());
        $this->assertEquals(2, $players->limit(2, 0)->count());
        $this->assertEquals(1, $players->limit(5, 3)->count());
    }

    public function testWriteNoChangesDoesntUpdateLastEdited()
    {
        // set mock now so we can be certain of LastEdited time for our test
        DBDatetime::set_mock_now('2017-01-01 00:00:00');
        $obj = new Player();
        $obj->FirstName = 'Test';
        $obj->Surname = 'Plater';
        $obj->Email = 'test.player@example.com';
        $obj->write();
        $this->assertEquals('2017-01-01 00:00:00', $obj->LastEdited);
        $writtenObj = Player::get()->byID($obj->ID);
        $this->assertEquals('2017-01-01 00:00:00', $writtenObj->LastEdited);

        // set mock now so we get a new LastEdited if, for some reason, it's updated
        DBDatetime::set_mock_now('2017-02-01 00:00:00');
        $writtenObj->write();
        $this->assertEquals('2017-01-01 00:00:00', $writtenObj->LastEdited);
        $this->assertEquals($obj->ID, $writtenObj->ID);

        $reWrittenObj = Player::get()->byID($writtenObj->ID);
        $this->assertEquals('2017-01-01 00:00:00', $reWrittenObj->LastEdited);
    }

    /**
     * Test writing of database columns which don't correlate to a DBField,
     * e.g. all relation fields on has_one/has_many like "ParentID".
     */
    public function testWritePropertyWithoutDBField()
    {
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $obj->FavouriteTeamID = 99;
        $obj->write();

        // reload the page from the database
        $savedObj = DataObject::get_by_id(DataObjectTest\Player::class, $obj->ID);
        $this->assertTrue($savedObj->FavouriteTeamID == 99);

        // Test with porymorphic relation
        $obj2 = $this->objFromFixture(DataObjectTest\Fan::class, "fan1");
        $obj2->FavouriteID = 99;
        $obj2->FavouriteClass = DataObjectTest\Player::class;
        $obj2->write();

        $savedObj2 = DataObject::get_by_id(DataObjectTest\Fan::class, $obj2->ID);
        $this->assertTrue($savedObj2->FavouriteID == 99);
        $this->assertTrue($savedObj2->FavouriteClass == DataObjectTest\Player::class);
    }

    /**
     * Test has many relationships
     *   - Test getComponents() gets the ComponentSet of the other side of the relation
     *   - Test the IDs on the DataObjects are set correctly
     */
    public function testHasManyRelationships()
    {
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');

        // Test getComponents() gets the ComponentSet of the other side of the relation
        $this->assertTrue($team1->Comments()->count() == 2);

        $team1Comments = [
            ['Comment' => 'This is a team comment by Joe'],
            ['Comment' => 'This is a team comment by Bob'],
        ];

        // Test the IDs on the DataObjects are set correctly
        $this->assertListEquals($team1Comments, $team1->Comments());

        // Test that has_many can be infered from the has_one via getNonReciprocalComponent
        $this->assertListEquals(
            $team1Comments,
            $team1->inferReciprocalComponent(DataObjectTest\TeamComment::class, 'Team')
        );

        // Test that we can add and remove items that already exist in the database
        $newComment = new DataObjectTest\TeamComment();
        $newComment->Name = "Automated commenter";
        $newComment->Comment = "This is a new comment";
        $newComment->write();
        $team1->Comments()->add($newComment);
        $this->assertEquals($team1->ID, $newComment->TeamID);

        $comment1 = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1');
        $comment2 = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment2');
        $team1->Comments()->remove($comment2);

        $team1CommentIDs = $team1->Comments()->sort('ID')->column('ID');
        $this->assertEquals(array($comment1->ID, $newComment->ID), $team1CommentIDs);

        // Test that removing an item from a list doesn't remove it from the same
        // relation belonging to a different object
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $team2 = $this->objFromFixture(DataObjectTest\Team::class, 'team2');
        $team2->Comments()->remove($comment1);
        $team1CommentIDs = $team1->Comments()->sort('ID')->column('ID');
        $this->assertEquals(array($comment1->ID, $newComment->ID), $team1CommentIDs);
    }


    /**
     * Test has many relationships against polymorphic has_one fields
     *   - Test getComponents() gets the ComponentSet of the other side of the relation
     *   - Test the IDs on the DataObjects are set correctly
     */
    public function testHasManyPolymorphicRelationships()
    {
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');

        // Test getComponents() gets the ComponentSet of the other side of the relation
        $this->assertTrue($team1->Fans()->count() == 2);

        // Test the IDs/Classes on the DataObjects are set correctly
        foreach ($team1->Fans() as $fan) {
            $this->assertEquals($team1->ID, $fan->FavouriteID, 'Fan has the correct FavouriteID');
            $this->assertEquals(DataObjectTest\Team::class, $fan->FavouriteClass, 'Fan has the correct FavouriteClass');
        }

        // Test that we can add and remove items that already exist in the database
        $newFan = new DataObjectTest\Fan();
        $newFan->Name = "New fan";
        $newFan->write();
        $team1->Fans()->add($newFan);
        $this->assertEquals($team1->ID, $newFan->FavouriteID, 'Newly created fan has the correct FavouriteID');
        $this->assertEquals(
            DataObjectTest\Team::class,
            $newFan->FavouriteClass,
            'Newly created fan has the correct FavouriteClass'
        );

        $fan1 = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $fan3 = $this->objFromFixture(DataObjectTest\Fan::class, 'fan3');
        $team1->Fans()->remove($fan3);

        $team1FanIDs = $team1->Fans()->sort('ID')->column('ID');
        $this->assertEquals(array($fan1->ID, $newFan->ID), $team1FanIDs);

        // Test that removing an item from a list doesn't remove it from the same
        // relation belonging to a different object
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $player1 = $this->objFromFixture(DataObjectTest\Player::class, 'player1');
        $player1->Fans()->remove($fan1);
        $team1FanIDs = $team1->Fans()->sort('ID')->column('ID');
        $this->assertEquals(array($fan1->ID, $newFan->ID), $team1FanIDs);
    }


    public function testHasOneRelationship()
    {
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $player1 = $this->objFromFixture(DataObjectTest\Player::class, 'player1');
        $player2 = $this->objFromFixture(DataObjectTest\Player::class, 'player2');
        $fan1 = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');

        // Test relation probing
        $this->assertFalse((bool)$team1->hasValue('Captain', null, false));
        $this->assertFalse((bool)$team1->hasValue('CaptainID', null, false));

        // Add a captain to team 1
        $team1->setField('CaptainID', $player1->ID);
        $team1->write();

        $this->assertTrue((bool)$team1->hasValue('Captain', null, false));
        $this->assertTrue((bool)$team1->hasValue('CaptainID', null, false));

        $this->assertEquals(
            $player1->ID,
            $team1->Captain()->ID,
            'The captain exists for team 1'
        );
        $this->assertEquals(
            $player1->ID,
            $team1->getComponent('Captain')->ID,
            'The captain exists through the component getter'
        );

        $this->assertEquals(
            $team1->Captain()->FirstName,
            'Player 1',
            'Player 1 is the captain'
        );
        $this->assertEquals(
            $team1->getComponent('Captain')->FirstName,
            'Player 1',
            'Player 1 is the captain'
        );

        $team1->CaptainID = $player2->ID;
        $team1->write();

        $this->assertEquals($player2->ID, $team1->Captain()->ID);
        $this->assertEquals($player2->ID, $team1->getComponent('Captain')->ID);
        $this->assertEquals('Player 2', $team1->Captain()->FirstName);
        $this->assertEquals('Player 2', $team1->getComponent('Captain')->FirstName);


        // Set the favourite team for fan1
        $fan1->setField('FavouriteID', $team1->ID);
        $fan1->setField('FavouriteClass', get_class($team1));

        $this->assertEquals($team1->ID, $fan1->Favourite()->ID, 'The team is assigned to fan 1');
        $this->assertInstanceOf(get_class($team1), $fan1->Favourite(), 'The team is assigned to fan 1');
        $this->assertEquals(
            $team1->ID,
            $fan1->getComponent('Favourite')->ID,
            'The team exists through the component getter'
        );
        $this->assertInstanceOf(
            get_class($team1),
            $fan1->getComponent('Favourite'),
            'The team exists through the component getter'
        );

        $this->assertEquals(
            $fan1->Favourite()->Title,
            'Team 1',
            'Team 1 is the favourite'
        );
        $this->assertEquals(
            $fan1->getComponent('Favourite')->Title,
            'Team 1',
            'Team 1 is the favourite'
        );
    }

    /**
     * @todo Extend type change tests (e.g. '0'==NULL)
     */
    public function testChangedFields()
    {
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $obj->FirstName = 'Captain-changed';
        $obj->IsRetired = true;

        $this->assertEquals(
            $obj->getChangedFields(true, DataObject::CHANGE_STRICT),
            array(
                'FirstName' => array(
                    'before' => 'Captain',
                    'after' => 'Captain-changed',
                    'level' => DataObject::CHANGE_VALUE
                ),
                'IsRetired' => array(
                    'before' => 1,
                    'after' => true,
                    'level' => DataObject::CHANGE_STRICT
                )
            ),
            'Changed fields are correctly detected with strict type changes (level=1)'
        );

        $this->assertEquals(
            $obj->getChangedFields(true, DataObject::CHANGE_VALUE),
            array(
                'FirstName' => array(
                    'before' => 'Captain',
                    'after' => 'Captain-changed',
                    'level' => DataObject::CHANGE_VALUE
                )
            ),
            'Changed fields are correctly detected while ignoring type changes (level=2)'
        );

        $newObj = new DataObjectTest\Player();
        $newObj->FirstName = "New Player";
        $this->assertEquals(
            array(
                'FirstName' => array(
                    'before' => null,
                    'after' => 'New Player',
                    'level' => DataObject::CHANGE_VALUE
                )
            ),
            $newObj->getChangedFields(true, DataObject::CHANGE_VALUE),
            'Initialised fields are correctly detected as full changes'
        );
    }

    /**
     * @skipUpgrade
     */
    public function testIsChanged()
    {
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $obj->NonDBField = 'bob';
        $obj->FirstName = 'Captain-changed';
        $obj->IsRetired = true; // type change only, database stores "1"

        // Now that DB fields are changed, isChanged is true
        $this->assertTrue($obj->isChanged('NonDBField'));
        $this->assertFalse($obj->isChanged('NonField'));
        $this->assertTrue($obj->isChanged('FirstName', DataObject::CHANGE_STRICT));
        $this->assertTrue($obj->isChanged('FirstName', DataObject::CHANGE_VALUE));
        $this->assertTrue($obj->isChanged('IsRetired', DataObject::CHANGE_STRICT));
        $this->assertFalse($obj->isChanged('IsRetired', DataObject::CHANGE_VALUE));
        $this->assertFalse($obj->isChanged('Email', 1), 'Doesnt change mark unchanged property');
        $this->assertFalse($obj->isChanged('Email', 2), 'Doesnt change mark unchanged property');

        $newObj = new DataObjectTest\Player();
        $newObj->FirstName = "New Player";
        $this->assertTrue($newObj->isChanged('FirstName', DataObject::CHANGE_STRICT));
        $this->assertTrue($newObj->isChanged('FirstName', DataObject::CHANGE_VALUE));
        $this->assertFalse($newObj->isChanged('Email', DataObject::CHANGE_STRICT));
        $this->assertFalse($newObj->isChanged('Email', DataObject::CHANGE_VALUE));

        $newObj->write();
        $this->assertFalse($newObj->ischanged());
        $this->assertFalse($newObj->isChanged('FirstName', DataObject::CHANGE_STRICT));
        $this->assertFalse($newObj->isChanged('FirstName', DataObject::CHANGE_VALUE));
        $this->assertFalse($newObj->isChanged('Email', DataObject::CHANGE_STRICT));
        $this->assertFalse($newObj->isChanged('Email', DataObject::CHANGE_VALUE));

        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $obj->FirstName = null;
        $this->assertTrue($obj->isChanged('FirstName', DataObject::CHANGE_STRICT));
        $this->assertTrue($obj->isChanged('FirstName', DataObject::CHANGE_VALUE));

        /* Test when there's not field provided */
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain2');
        $this->assertFalse($obj->isChanged());
        $obj->NonDBField = 'new value';
        $this->assertFalse($obj->isChanged());
        $obj->FirstName = "New Player";
        $this->assertTrue($obj->isChanged());

        $obj->write();
        $this->assertFalse($obj->isChanged());
    }

    public function testRandomSort()
    {
        /* If we perform the same regularly sorted query twice, it should return the same results */
        $itemsA = DataObject::get(DataObjectTest\TeamComment::class, "", "ID");
        foreach ($itemsA as $item) {
            $keysA[] = $item->ID;
        }

        $itemsB = DataObject::get(DataObjectTest\TeamComment::class, "", "ID");
        foreach ($itemsB as $item) {
            $keysB[] = $item->ID;
        }

        /* Test when there's not field provided */
        $obj = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $obj->FirstName = "New Player";
        $this->assertTrue($obj->isChanged());

        $obj->write();
        $this->assertFalse($obj->isChanged());

        /* If we perform the same random query twice, it shouldn't return the same results */
        $itemsA = DataObject::get(DataObjectTest\TeamComment::class, "", DB::get_conn()->random());
        $itemsB = DataObject::get(DataObjectTest\TeamComment::class, "", DB::get_conn()->random());
        $itemsC = DataObject::get(DataObjectTest\TeamComment::class, "", DB::get_conn()->random());
        $itemsD = DataObject::get(DataObjectTest\TeamComment::class, "", DB::get_conn()->random());
        foreach ($itemsA as $item) {
            $keysA[] = $item->ID;
        }
        foreach ($itemsB as $item) {
            $keysB[] = $item->ID;
        }
        foreach ($itemsC as $item) {
            $keysC[] = $item->ID;
        }
        foreach ($itemsD as $item) {
            $keysD[] = $item->ID;
        }

        // These shouldn't all be the same (run it 4 times to minimise chance of an accidental collision)
        // There's about a 1 in a billion chance of an accidental collision
        $this->assertTrue($keysA != $keysB || $keysB != $keysC || $keysC != $keysD);
    }

    public function testWriteSavesToHasOneRelations()
    {
        /* DataObject::write() should save to a has_one relationship if you set a field called (relname)ID */
        $team = new DataObjectTest\Team();
        $captainID = $this->idFromFixture(DataObjectTest\Player::class, 'player1');
        $team->CaptainID = $captainID;
        $team->write();
        $this->assertEquals(
            $captainID,
            DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value()
        );

        /* After giving it a value, you should also be able to set it back to null */
        $team->CaptainID = '';
        $team->write();
        $this->assertEquals(
            0,
            DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value()
        );

        /* You should also be able to save a blank to it when it's first created */
        $team = new DataObjectTest\Team();
        $team->CaptainID = '';
        $team->write();
        $this->assertEquals(
            0,
            DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $team->ID")->value()
        );

        /* Ditto for existing records without a value */
        $existingTeam = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $existingTeam->CaptainID = '';
        $existingTeam->write();
        $this->assertEquals(
            0,
            DB::query("SELECT \"CaptainID\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $existingTeam->ID")->value()
        );
    }

    public function testCanAccessHasOneObjectsAsMethods()
    {
        /* If you have a has_one relation 'Captain' on $obj, and you set the $obj->CaptainID = (ID), then the
        * object itself should be accessible as $obj->Captain() */
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $captainID = $this->idFromFixture(DataObjectTest\Player::class, 'captain1');

        $team->CaptainID = $captainID;
        $this->assertNotNull($team->Captain());
        $this->assertEquals($captainID, $team->Captain()->ID);

        // Test for polymorphic has_one relations
        $fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $fan->FavouriteID = $team->ID;
        $fan->FavouriteClass = DataObjectTest\Team::class;
        $this->assertNotNull($fan->Favourite());
        $this->assertEquals($team->ID, $fan->Favourite()->ID);
        $this->assertInstanceOf(DataObjectTest\Team::class, $fan->Favourite());
    }

    public function testFieldNamesThatMatchMethodNamesWork()
    {
        /* Check that a field name that corresponds to a method on DataObject will still work */
        $obj = new DataObjectTest\Fixture();
        $obj->Data = "value1";
        $obj->DbObject = "value2";
        $obj->Duplicate = "value3";
        $obj->write();

        $this->assertNotNull($obj->ID);
        $this->assertEquals(
            'value1',
            DB::query("SELECT \"Data\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value()
        );
        $this->assertEquals(
            'value2',
            DB::query("SELECT \"DbObject\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value()
        );
        $this->assertEquals(
            'value3',
            DB::query("SELECT \"Duplicate\" FROM \"DataObjectTest_Fixture\" WHERE \"ID\" = $obj->ID")->value()
        );
    }

    /**
     * @todo Re-enable all test cases for field existence after behaviour has been fixed
     */
    public function testFieldExistence()
    {
        $teamInstance = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $teamSingleton = singleton(DataObjectTest\Team::class);

        $subteamInstance = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $schema = DataObject::getSchema();

        /* hasField() singleton checks */
        $this->assertTrue(
            $teamSingleton->hasField('ID'),
            'hasField() finds built-in fields in singletons'
        );
        $this->assertTrue(
            $teamSingleton->hasField('Title'),
            'hasField() finds custom fields in singletons'
        );

        /* hasField() instance checks */
        $this->assertFalse(
            $teamInstance->hasField('NonExistingField'),
            'hasField() doesnt find non-existing fields in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('ID'),
            'hasField() finds built-in fields in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('Created'),
            'hasField() finds built-in fields in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('DatabaseField'),
            'hasField() finds custom fields in instances'
        );
        //$this->assertFalse($teamInstance->hasField('SubclassDatabaseField'),
        //'hasField() doesnt find subclass fields in parentclass instances');
        $this->assertTrue(
            $teamInstance->hasField('DynamicField'),
            'hasField() finds dynamic getters in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('HasOneRelationshipID'),
            'hasField() finds foreign keys in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('ExtendedDatabaseField'),
            'hasField() finds extended fields in instances'
        );
        $this->assertTrue(
            $teamInstance->hasField('ExtendedHasOneRelationshipID'),
            'hasField() finds extended foreign keys in instances'
        );
        //$this->assertTrue($teamInstance->hasField('ExtendedDynamicField'),
        //'hasField() includes extended dynamic getters in instances');

        /* hasField() subclass checks */
        $this->assertTrue(
            $subteamInstance->hasField('ID'),
            'hasField() finds built-in fields in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('Created'),
            'hasField() finds built-in fields in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('DatabaseField'),
            'hasField() finds custom fields in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('SubclassDatabaseField'),
            'hasField() finds custom fields in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('DynamicField'),
            'hasField() finds dynamic getters in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('HasOneRelationshipID'),
            'hasField() finds foreign keys in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('ExtendedDatabaseField'),
            'hasField() finds extended fields in subclass instances'
        );
        $this->assertTrue(
            $subteamInstance->hasField('ExtendedHasOneRelationshipID'),
            'hasField() finds extended foreign keys in subclass instances'
        );

        /* hasDatabaseField() singleton checks */
        //$this->assertTrue($teamSingleton->hasDatabaseField('ID'),
        //'hasDatabaseField() finds built-in fields in singletons');
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'Title'),
            'hasDatabaseField() finds custom fields in singletons'
        );

        /* hasDatabaseField() instance checks */
        $this->assertNull(
            $schema->fieldSpec(DataObjectTest\Team::class, 'NonExistingField'),
            'hasDatabaseField() doesnt find non-existing fields in instances'
        );
        //$this->assertNotEmpty($schema->fieldSpec(DataObjectTest_Team::class, 'ID'),
        //'hasDatabaseField() finds built-in fields in instances');
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'Created'),
            'hasDatabaseField() finds built-in fields in instances'
        );
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'DatabaseField'),
            'hasDatabaseField() finds custom fields in instances'
        );
        $this->assertNull(
            $schema->fieldSpec(DataObjectTest\Team::class, 'SubclassDatabaseField'),
            'hasDatabaseField() doesnt find subclass fields in parentclass instances'
        );
        //$this->assertNull($schema->fieldSpec(DataObjectTest_Team::class, 'DynamicField'),
        //'hasDatabaseField() doesnt dynamic getters in instances');
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'HasOneRelationshipID'),
            'hasDatabaseField() finds foreign keys in instances'
        );
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'ExtendedDatabaseField'),
            'hasDatabaseField() finds extended fields in instances'
        );
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\Team::class, 'ExtendedHasOneRelationshipID'),
            'hasDatabaseField() finds extended foreign keys in instances'
        );
        $this->assertNull(
            $schema->fieldSpec(DataObjectTest\Team::class, 'ExtendedDynamicField'),
            'hasDatabaseField() doesnt include extended dynamic getters in instances'
        );

        /* hasDatabaseField() subclass checks */
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\SubTeam::class, 'DatabaseField'),
            'hasField() finds custom fields in subclass instances'
        );
        $this->assertNotEmpty(
            $schema->fieldSpec(DataObjectTest\SubTeam::class, 'SubclassDatabaseField'),
            'hasField() finds custom fields in subclass instances'
        );
    }

    /**
     * @todo Re-enable all test cases for field inheritance aggregation after behaviour has been fixed
     */
    public function testFieldInheritance()
    {
        $schema = DataObject::getSchema();

        // Test logical fields (including composite)
        $teamSpecifications = $schema->fieldSpecs(DataObjectTest\Team::class);
        $this->assertEquals(
            array(
                'ID',
                'ClassName',
                'LastEdited',
                'Created',
                'Title',
                'DatabaseField',
                'ExtendedDatabaseField',
                'CaptainID',
                'FounderID',
                'HasOneRelationshipID',
                'ExtendedHasOneRelationshipID'
            ),
            array_keys($teamSpecifications),
            'fieldSpecifications() contains all fields defined on instance: base, extended and foreign keys'
        );

        $teamFields = $schema->databaseFields(DataObjectTest\Team::class, false);
        $this->assertEquals(
            array(
                'ID',
                'ClassName',
                'LastEdited',
                'Created',
                'Title',
                'DatabaseField',
                'ExtendedDatabaseField',
                'CaptainID',
                'FounderID',
                'HasOneRelationshipID',
                'ExtendedHasOneRelationshipID'
            ),
            array_keys($teamFields),
            'databaseFields() contains only fields defined on instance, including base, extended and foreign keys'
        );

        $subteamSpecifications = $schema->fieldSpecs(DataObjectTest\SubTeam::class);
        $this->assertEquals(
            array(
                'ID',
                'ClassName',
                'LastEdited',
                'Created',
                'Title',
                'DatabaseField',
                'ExtendedDatabaseField',
                'CaptainID',
                'FounderID',
                'HasOneRelationshipID',
                'ExtendedHasOneRelationshipID',
                'SubclassDatabaseField',
                'ParentTeamID',
            ),
            array_keys($subteamSpecifications),
            'fieldSpecifications() on subclass contains all fields, including base, extended  and foreign keys'
        );

        $subteamFields = $schema->databaseFields(DataObjectTest\SubTeam::class, false);
        $this->assertEquals(
            array(
                'ID',
                'SubclassDatabaseField',
                'ParentTeamID',
            ),
            array_keys($subteamFields),
            'databaseFields() on subclass contains only fields defined on instance'
        );
    }

    public function testSearchableFields()
    {
        $player = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $fields = $player->searchableFields();
        $this->assertArrayHasKey(
            'IsRetired',
            $fields,
            'Fields defined by $searchable_fields static are correctly detected'
        );
        $this->assertArrayHasKey(
            'ShirtNumber',
            $fields,
            'Fields defined by $searchable_fields static are correctly detected'
        );

        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $fields = $team->searchableFields();
        $this->assertArrayHasKey(
            'Title',
            $fields,
            'Fields can be inherited from the $summary_fields static, including methods called on fields'
        );
        $this->assertArrayHasKey(
            'Captain.ShirtNumber',
            $fields,
            'Fields on related objects can be inherited from the $summary_fields static'
        );
        $this->assertArrayHasKey(
            'Captain.FavouriteTeam.Title',
            $fields,
            'Fields on related objects can be inherited from the $summary_fields static'
        );

        $testObj = new DataObjectTest\Fixture();
        $fields = $testObj->searchableFields();
        $this->assertEmpty($fields);
    }

    public function testCastingHelper()
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');

        $this->assertEquals('Varchar', $team->castingHelper('Title'), 'db field wasn\'t casted correctly');
        $this->assertEquals('HTMLVarchar', $team->castingHelper('DatabaseField'), 'db field wasn\'t casted correctly');

        $sponsor = $team->Sponsors()->first();
        $this->assertEquals('Int', $sponsor->castingHelper('SponsorFee'), 'many_many_extraFields not casted correctly');
    }

    public function testSummaryFieldsCustomLabels()
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $summaryFields = $team->summaryFields();

        $this->assertEquals(
            [
                'Title' => 'Custom Title',
                'Title.UpperCase' => 'Title',
                'Captain.ShirtNumber' => 'Captain\'s shirt number',
                'Captain.FavouriteTeam.Title' => 'Captain\'s favourite team',
            ],
            $summaryFields
        );
    }

    public function testDataObjectUpdate()
    {
        /* update() calls can use the dot syntax to reference has_one relations and other methods that return
        * objects */
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $team1->CaptainID = $this->idFromFixture(DataObjectTest\Player::class, 'captain1');

        $team1->update(
            array(
                'DatabaseField' => 'Something',
                'Captain.FirstName' => 'Jim',
                'Captain.Email' => 'jim@example.com',
                'Captain.FavouriteTeam.Title' => 'New and improved team 1',
            )
        );

        /* Test the simple case of updating fields on the object itself */
        $this->assertEquals('Something', $team1->DatabaseField);

        /* Setting Captain.Email and Captain.FirstName will have updated DataObjectTest_Captain.captain1 in
        * the database.  Although update() doesn't usually write, it does write related records automatically. */
        $captain1 = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        $this->assertEquals('Jim', $captain1->FirstName);
        $this->assertEquals('jim@example.com', $captain1->Email);

        /* Jim's favourite team is team 1; we need to reload the object to the the change that setting Captain.
        * FavouriteTeam.Title made */
        $reloadedTeam1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $this->assertEquals('New and improved team 1', $reloadedTeam1->Title);
    }

    public function testDataObjectUpdateNew()
    {
        /* update() calls can use the dot syntax to reference has_one relations and other methods that return
        * objects */
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $team1->CaptainID = 0;

        $team1->update(
            array(
                'Captain.FirstName' => 'Jim',
                'Captain.FavouriteTeam.Title' => 'New and improved team 1',
            )
        );
        /* Test that the captain ID has been updated */
        $this->assertGreaterThan(0, $team1->CaptainID);

        /* Fetch the newly created captain */
        $captain1 = DataObjectTest\Player::get()->byID($team1->CaptainID);
        $this->assertEquals('Jim', $captain1->FirstName);

        /* Grab the favourite team and make sure it has the correct values */
        $reloadedTeam1 = $captain1->FavouriteTeam();
        $this->assertEquals($reloadedTeam1->ID, $captain1->FavouriteTeamID);
        $this->assertEquals('New and improved team 1', $reloadedTeam1->Title);
    }


    /**
     * @expectedException \SilverStripe\ORM\ValidationException
     */
    public function testWritingInvalidDataObjectThrowsException()
    {
        $validatedObject = new DataObjectTest\ValidatedObject();
        $validatedObject->write();
    }

    public function testWritingValidDataObjectDoesntThrowException()
    {
        $validatedObject = new DataObjectTest\ValidatedObject();
        $validatedObject->Name = "Mr. Jones";

        $validatedObject->write();
        $this->assertTrue($validatedObject->isInDB(), "Validated object was not saved to database");
    }

    public function testSubclassCreation()
    {
        /* Creating a new object of a subclass should set the ClassName field correctly */
        $obj = new DataObjectTest\SubTeam();
        $obj->write();
        $this->assertEquals(
            DataObjectTest\SubTeam::class,
            DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value()
        );
    }

    public function testForceInsert()
    {
        /* If you set an ID on an object and pass forceInsert = true, then the object should be correctly created */
        $conn = DB::get_conn();
        if (method_exists($conn, 'allowPrimaryKeyEditing')) {
            $conn->allowPrimaryKeyEditing(DataObjectTest\Team::class, true);
        }
        $obj = new DataObjectTest\SubTeam();
        $obj->ID = 1001;
        $obj->Title = 'asdfasdf';
        $obj->SubclassDatabaseField = 'asdfasdf';
        $obj->write(false, true);
        if (method_exists($conn, 'allowPrimaryKeyEditing')) {
            $conn->allowPrimaryKeyEditing(DataObjectTest\Team::class, false);
        }

        $this->assertEquals(
            DataObjectTest\SubTeam::class,
            DB::query("SELECT \"ClassName\" FROM \"DataObjectTest_Team\" WHERE \"ID\" = $obj->ID")->value()
        );

        /* Check that it actually saves to the database with the correct ID */
        $this->assertEquals(
            "1001",
            DB::query(
                "SELECT \"ID\" FROM \"DataObjectTest_SubTeam\" WHERE \"SubclassDatabaseField\" = 'asdfasdf'"
            )->value()
        );
        $this->assertEquals(
            "1001",
            DB::query("SELECT \"ID\" FROM \"DataObjectTest_Team\" WHERE \"Title\" = 'asdfasdf'")->value()
        );
    }

    public function testHasOwnTable()
    {
        $schema = DataObject::getSchema();
        /* Test DataObject::has_own_table() returns true if the object has $has_one or $db values */
        $this->assertTrue($schema->classHasTable(DataObjectTest\Player::class));
        $this->assertTrue($schema->classHasTable(DataObjectTest\Team::class));
        $this->assertTrue($schema->classHasTable(DataObjectTest\Fixture::class));

        /* Root DataObject that always have a table, even if they lack both $db and $has_one */
        $this->assertTrue($schema->classHasTable(DataObjectTest\FieldlessTable::class));

        /* Subclasses without $db or $has_one don't have a table */
        $this->assertFalse($schema->classHasTable(DataObjectTest\FieldlessSubTable::class));

        /* Return false if you don't pass it a subclass of DataObject */
        $this->assertFalse($schema->classHasTable(DataObject::class));
        $this->assertFalse($schema->classHasTable(ViewableData::class));

        /* Invalid class name */
        $this->assertFalse($schema->classHasTable("ThisIsntADataObject"));
    }

    public function testMerge()
    {
        // test right merge of subclasses
        $left = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $right = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam2_with_player_relation');
        $leftOrigID = $left->ID;
        $left->merge($right, 'right', false, false);
        $this->assertEquals(
            $left->Title,
            'Subteam 2',
            'merge() with "right" priority overwrites fields with existing values on subclasses'
        );
        $this->assertEquals(
            $left->ID,
            $leftOrigID,
            'merge() with "right" priority doesnt overwrite database ID'
        );

        // test overwriteWithEmpty flag on existing left values
        $left = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam2_with_player_relation');
        $right = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam3_with_empty_fields');
        $left->merge($right, 'right', false, true);
        $this->assertEquals(
            $left->Title,
            'Subteam 3',
            'merge() with $overwriteWithEmpty overwrites non-empty fields on left object'
        );

        // test overwriteWithEmpty flag on empty left values
        $left = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        // $SubclassDatabaseField is empty on here
        $right = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam2_with_player_relation');
        $left->merge($right, 'right', false, true);
        $this->assertEquals(
            $left->SubclassDatabaseField,
            null,
            'merge() with $overwriteWithEmpty overwrites empty fields on left object'
        );

        // @todo test "left" priority flag
        // @todo test includeRelations flag
        // @todo test includeRelations in combination with overwriteWithEmpty
        // @todo test has_one relations
        // @todo test has_many and many_many relations
    }

    public function testPopulateDefaults()
    {
        $obj = new DataObjectTest\Fixture();
        $this->assertEquals(
            $obj->MyFieldWithDefault,
            'Default Value',
            'Defaults are populated for in-memory object from $defaults array'
        );

        $this->assertEquals(
            $obj->MyFieldWithAltDefault,
            'Default Value',
            'Defaults are populated from overloaded populateDefaults() method'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateModelDefinitionsFailsWithArray()
    {
        Config::modify()->merge(DataObjectTest\Team::class, 'has_one', array('NotValid' => array('NoArraysAllowed')));
        DataObject::getSchema()->hasOneComponent(DataObjectTest\Team::class, 'NotValid');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateModelDefinitionsFailsWithIntKey()
    {
        Config::modify()->set(DataObjectTest\Team::class, 'has_many', array(0 => DataObjectTest\Player::class));
        DataObject::getSchema()->hasManyComponent(DataObjectTest\Team::class, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidateModelDefinitionsFailsWithIntValue()
    {
        Config::modify()->merge(DataObjectTest\Team::class, 'many_many', array('Players' => 12));
        DataObject::getSchema()->manyManyComponent(DataObjectTest\Team::class, 'Players');
    }

    public function testNewClassInstance()
    {
        $dataObject = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $changedDO = $dataObject->newClassInstance(DataObjectTest\SubTeam::class);
        $changedFields = $changedDO->getChangedFields();

        // Don't write the record, it will reset changed fields
        $this->assertInstanceOf(DataObjectTest\SubTeam::class, $changedDO);
        $this->assertEquals($changedDO->ClassName, DataObjectTest\SubTeam::class);
        $this->assertEquals($changedDO->RecordClassName, DataObjectTest\SubTeam::class);
        $this->assertContains('ClassName', array_keys($changedFields));
        $this->assertEquals($changedFields['ClassName']['before'], DataObjectTest\Team::class);
        $this->assertEquals($changedFields['ClassName']['after'], DataObjectTest\SubTeam::class);
        $this->assertEquals($changedFields['RecordClassName']['before'], DataObjectTest\Team::class);
        $this->assertEquals($changedFields['RecordClassName']['after'], DataObjectTest\SubTeam::class);

        $changedDO->write();

        $this->assertInstanceOf(DataObjectTest\SubTeam::class, $changedDO);
        $this->assertEquals($changedDO->ClassName, DataObjectTest\SubTeam::class);

        // Test invalid classes fail
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller is not a valid subclass of DataObject');
        /**
         * @skipUpgrade
         */
        $dataObject->newClassInstance('Controller');
    }

    public function testMultipleManyManyWithSameClass()
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $company2 = $this->objFromFixture(DataObjectTest\EquipmentCompany::class, 'equipmentcompany2');
        $sponsors = $team->Sponsors();
        $equipmentSuppliers = $team->EquipmentSuppliers();

        // Check that DataObject::many_many() works as expected
        $manyManyComponent = DataObject::getSchema()->manyManyComponent(DataObjectTest\Team::class, 'Sponsors');
        $this->assertEquals(ManyManyList::class, $manyManyComponent['relationClass']);
        $this->assertEquals(
            DataObjectTest\Team::class,
            $manyManyComponent['parentClass'],
            'DataObject::many_many() didn\'t find the correct base class'
        );
        $this->assertEquals(
            DataObjectTest\EquipmentCompany::class,
            $manyManyComponent['childClass'],
            'DataObject::many_many() didn\'t find the correct target class for the relation'
        );
        $this->assertEquals(
            'DataObjectTest_EquipmentCompany_SponsoredTeams',
            $manyManyComponent['join'],
            'DataObject::many_many() didn\'t find the correct relation table'
        );
        $this->assertEquals('DataObjectTest_TeamID', $manyManyComponent['parentField']);
        $this->assertEquals('DataObjectTest_EquipmentCompanyID', $manyManyComponent['childField']);

        // Check that ManyManyList still works
        $this->assertEquals(2, $sponsors->count(), 'Rows are missing from relation');
        $this->assertEquals(1, $equipmentSuppliers->count(), 'Rows are missing from relation');

        // Check everything works when no relation is present
        $teamWithoutSponsor = $this->objFromFixture(DataObjectTest\Team::class, 'team3');
        $this->assertInstanceOf(ManyManyList::class, $teamWithoutSponsor->Sponsors());
        $this->assertEquals(0, $teamWithoutSponsor->Sponsors()->count());

        // Test that belongs_many_many can be infered from with getNonReciprocalComponent
        $this->assertListEquals(
            [
                ['Name' => 'Company corp'],
                ['Name' => 'Team co.'],
            ],
            $team->inferReciprocalComponent(DataObjectTest\EquipmentCompany::class, 'SponsoredTeams')
        );

        // Test that many_many can be infered from getNonReciprocalComponent
        $this->assertListEquals(
            [
                ['Title' => 'Team 1'],
                ['Title' => 'Team 2'],
                ['Title' => 'Subteam 1'],
            ],
            $company2->inferReciprocalComponent(DataObjectTest\Team::class, 'Sponsors')
        );

        // Check many_many_extraFields still works
        $equipmentCompany = $this->objFromFixture(DataObjectTest\EquipmentCompany::class, 'equipmentcompany1');
        $equipmentCompany->SponsoredTeams()->add($teamWithoutSponsor, array('SponsorFee' => 1000));
        $sponsoredTeams = $equipmentCompany->SponsoredTeams();
        $this->assertEquals(
            1000,
            $sponsoredTeams->byID($teamWithoutSponsor->ID)->SponsorFee,
            'Data from many_many_extraFields was not stored/extracted correctly'
        );

        // Check subclasses correctly inherit multiple many_manys
        $subTeam = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $this->assertEquals(
            2,
            $subTeam->Sponsors()->count(),
            'Child class did not inherit multiple many_manys'
        );
        $this->assertEquals(
            1,
            $subTeam->EquipmentSuppliers()->count(),
            'Child class did not inherit multiple many_manys'
        );
        // Team 2 has one EquipmentCompany sponsor and one SubEquipmentCompany
        $team2 = $this->objFromFixture(DataObjectTest\Team::class, 'team2');
        $this->assertEquals(
            2,
            $team2->Sponsors()->count(),
            'Child class did not inherit multiple belongs_many_manys'
        );

        // Check many_many_extraFields also works from the belongs_many_many side
        $sponsors = $team2->Sponsors();
        $sponsors->add($equipmentCompany, array('SponsorFee' => 750));
        $this->assertEquals(
            750,
            $sponsors->byID($equipmentCompany->ID)->SponsorFee,
            'Data from many_many_extraFields was not stored/extracted correctly'
        );

        $subEquipmentCompany = $this->objFromFixture(DataObjectTest\SubEquipmentCompany::class, 'subequipmentcompany1');
        $subTeam->Sponsors()->add($subEquipmentCompany, array('SponsorFee' => 1200));
        $this->assertEquals(
            1200,
            $subTeam->Sponsors()->byID($subEquipmentCompany->ID)->SponsorFee,
            'Data from inherited many_many_extraFields was not stored/extracted correctly'
        );
    }

    public function testManyManyExtraFields()
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $schema = DataObject::getSchema();

        // Get all extra fields
        $teamExtraFields = $team->manyManyExtraFields();
        $this->assertEquals(
            array(
                'Players' => array('Position' => 'Varchar(100)')
            ),
            $teamExtraFields
        );

        // Ensure fields from parent classes are included
        $subTeam = singleton(DataObjectTest\SubTeam::class);
        $teamExtraFields = $subTeam->manyManyExtraFields();
        $this->assertEquals(
            array(
                'Players' => array('Position' => 'Varchar(100)'),
                'FormerPlayers' => array('Position' => 'Varchar(100)')
            ),
            $teamExtraFields
        );

        // Extra fields are immediately available on the Team class (defined in $many_many_extraFields)
        $teamExtraFields = $schema->manyManyExtraFieldsForComponent(DataObjectTest\Team::class, 'Players');
        $this->assertEquals(
            $teamExtraFields,
            array(
                'Position' => 'Varchar(100)'
            )
        );

        // We'll have to go through the relation to get the extra fields on Player
        $playerExtraFields = $schema->manyManyExtraFieldsForComponent(DataObjectTest\Player::class, 'Teams');
        $this->assertEquals(
            $playerExtraFields,
            array(
                'Position' => 'Varchar(100)'
            )
        );

        // Iterate through a many-many relationship and confirm that extra fields are included
        $newTeam = new DataObjectTest\Team();
        $newTeam->Title = "New team";
        $newTeam->write();
        $newTeamID = $newTeam->ID;

        $newPlayer = new DataObjectTest\Player();
        $newPlayer->FirstName = "Sam";
        $newPlayer->Surname = "Minnee";
        $newPlayer->write();

        // The idea of Sam as a prop is essentially humourous.
        $newTeam->Players()->add($newPlayer, array("Position" => "Prop"));

        // Requery and uncache everything
        $newTeam->flushCache();
        $newTeam = DataObject::get_by_id(DataObjectTest\Team::class, $newTeamID);

        // Check that the Position many_many_extraField is extracted.
        $player = $newTeam->Players()->first();
        $this->assertEquals('Sam', $player->FirstName);
        $this->assertEquals("Prop", $player->Position);

        // Check that ordering a many-many relation by an aggregate column doesn't fail
        $player = $this->objFromFixture(DataObjectTest\Player::class, 'player2');
        $player->Teams()->sort("count(DISTINCT \"DataObjectTest_Team_Players\".\"DataObjectTest_PlayerID\") DESC");
    }

    /**
     * Check that the queries generated for many-many relation queries can have unlimitedRowCount
     * called on them.
     */
    public function testManyManyUnlimitedRowCount()
    {
        $player = $this->objFromFixture(DataObjectTest\Player::class, 'player2');
        // TODO: What's going on here?
        $this->assertEquals(2, $player->Teams()->dataQuery()->query()->unlimitedRowCount());
    }

    /**
     * Tests that singular_name() generates sensible defaults.
     */
    public function testSingularName()
    {
        $assertions = array(
            DataObjectTest\Player::class => 'Player',
            DataObjectTest\Team::class => 'Team',
            DataObjectTest\Fixture::class => 'Fixture',
        );

        foreach ($assertions as $class => $expectedSingularName) {
            $this->assertEquals(
                $expectedSingularName,
                singleton($class)->singular_name(),
                "Assert that the singular_name for '$class' is correct."
            );
        }
    }

    /**
     * Tests that plural_name() generates sensible defaults.
     */
    public function testPluralName()
    {
        $assertions = array(
            DataObjectTest\Player::class => 'Players',
            DataObjectTest\Team::class => 'Teams',
            DataObjectTest\Fixture::class => 'Fixtures',
            DataObjectTest\Play::class => 'Plays',
            DataObjectTest\Bogey::class => 'Bogeys',
            DataObjectTest\Ploy::class => 'Ploys',
        );
        i18n::set_locale('en_NZ');
        foreach ($assertions as $class => $expectedPluralName) {
            $this->assertEquals(
                $expectedPluralName,
                DataObject::singleton($class)->plural_name(),
                "Assert that the plural_name for '$class' is correct."
            );
            $this->assertEquals(
                $expectedPluralName,
                DataObject::singleton($class)->i18n_plural_name(),
                "Assert that the i18n_plural_name for '$class' is correct."
            );
        }
    }

    public function testHasDatabaseField()
    {
        $team = singleton(DataObjectTest\Team::class);
        $subteam = singleton(DataObjectTest\SubTeam::class);

        $this->assertTrue(
            $team->hasDatabaseField('Title'),
            "hasOwnDatabaseField() works with \$db fields"
        );
        $this->assertTrue(
            $team->hasDatabaseField('CaptainID'),
            "hasOwnDatabaseField() works with \$has_one fields"
        );
        $this->assertFalse(
            $team->hasDatabaseField('NonExistentField'),
            "hasOwnDatabaseField() doesn't detect non-existend fields"
        );
        $this->assertTrue(
            $team->hasDatabaseField('ExtendedDatabaseField'),
            "hasOwnDatabaseField() works with extended fields"
        );
        $this->assertFalse(
            $team->hasDatabaseField('SubclassDatabaseField'),
            "hasOwnDatabaseField() doesn't pick up fields in subclasses on parent class"
        );

        $this->assertTrue(
            $subteam->hasDatabaseField('SubclassDatabaseField'),
            "hasOwnDatabaseField() picks up fields in subclasses"
        );
    }

    public function testFieldTypes()
    {
        $obj = new DataObjectTest\Fixture();
        $obj->DateField = '1988-01-02';
        $obj->DatetimeField = '1988-03-04 06:30';
        $obj->write();
        $obj->flushCache();

        $obj = DataObject::get_by_id(DataObjectTest\Fixture::class, $obj->ID);
        $this->assertEquals('1988-01-02', $obj->DateField);
        $this->assertEquals('1988-03-04 06:30:00', $obj->DatetimeField);
    }

    public function testTwoSubclassesWithTheSameFieldNameWork()
    {
        // Create two objects of different subclasses, setting the values of fields that are
        // defined separately in each subclass
        $obj1 = new DataObjectTest\SubTeam();
        $obj1->SubclassDatabaseField = "obj1";
        $obj2 = new DataObjectTest\OtherSubclassWithSameField();
        $obj2->SubclassDatabaseField = "obj2";

        // Write them to the database
        $obj1->write();
        $obj2->write();

        // Check that the values of those fields are properly read from the database
        $values = DataObject::get(
            DataObjectTest\Team::class,
            "\"DataObjectTest_Team\".\"ID\" IN
			($obj1->ID, $obj2->ID)"
        )->column("SubclassDatabaseField");
        $this->assertEquals(array_intersect($values, array('obj1', 'obj2')), $values);
    }

    public function testClassNameSetForNewObjects()
    {
        $d = new DataObjectTest\Player();
        $this->assertEquals(DataObjectTest\Player::class, $d->ClassName);
    }

    public function testHasValue()
    {
        $team = new DataObjectTest\Team();
        $this->assertFalse($team->hasValue('Title', null, false));
        $this->assertFalse($team->hasValue('DatabaseField', null, false));

        $team->Title = 'hasValue';
        $this->assertTrue($team->hasValue('Title', null, false));
        $this->assertFalse($team->hasValue('DatabaseField', null, false));

        $team->Title = '<p></p>';
        $this->assertTrue(
            $team->hasValue('Title', null, false),
            'Test that an empty paragraph is a value for non-HTML fields.'
        );

        $team->DatabaseField = 'hasValue';
        $this->assertTrue($team->hasValue('Title', null, false));
        $this->assertTrue($team->hasValue('DatabaseField', null, false));
    }

    public function testHasMany()
    {
        $company = new DataObjectTest\Company();

        $this->assertEquals(
            array(
                'CurrentStaff' => DataObjectTest\Staff::class,
                'PreviousStaff' => DataObjectTest\Staff::class
            ),
            $company->hasMany(),
            'has_many strips field name data by default.'
        );

        $this->assertEquals(
            DataObjectTest\Staff::class,
            DataObject::getSchema()->hasManyComponent(DataObjectTest\Company::class, 'CurrentStaff'),
            'has_many strips field name data by default on single relationships.'
        );

        $this->assertEquals(
            array(
                'CurrentStaff' => DataObjectTest\Staff::class . '.CurrentCompany',
                'PreviousStaff' => DataObjectTest\Staff::class . '.PreviousCompany'
            ),
            $company->hasMany(false),
            'has_many returns field name data when $classOnly is false.'
        );

        $this->assertEquals(
            DataObjectTest\Staff::class . '.CurrentCompany',
            DataObject::getSchema()->hasManyComponent(DataObjectTest\Company::class, 'CurrentStaff', false),
            'has_many returns field name data on single records when $classOnly is false.'
        );
    }

    public function testGetRemoteJoinField()
    {
        $schema = DataObject::getSchema();

        // Company schema
        $staffJoinField = $schema->getRemoteJoinField(
            DataObjectTest\Company::class,
            'CurrentStaff',
            'has_many',
            $polymorphic
        );
        $this->assertEquals('CurrentCompanyID', $staffJoinField);
        $this->assertFalse($polymorphic, 'DataObjectTest_Company->CurrentStaff is not polymorphic');
        $previousStaffJoinField = $schema->getRemoteJoinField(
            DataObjectTest\Company::class,
            'PreviousStaff',
            'has_many',
            $polymorphic
        );
        $this->assertEquals('PreviousCompanyID', $previousStaffJoinField);
        $this->assertFalse($polymorphic, 'DataObjectTest_Company->PreviousStaff is not polymorphic');

        // CEO Schema
        $this->assertEquals(
            'CEOID',
            $schema->getRemoteJoinField(
                DataObjectTest\CEO::class,
                'Company',
                'belongs_to',
                $polymorphic
            )
        );
        $this->assertFalse($polymorphic, 'DataObjectTest_CEO->Company is not polymorphic');
        $this->assertEquals(
            'PreviousCEOID',
            $schema->getRemoteJoinField(
                DataObjectTest\CEO::class,
                'PreviousCompany',
                'belongs_to',
                $polymorphic
            )
        );
        $this->assertFalse($polymorphic, 'DataObjectTest_CEO->PreviousCompany is not polymorphic');

        // Team schema
        $this->assertEquals(
            'Favourite',
            $schema->getRemoteJoinField(
                DataObjectTest\Team::class,
                'Fans',
                'has_many',
                $polymorphic
            )
        );
        $this->assertTrue($polymorphic, 'DataObjectTest_Team->Fans is polymorphic');
        $this->assertEquals(
            'TeamID',
            $schema->getRemoteJoinField(
                DataObjectTest\Team::class,
                'Comments',
                'has_many',
                $polymorphic
            )
        );
        $this->assertFalse($polymorphic, 'DataObjectTest_Team->Comments is not polymorphic');
    }

    public function testBelongsTo()
    {
        $company = new DataObjectTest\Company();
        $ceo = new DataObjectTest\CEO();

        $company->Name = 'New Company';
        $company->write();
        $ceo->write();

        // Test belongs_to assignment
        $company->CEOID = $ceo->ID;
        $company->write();

        $this->assertEquals($company->ID, $ceo->Company()->ID, 'belongs_to returns the right results.');

        // Test belongs_to can be infered via getNonReciprocalComponent
        // Note: Will be returned as has_many since the belongs_to is ignored.
        $this->assertListEquals(
            [['Name' => 'New Company']],
            $ceo->inferReciprocalComponent(DataObjectTest\Company::class, 'CEO')
        );

        // Test has_one to a belongs_to can be infered via getNonReciprocalComponent
        $this->assertEquals(
            $ceo->ID,
            $company->inferReciprocalComponent(DataObjectTest\CEO::class, 'Company')->ID
        );

        // Test automatic creation of class where no assigment exists
        $ceo = new DataObjectTest\CEO();
        $ceo->write();

        $this->assertTrue(
            $ceo->Company() instanceof DataObjectTest\Company,
            'DataObjects across belongs_to relations are automatically created.'
        );
        $this->assertEquals($ceo->ID, $ceo->Company()->CEOID, 'Remote IDs are automatically set.');

        // Write object with components
        $ceo->Name = 'Edward Scissorhands';
        $ceo->write(false, false, false, true);
        $this->assertTrue($ceo->Company()->isInDB(), 'write() writes belongs_to components to the database.');

        $newCEO = DataObject::get_by_id(DataObjectTest\CEO::class, $ceo->ID);
        $this->assertEquals(
            $ceo->Company()->ID,
            $newCEO->Company()->ID,
            'belongs_to can be retrieved from the database.'
        );
    }

    public function testBelongsToPolymorphic()
    {
        $company = new DataObjectTest\Company();
        $ceo = new DataObjectTest\CEO();

        $company->write();
        $ceo->write();

        // Test belongs_to assignment
        $company->OwnerID = $ceo->ID;
        $company->OwnerClass = DataObjectTest\CEO::class;
        $company->write();

        $this->assertEquals($company->ID, $ceo->CompanyOwned()->ID, 'belongs_to returns the right results.');
        $this->assertInstanceOf(
            DataObjectTest\Company::class,
            $ceo->CompanyOwned(),
            'belongs_to returns the right results.'
        );

        // Test automatic creation of class where no assigment exists
        $ceo = new DataObjectTest\CEO();
        $ceo->write();

        $this->assertTrue(
            $ceo->CompanyOwned() instanceof DataObjectTest\Company,
            'DataObjects across polymorphic belongs_to relations are automatically created.'
        );
        $this->assertEquals($ceo->ID, $ceo->CompanyOwned()->OwnerID, 'Remote IDs are automatically set.');
        $this->assertInstanceOf($ceo->CompanyOwned()->OwnerClass, $ceo, 'Remote class is automatically  set');

        // Write object with components
        $ceo->write(false, false, false, true);
        $this->assertTrue($ceo->CompanyOwned()->isInDB(), 'write() writes belongs_to components to the database.');

        $newCEO = DataObject::get_by_id(DataObjectTest\CEO::class, $ceo->ID);
        $this->assertEquals(
            $ceo->CompanyOwned()->ID,
            $newCEO->CompanyOwned()->ID,
            'polymorphic belongs_to can be retrieved from the database.'
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidate()
    {
        $do = new DataObjectTest\Fixture();
        $do->write();

        $do->delete();

        $do->delete(); // Prohibit invalid object manipulation
        $do->write();
        $do->duplicate();
    }

    public function testToMap()
    {
        $obj = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');

        $map = $obj->toMap();

        $this->assertArrayHasKey('ID', $map, 'Contains base fields');
        $this->assertArrayHasKey('Title', $map, 'Contains fields from parent class');
        $this->assertArrayHasKey('SubclassDatabaseField', $map, 'Contains fields from concrete class');

        $this->assertEquals(
            $obj->ID,
            $map['ID'],
            'Contains values from base fields'
        );
        $this->assertEquals(
            $obj->Title,
            $map['Title'],
            'Contains values from parent class fields'
        );
        $this->assertEquals(
            $obj->SubclassDatabaseField,
            $map['SubclassDatabaseField'],
            'Contains values from concrete class fields'
        );

        $newObj = new DataObjectTest\SubTeam();
        $this->assertArrayHasKey('Title', $map, 'Contains null fields');
    }

    public function testIsEmpty()
    {
        $objEmpty = new DataObjectTest\Team();
        $this->assertTrue($objEmpty->isEmpty(), 'New instance without populated defaults is empty');

        $objEmpty->Title = '0'; //
        $this->assertFalse($objEmpty->isEmpty(), 'Zero value in attribute considered non-empty');
    }

    public function testRelField()
    {
        $captain = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');
        // Test traversal of a single has_one
        $this->assertEquals("Team 1", $captain->relField('FavouriteTeam.Title'));
        // Test direct field access
        $this->assertEquals("Captain", $captain->relField('FirstName'));

        $player = $this->objFromFixture(DataObjectTest\Player::class, 'player2');
        // Test that we can traverse more than once, and that arbitrary methods are okay
        $this->assertEquals("Team 1", $player->relField('Teams.First.Title'));

        $newPlayer = new DataObjectTest\Player();
        $this->assertNull($newPlayer->relField('Teams.First.Title'));

        // Test that relField works on db field manipulations
        $comment = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3');
        $this->assertEquals("PHIL IS A UNIQUE GUY, AND COMMENTS ON TEAM2", $comment->relField('Comment.UpperCase'));
    }

    public function testRelObject()
    {
        $captain = $this->objFromFixture(DataObjectTest\Player::class, 'captain1');

        // Test traversal of a single has_one
        $this->assertInstanceOf(DBVarchar::class, $captain->relObject('FavouriteTeam.Title'));
        $this->assertEquals("Team 1", $captain->relObject('FavouriteTeam.Title')->getValue());

        // Test direct field access
        $this->assertInstanceOf(DBBoolean::class, $captain->relObject('IsRetired'));
        $this->assertEquals(1, $captain->relObject('IsRetired')->getValue());

        $player = $this->objFromFixture(DataObjectTest\Player::class, 'player2');
        // Test that we can traverse more than once, and that arbitrary methods are okay
        $this->assertInstanceOf(DBVarchar::class, $player->relObject('Teams.First.Title'));
        $this->assertEquals("Team 1", $player->relObject('Teams.First.Title')->getValue());
    }

    public function testLateStaticBindingStyle()
    {
        // Confirm that DataObjectTest_Player::get() operates as excepted
        $this->assertEquals(4, DataObjectTest\Player::get()->count());
        $this->assertInstanceOf(DataObjectTest\Player::class, DataObjectTest\Player::get()->first());

        // You can't pass arguments to LSB syntax - use the DataList methods instead.
        $this->expectException(InvalidArgumentException::class);

        DataObjectTest\Player::get(null, "\"ID\" = 1");
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBrokenLateStaticBindingStyle()
    {
        // If you call DataObject::get() you have to pass a first argument
        DataObject::get();
    }

    public function testBigIntField()
    {
        $staff = new DataObjectTest\Staff();
        $staff->Salary = PHP_INT_MAX;
        $staff->write();
        $this->assertEquals(PHP_INT_MAX, DataObjectTest\Staff::get()->byID($staff->ID)->Salary);
    }

    public function testGetOneMissingValueReturnsNull()
    {

        // Test that missing values return null
        $this->assertEquals(null, DataObject::get_one(
            DataObjectTest\TeamComment::class,
            ['"DataObjectTest_TeamComment"."Name"' => 'does not exists']
        ));
    }
}
