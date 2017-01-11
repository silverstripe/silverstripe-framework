<?php

namespace SilverStripe\Assets\Tests;

use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;

/**
 * @author Ingo Schommer (ingo at silverstripe dot com)
 */
class FolderTest extends SapphireTest
{

    protected static $fixture_file = 'FileTest.yml';

    public function setUp()
    {
        parent::setUp();

        $this->logInWithPermission('ADMIN');
        Versioned::set_stage(Versioned::DRAFT);

        // Set backend root to /FolderTest
        TestAssetStore::activate('FolderTest');

        // Set the File Name Filter replacements so files have the expected names
        Config::inst()->update(
            'SilverStripe\\Assets\\FileNameFilter',
            'default_replacements',
            array(
            '/\s/' => '-', // remove whitespace
            '/_/' => '-', // underscores to dashes
            '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
            '/[\-]{2,}/' => '-', // remove duplicate dashes
            '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
            )
        );

        // Create a test folders for each of the fixture references
        foreach (Folder::get() as $folder) {
            $path = TestAssetStore::getLocalPath($folder);
            Filesystem::makeFolder($path);
        }

        // Create a test files for each of the fixture references
        $files = File::get()->exclude('ClassName', Folder::class);
        foreach ($files as $file) {
            $path = TestAssetStore::getLocalPath($file);
            Filesystem::makeFolder(dirname($path));
            $fh = fopen($path, "w+");
            fwrite($fh, str_repeat('x', 1000000));
            fclose($fh);
        }
    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testCreateFromNameAndParentIDSetsFilename()
    {
        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $newFolder = new Folder();
        $newFolder->Name = 'CreateFromNameAndParentID';
        $newFolder->ParentID = $folder1->ID;
        $newFolder->write();

        $this->assertEquals($folder1->Filename . 'CreateFromNameAndParentID/', $newFolder->Filename);
    }

    public function testRenamesDuplicateFolders()
    {
        $original = new Folder();
        $original->update([
            'Name' => 'folder1',
            'ParentID' => 0
        ]);
        $original->write();

        $duplicate = new Folder();
        $duplicate->update([
            'Name' => 'folder1',
            'ParentID' => 0
        ]);
        $duplicate->write();

        $original = Folder::get()->byID($original->ID);

        $this->assertEquals($original->Name, 'folder1');
        $this->assertEquals($original->Title, 'folder1');
        $this->assertEquals($duplicate->Name, 'folder1-v2');
        $this->assertEquals($duplicate->Title, 'folder1-v2');
    }

    public function testAllChildrenIncludesFolders()
    {
        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');
        $file1 = $this->objFromFixture(File::class, 'file1-folder1');

        $children = $folder1->allChildren();
        $this->assertEquals(2, $children->Count());
        $this->assertContains($subfolder1->ID, $children->column('ID'));
        $this->assertContains($file1->ID, $children->column('ID'));
    }

    public function testFindOrMake()
    {
        $path = 'parent/testFindOrMake/';
        $folder = Folder::find_or_make($path);
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/' . $path,
            TestAssetStore::getLocalPath($folder),
            'Nested path information is correctly saved to database (with trailing slash)'
        );

        // Folder does not exist until it contains files
        $this->assertFileNotExists(
            TestAssetStore::getLocalPath($folder),
            'Empty folder does not have a filesystem record automatically'
        );

        $parentFolder = DataObject::get_one(
            Folder::class,
            array(
            '"File"."Name"' => 'parent'
            )
        );
        $this->assertNotNull($parentFolder);
        $this->assertEquals($parentFolder->ID, $folder->ParentID);

        $path = 'parent/testFindOrMake'; // no trailing slash
        $folder = Folder::find_or_make($path);
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/' . $path . '/', // Slash is automatically added here
            TestAssetStore::getLocalPath($folder),
            'Path information is correctly saved to database (without trailing slash)'
        );

