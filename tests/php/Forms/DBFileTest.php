<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;

class DBFileTest extends SapphireTest
{

    protected $extraDataObjects = array(
        DBFileTest\TestObject::class,
        DBFileTest\Subclass::class,
    );

    protected $usesDatabase = true;

    public function setUp()
    {
        parent::setUp();

        // Set backend
        TestAssetStore::activate('DBFileTest');
        Director::config()->update('alternate_base_url', '/mysite/');
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    /**
     * Test that images in a DBFile are rendered properly
     */
    public function testRender()
    {
        $obj = new DBFileTest\TestObject();

        // Test image tag
        $fish = realpath(__DIR__ .'/../ORM/ImageTest/test-image-high-quality.jpg');
        $this->assertFileExists($fish);
        $obj->MyFile->setFromLocalFile($fish, 'awesome-fish.jpg');
        $this->assertEquals(
            '<img src="/mysite/assets/DBFileTest/a870de278b/awesome-fish.jpg" alt="awesome-fish.jpg" />',
            trim($obj->MyFile->forTemplate())
        );

        // Test download tag
        $obj->MyFile->setFromString('puppies', 'subdir/puppy-document.txt');
        $this->assertEquals(
            '<a href="/mysite/assets/DBFileTest/subdir/2a17a9cb4b/puppy-document.txt" title="puppy-document.txt" download="puppy-document.txt"/>',
            trim($obj->MyFile->forTemplate())
        );
    }

    public function testValidation()
    {
        $obj = new DBFileTest\ImageOnly();

        // Test from image
        $fish = realpath(__DIR__ .'/../ORM/ImageTest/test-image-high-quality.jpg');
        $this->assertFileExists($fish);
        $obj->MyFile->setFromLocalFile($fish, 'awesome-fish.jpg');

        // This should fail
        $this->setExpectedException('SilverStripe\\ORM\\ValidationException');
        $obj->MyFile->setFromString('puppies', 'subdir/puppy-document.txt');
    }

    public function testPermission()
    {
        $obj = new DBFileTest\TestObject();

        // Test from image
        $fish = realpath(__DIR__ .'/../ORM/ImageTest/test-image-high-quality.jpg');
        $this->assertFileExists($fish);
        $obj->MyFile->setFromLocalFile(
            $fish,
            'private/awesome-fish.jpg',
            null,
            null,
            array(
            'visibility' => AssetStore::VISIBILITY_PROTECTED
            )
        );

        // Test various file permissions work on DBFile
        $this->assertFalse($obj->MyFile->canViewFile());
        $obj->MyFile->getURL();
        $this->assertTrue($obj->MyFile->canViewFile());
        $obj->MyFile->revokeFile();
        $this->assertFalse($obj->MyFile->canViewFile());
        $obj->MyFile->getURL(false);
        $this->assertFalse($obj->MyFile->canViewFile());
        $obj->MyFile->grantFile();
        $this->assertTrue($obj->MyFile->canViewFile());
    }
}
