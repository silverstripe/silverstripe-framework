<?php

namespace SilverStripe\Assets\Tests;

use SilverStripe\Assets\AssetManipulationList;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests set manipulations of groups of assets of differing visibilities
 */
class AssetManipulationListTest extends SapphireTest
{

    public function testVisibility()
    {
        $set = new AssetManipulationList();
        $file1 = ['Filename' => 'Test1.jpg', 'Hash' => '975677589962604d9e16b700cf84734f9dda2817'];
        $file2 = ['Filename' => 'Test2.jpg', 'Hash' => '22af86a45ea56287437a12cf83aded5c077a5db5'];
        $file3 = ['Filename' => 'DupeHash1.jpg', 'Hash' => 'f167433dd318e738281b845a07d7be2053b8c997'];
        $file4 = ['Filename' => 'DupeName.jpg', 'Hash' => 'afde6577a034323959b7915f41ac8d1f53bc597f'];
        $file5 = ['Filename' => 'DupeName.jpg', 'Hash' => '1e94b066e5aa16907d0e5e32556c7a2a0b692eb9'];
        $file6 = ['Filename' => 'DupeHash2.jpg', 'Hash' => 'f167433dd318e738281b845a07d7be2053b8c997'];

        // Non-overlapping assets remain in assigned sets
        $this->assertTrue($set->addDeletedAsset($file1));
        $this->assertTrue($set->addDeletedAsset($file2));
        $this->assertTrue($set->addProtectedAsset($file3));
        $this->assertTrue($set->addProtectedAsset($file4));
        $this->assertTrue($set->addPublicAsset($file5));
        $this->assertTrue($set->addPublicAsset($file6));

        // Check initial state of list
        $this->assertEquals(6, $this->countItems($set));
        $this->assertContains($file1, $set->getDeletedAssets());
        $this->assertContains($file2, $set->getDeletedAssets());
        $this->assertContains($file3, $set->getProtectedAssets());
        $this->assertContains($file4, $set->getProtectedAssets());
        $this->assertContains($file5, $set->getPublicAssets());
        $this->assertContains($file6, $set->getPublicAssets());

        // Public or Protected assets will not be deleted
        $this->assertFalse($set->addDeletedAsset($file3));
        $this->assertFalse($set->addDeletedAsset($file4));
        $this->assertFalse($set->addDeletedAsset($file5));
        $this->assertFalse($set->addDeletedAsset($file6));
        $this->assertEquals(6, $this->countItems($set));
        $this->assertNotContains($file3, $set->getDeletedAssets());
        $this->assertNotContains($file4, $set->getDeletedAssets());
        $this->assertNotContains($file5, $set->getDeletedAssets());
        $this->assertNotContains($file6, $set->getDeletedAssets());

        // Adding records as protected will remove them from the deletion list, but
        // not the public list
        $this->assertTrue($set->addProtectedAsset($file1));
        $this->assertFalse($set->addProtectedAsset($file5));
        $this->assertEquals(6, $this->countItems($set));
        $this->assertNotContains($file1, $set->getDeletedAssets());
        $this->assertContains($file1, $set->getProtectedAssets());
        $this->assertNotContains($file5, $set->getProtectedAssets());
        $this->assertContains($file5, $set->getPublicAssets());

        // Adding records as public will ensure they are not deleted or marked as protected
        // Existing public assets won't be re-added
        $this->assertTrue($set->addPublicAsset($file2));
        $this->assertTrue($set->addPublicAsset($file4));
        $this->assertFalse($set->addPublicAsset($file5));
        $this->assertEquals(6, $this->countItems($set));
        $this->assertNotContains($file2, $set->getDeletedAssets());
        $this->assertNotContains($file2, $set->getProtectedAssets());
        $this->assertContains($file2, $set->getPublicAssets());
        $this->assertNotContains($file4, $set->getProtectedAssets());
        $this->assertContains($file4, $set->getPublicAssets());
        $this->assertContains($file5, $set->getPublicAssets());
    }

    /**
     * Helper to count all items in a set
     *
     * @param  AssetManipulationList $set
     * @return int
     */
    protected function countItems(AssetManipulationList $set)
    {
        return count($set->getPublicAssets()) + count($set->getProtectedAssets()) + count($set->getDeletedAssets());
    }
}