        $path = 'assets/'; // relative to "assets/" folder, should produce "assets/assets/"
        $folder = Folder::find_or_make($path);
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/' . $path,
            TestAssetStore::getLocalPath($folder),
            'A folder named "assets/" within "assets/" is allowed'
        );
    }

    /**
     * Tests for the bug #5994 - Moving folder after executing Folder::findOrMake will not set the Filenames properly
     */
    public function testFindOrMakeFolderThenMove()
    {
        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        Folder::find_or_make($folder1->Filename);
        $folder2 = $this->objFromFixture(Folder::class, 'folder2');

        // Publish file1
        /**
 * @var File $file1
*/
        $file1 = DataObject::get_by_id(File::class, $this->idFromFixture(File::class, 'file1-folder1'), false);
        $file1->publishRecursive();

        // set ParentID. This should cause updateFilesystem to be called on all children
        $folder1->ParentID = $folder2->ID;
        $folder1->write();

        // Check if the file in the folder moved along
        /**
 * @var File $file1Draft
*/
        $file1Draft = Versioned::get_by_stage(File::class, Versioned::DRAFT)->byID($file1->ID);
        $this->assertFileExists(TestAssetStore::getLocalPath($file1Draft));

        $this->assertEquals(
            'FileTest-folder2/FileTest-folder1/File1.txt',
            $file1Draft->Filename,
            'The file DataObject has updated path'
        );

        // File should be located in new folder
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/.protected/FileTest-folder2/FileTest-folder1/55b443b601/File1.txt',
            TestAssetStore::getLocalPath($file1Draft)
        );

        // Published (live) version remains in the old location
        /**
 * @var File $file1Live
*/
        $file1Live = Versioned::get_by_stage(File::class, Versioned::LIVE)->byID($file1->ID);
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/FileTest-folder1/55b443b601/File1.txt',
            TestAssetStore::getLocalPath($file1Live)
        );

        // Publishing the draft to live should move the new file to the public store
        $file1Draft->publishRecursive();
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/FileTest-folder2/FileTest-folder1/55b443b601/File1.txt',
            TestAssetStore::getLocalPath($file1Draft)
        );
    }

    /**
     * Tests for the bug #5994 - if you don't execute get_by_id prior to the rename or move, it will fail.
     */
    public function testRenameFolderAndCheckTheFile()
    {
        // ID is prefixed in case Folder is subclassed by project/other module.
        $folder1 = DataObject::get_one(
            Folder::class,
            array(
            '"File"."ID"' => $this->idFromFixture(Folder::class, 'folder1')
            )
        );

        $folder1->Name = 'FileTest-folder1-changed';
        $folder1->write();

        // Check if the file in the folder moved along
        $file1 = DataObject::get_by_id(File::class, $this->idFromFixture(File::class, 'file1-folder1'), false);
        $this->assertFileExists(
            TestAssetStore::getLocalPath($file1)
        );
        $this->assertEquals(
            $file1->Filename,
            'FileTest-folder1-changed/File1.txt',
            'The file DataObject path uses renamed folder'
        );

        // File should be located in new folder
        $this->assertEquals(
            ASSETS_PATH . '/FolderTest/.protected/FileTest-folder1-changed/55b443b601/File1.txt',
            TestAssetStore::getLocalPath($file1)
        );
    }

    /**
     * URL and Link are undefined for folder dataobjects
     */
    public function testLinkAndRelativeLink()
    {
        $folder = $this->objFromFixture(Folder::class, 'folder1');
        $this->assertEmpty($folder->getURL());
        $this->assertEmpty($folder->Link());
    }

    public function testIllegalFilenames()
    {

        // Test that generating a filename with invalid characters generates a correctly named folder.
        $folder = Folder::find_or_make('/FolderTest/EN_US Lang');
        $this->assertEquals('FolderTest/EN-US-Lang/', $folder->getFilename());

        // Test repeatitions of folder
        $folder2 = Folder::find_or_make('/FolderTest/EN_US Lang');
        $this->assertEquals($folder->ID, $folder2->ID);

        $folder3 = Folder::find_or_make('/FolderTest/EN--US_L!ang');
        $this->assertEquals($folder->ID, $folder3->ID);

        $folder4 = Folder::find_or_make('/FolderTest/EN-US-Lang');
        $this->assertEquals($folder->ID, $folder4->ID);
    }

    public function testTitleTiedToName()
    {
        $newFolder = new Folder();

        $newFolder->Name = 'TestNameCopiedToTitle';
        $this->assertEquals($newFolder->Name, $newFolder->Title);
        $this->assertEquals($newFolder->Title, 'TestNameCopiedToTitle');

        $newFolder->Title = 'TestTitleCopiedToName';
        $this->assertEquals($newFolder->Name, $newFolder->Title);
        $this->assertEquals($newFolder->Title, 'TestTitleCopiedToName');

        $newFolder->Name = 'TestNameWithIllegalCharactersCopiedToTitle <!BANG!>';
        $this->assertEquals($newFolder->Name, $newFolder->Title);
        $this->assertEquals($newFolder->Title, 'TestNameWithIllegalCharactersCopiedToTitle <!BANG!>');

        $newFolder->Title = 'TestTitleWithIllegalCharactersCopiedToName <!BANG!>';
        $this->assertEquals($newFolder->Name, $newFolder->Title);
        $this->assertEquals($newFolder->Title, 'TestTitleWithIllegalCharactersCopiedToName <!BANG!>');
    }
}
