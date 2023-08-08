<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\HasManyListTest\Company;
use SilverStripe\ORM\Tests\HasManyListTest\CompanyCar;
use SilverStripe\ORM\Tests\HasManyListTest\Employee;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;

class HasManyListTest extends SapphireTest
{

    protected static $fixture_file = [
        'DataObjectTest.yml', // Borrow the model from DataObjectTest
        'HasManyListTest.yml',
    ];

    public static $extra_data_objects = [
        Company::class,
        Employee::class,
        CompanyCar::class,
    ];

    public static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects,
            static::$extra_data_objects
        );
    }

    public function testRelationshipEmptyOnNewRecords()
    {
        // Relies on the fact that (unrelated) comments exist in the fixture file already
        $newTeam = new Team(); // has_many Comments
        $this->assertEquals([], $newTeam->Comments()->column('ID'));
    }

    /**
     * Test that related objects can be removed from a relation
     */
    public function testRemoveRelation()
    {

        // Check that expected teams exist
        $list = Team::get();
        $this->assertEquals(
            ['Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'],
            $list->sort('Title')->column('Title')
        );

        // Test that each team has the correct fans
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $team2 = $this->objFromFixture(DataObjectTest\Team::class, 'team2');
        $this->assertEquals(['Bob', 'Joe'], $team1->Comments()->sort('Name')->column('Name'));
        $this->assertEquals(['Phil'], $team2->Comments()->sort('Name')->column('Name'));

        // Test that removing comments from unrelated team has no effect
        $team1comment = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1');
        $team2comment = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3');
        $team1->Comments()->remove($team2comment);
        $team2->Comments()->remove($team1comment);
        $this->assertEquals(['Bob', 'Joe'], $team1->Comments()->sort('Name')->column('Name'));
        $this->assertEquals(['Phil'], $team2->Comments()->sort('Name')->column('Name'));
        $this->assertEquals($team1->ID, $team1comment->TeamID);
        $this->assertEquals($team2->ID, $team2comment->TeamID);

        // Test that removing items from the related team resets the has_one relations on the fan
        $team1comment = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1');
        $team2comment = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3');
        $team1->Comments()->remove($team1comment);
        $team2->Comments()->remove($team2comment);
        $this->assertEquals(['Bob'], $team1->Comments()->sort('Name')->column('Name'));
        $this->assertEquals([], $team2->Comments()->sort('Name')->column('Name'));
        $this->assertEmpty($team1comment->TeamID);
        $this->assertEmpty($team2comment->TeamID);
    }

    public function testDefaultSortIsUsedOnList()
    {
        /** @var Company $company */
        $company = $this->objFromFixture(Company::class, 'silverstripe');

        $this->assertListEquals([
            ['Make' => 'Ferrari'],
            ['Make' => 'Jaguar'],
            ['Make' => 'Lamborghini'],
        ], $company->CompanyCars());
    }

    public function testCanBeSortedDescending()
    {
        /** @var Company $company */
        $company = $this->objFromFixture(Company::class, 'silverstripe');

        $this->assertListEquals([
            ['Make' => 'Lamborghini'],
            ['Make' => 'Jaguar'],
            ['Make' => 'Ferrari'],
        ], $company->CompanyCars()->sort('"Make" DESC'));
    }

    public function testSortByModel()
    {
        /** @var Company $company */
        $company = $this->objFromFixture(Company::class, 'silverstripe');

        $this->assertListEquals([
            ['Model' => 'Countach'],
            ['Model' => 'E Type'],
            ['Model' => 'F40'],
        ], $company->CompanyCars()->sort('"Model" ASC'));
    }

    public function testCallbackOnSetById()
    {
        $addedIds = [];
        $removedIds = [];

        $base = $this->objFromFixture(Company::class, 'silverstripe');
        $relation = $base->Employees();
        $remove = $relation->First();
        $add = new Employee();
        $add->write();

        $relation->addCallbacks()->add(function ($list, $item) use (&$addedIds) {
            $addedIds[] = $item;
        });

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->setByIDList(array_merge(
            $base->Employees()->exclude('ID', $remove->ID)->column('ID'),
            [$add->ID]
        ));
        $this->assertEquals([$remove->ID], $removedIds);
    }

    public function testAddCallback()
    {
        $added = [];

        $base = $this->objFromFixture(Company::class, 'silverstripe');
        $relation = $base->Employees();
        $add = new Employee();
        $add->write();

        $relation->addCallbacks()->add(function ($list, $item) use (&$added) {
            $added[] = $item;
        });

        $relation->add($add);
        $this->assertEquals([$add], $added);
    }

    public function testRemoveCallbackOnRemove()
    {
        $removedIds = [];

        $base = $this->objFromFixture(Company::class, 'silverstripe');
        $relation = $base->Employees();
        $remove = $relation->First();

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->remove($remove);
        $this->assertEquals([$remove->ID], $removedIds);
    }

    public function testRemoveCallbackOnRemoveById()
    {
        $removedIds = [];

        $base = $this->objFromFixture(Company::class, 'silverstripe');
        $relation = $base->Employees();
        $remove = $relation->First();

        $relation->removeCallbacks()->add(function ($list, $ids) use (&$removedIds) {
            $removedIds = $ids;
        });

        $relation->removeByID($remove->ID);
        $this->assertEquals([$remove->ID], $removedIds);
    }

    /**
     * @dataProvider provideForForeignIDPlaceholders
     */
    public function testForForeignIDPlaceholders(bool $config, bool $useInt, bool $expected): void
    {
        Config::modify()->set(DataList::class, 'use_placeholders_for_integer_ids', $config);
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $team2 = $this->objFromFixture(Team::class, 'team2');
        $comments1 = $team1->Comments();
        $comments2 = $team2->Comments();
        $ids = $useInt ? [$team1->ID, $team2->ID] : ['Lorem', 'Ipsum'];
        $newCommentsList = $comments1->forForeignID($ids);
        $sql = $newCommentsList->dataQuery()->sql();
        preg_match('#ID" IN \(([^\)]+)\)\)#', $sql, $matches);
        $usesPlaceholders = $matches[1] === '?, ?';
        $this->assertSame($expected, $usesPlaceholders);
        $expectedIDs = $useInt
            ? array_values(array_merge($comments1->column('ID'), $comments2->column('ID')))
            : [];
        $this->assertSame($expectedIDs, $newCommentsList->column('ID'));
    }

    public function provideForForeignIDPlaceholders(): array
    {
        return [
            'config false' => [
                'config' => false,
                'useInt' => true,
                'expected' => false,
            ],
            'config false non-int' => [
                'config' => false,
                'useInt' => false,
                'expected' => true,
            ],
            'config true' => [
                'config' => true,
                'useInt' => true,
                'expected' => true,
            ],
            'config true non-int' => [
                'config' => true,
                'useInt' => false,
                'expected' => true,
            ],
        ];
    }
}
