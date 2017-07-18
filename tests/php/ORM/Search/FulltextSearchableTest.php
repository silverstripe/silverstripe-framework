<?php

namespace SilverStripe\ORM\Tests\Search;

use SilverStripe\Assets\File;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Search\FulltextSearchable;

class FulltextSearchableTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();

        FulltextSearchable::enable(File::class);
    }

    /**
     * FulltextSearchable::enable() leaves behind remains that don't get cleaned up
     * properly at the end of the test. This becomes apparent when a later test tries to
     * ALTER TABLE File and add fulltext indexes with the InnoDB table type.
     */
    protected function tearDown()
    {
        parent::tearDown();

        File::remove_extension(FulltextSearchable::class);
        Config::modify()->merge(
            File::class,
            'create_table_options',
            array(
            MySQLSchemaManager::ID => 'ENGINE=InnoDB')
        );
    }

    public function testEnable()
    {
        $this->assertTrue(File::has_extension(FulltextSearchable::class));
    }

    public function testEnableWithCustomClasses()
    {
        FulltextSearchable::enable(array(File::class));
        $this->assertTrue(File::has_extension(FulltextSearchable::class));

        File::remove_extension(FulltextSearchable::class);
        $this->assertFalse(File::has_extension(FulltextSearchable::class));
    }

    public function testIndexesAdded()
    {
        $indexes = DataObject::getSchema()->databaseIndexes(File::class);
        $this->assertArrayHasKey('SearchFields', $indexes);
        $this->assertCount(2, $indexes['SearchFields']['columns']);
        $this->assertContains('Name', $indexes['SearchFields']['columns']);
        $this->assertContains('Title', $indexes['SearchFields']['columns']);
    }
}
