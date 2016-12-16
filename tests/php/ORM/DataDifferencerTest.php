<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\Versioning\DataDifferencer;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;

class DataDifferencerTest extends SapphireTest
{

    protected static $fixture_file = 'DataDifferencerTest.yml';

    protected $extraDataObjects = array(
        DataDifferencerTest\TestObject::class,
        DataDifferencerTest\HasOneRelationObject::class
    );

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);

        // Set backend root to /DataDifferencerTest
        TestAssetStore::activate('DataDifferencerTest');

        // Create a test files for each of the fixture references
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $fromPath = __DIR__ . '/ImageTest/' . $file->Name;
            $destPath = TestAssetStore::getLocalPath($file); // Only correct for test asset store
            Filesystem::makeFolder(dirname($destPath));
            copy($fromPath, $destPath);
        }
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testArrayValues()
    {
        $obj1 = $this->objFromFixture(DataDifferencerTest\TestObject::class, 'obj1');
        $beforeVersion = $obj1->Version;
        // create a new version
        $obj1->Choices = 'a';
        $obj1->write();
        $afterVersion = $obj1->Version;
        $obj1v1 = Versioned::get_version(DataDifferencerTest\TestObject::class, $obj1->ID, $beforeVersion);
        $obj1v2 = Versioned::get_version(DataDifferencerTest\TestObject::class, $obj1->ID, $afterVersion);
        $differ = new DataDifferencer($obj1v1, $obj1v2);
        $obj1Diff = $differ->diffedData();
        // TODO Using getter would split up field again, bug only caused by simulating
        // an array-based value in the first place.
        $this->assertContains('<ins>a</ins><del>a,b</del>', str_replace(' ', '', $obj1Diff->getField('Choices')));
    }

    public function testHasOnes()
    {
        /**
 * @var DataDifferencerTest\TestObject $obj1
*/
        $obj1 = $this->objFromFixture(DataDifferencerTest\TestObject::class, 'obj1');
        $image1 = $this->objFromFixture(Image::class, 'image1');
        $image2 = $this->objFromFixture(Image::class, 'image2');
        $relobj2 = $this->objFromFixture(DataDifferencerTest\HasOneRelationObject::class, 'relobj2');

        // create a new version
        $beforeVersion = $obj1->Version;
        $obj1->ImageID = $image2->ID;
        $obj1->HasOneRelationID = $relobj2->ID;
        $obj1->write();
        $afterVersion = $obj1->Version;
        $this->assertNotEquals($beforeVersion, $afterVersion);
        /**
 * @var DataDifferencerTest\TestObject $obj1v1
*/
        $obj1v1 = Versioned::get_version(DataDifferencerTest\TestObject::class, $obj1->ID, $beforeVersion);
        /**
 * @var DataDifferencerTest\TestObject $obj1v2
*/
        $obj1v2 = Versioned::get_version(DataDifferencerTest\TestObject::class, $obj1->ID, $afterVersion);
        $differ = new DataDifferencer($obj1v1, $obj1v2);
        $obj1Diff = $differ->diffedData();

        /**
 * @skipUpgrade
*/
        $this->assertContains($image1->Name, $obj1Diff->getField('Image'));
        /**
 * @skipUpgrade
*/
        $this->assertContains($image2->Name, $obj1Diff->getField('Image'));
        $this->assertContains(
            '<ins>obj2</ins><del>obj1</del>',
            str_replace(' ', '', $obj1Diff->getField('HasOneRelationID'))
        );
    }
}
