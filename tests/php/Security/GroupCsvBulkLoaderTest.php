<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\GroupCsvBulkLoader;
use SilverStripe\Security\Group;
use SilverStripe\Dev\SapphireTest;

class GroupCsvBulkLoaderTest extends SapphireTest
{
    protected static $fixture_file = 'GroupCsvBulkLoaderTest.yml';

    public function testNewImport()
    {
        $loader = new GroupCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/GroupCsvBulkLoaderTest/GroupCsvBulkLoaderTest.csv');
        $created = $results->Created()->toArray();
        $this->assertEquals(count($created ?? []), 2);
        $this->assertEquals($created[0]->Code, 'newgroup1');
        $this->assertEquals($created[0]->ParentID, 0);
        $this->assertEquals($created[1]->Code, 'newchildgroup1');
        $this->assertEquals($created[1]->ParentID, $created[0]->ID);
    }

    public function testOverwriteExistingImport()
    {
        // This group will be overwritten.
        $existinggroup = new Group();
        $existinggroup->Title = 'Old Group Title';
        $existinggroup->Code = 'newgroup1';
        $existinggroup->write();

        $loader = new GroupCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/GroupCsvBulkLoaderTest/GroupCsvBulkLoaderTest.csv');

        $created = $results->Created()->toArray();
        $this->assertEquals(1, count($created));
        $this->assertEquals('newchildgroup1', $created[0]->Code);
        $this->assertEquals('New Child Group 1', $created[0]->Title);

        // This overrides the group because the code matches, which takes precedence over the ID.
        $updated = $results->Updated()->toArray();
        $this->assertEquals(1, count($updated));
        $this->assertEquals('newgroup1', $updated[0]->Code);
        $this->assertEquals('New Group 1', $updated[0]->Title);
    }

    public function testImportPermissions()
    {
        $loader = new GroupCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/GroupCsvBulkLoaderTest/GroupCsvBulkLoaderTest_withExisting.csv');

        $created = $results->Created()->toArray();
        $this->assertEquals(1, count($created));
        $this->assertEquals('newgroup1', $created[0]->Code);
        $this->assertEquals(['CODE1'], $created[0]->Permissions()->column('Code'));

        $updated = $results->Updated()->toArray();
        $this->assertEquals(1, count($updated));
        $this->assertEquals('existinggroup', $updated[0]->Code);
        $actual = $updated[0]->Permissions()->column('Code');
        $expected = ['CODE1', 'CODE2'];
        sort($actual);
        sort($expected);
        $this->assertEquals($expected, $actual);
    }
}
