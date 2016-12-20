<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\Versioning\ChangeSetItem;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Dev\SapphireTest;
use DateTime;

/**
 * Tests ownership API of versioned DataObjects
 */
class VersionedOwnershipTest extends SapphireTest
{

    protected $extraDataObjects = array(
        VersionedOwnershipTest\TestObject::class,
        VersionedOwnershipTest\Subclass::class,
        VersionedOwnershipTest\Related::class,
        VersionedOwnershipTest\Attachment::class,
        VersionedOwnershipTest\RelatedMany::class,
        VersionedOwnershipTest\TestPage::class,
        VersionedOwnershipTest\Banner::class,
        VersionedOwnershipTest\Image::class,
        VersionedOwnershipTest\CustomRelation::class,
    );

    protected static $fixture_file = 'VersionedOwnershipTest.yml';

    public function setUp()
    {
        parent::setUp();

        Versioned::set_stage(Versioned::DRAFT);

        // Automatically publish any object named *_published
        foreach ($this->getFixtureFactory()->getFixtures() as $class => $fixtures) {
            foreach ($fixtures as $name => $id) {
                if (stripos($name, '_published') !== false) {
                    /** @var Versioned|DataObject $object */
                    $object = DataObject::get($class)->byID($id);
                    $object->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
                }
            }
        }
    }

    /**
     * Virtual "sleep" that doesn't actually slow execution, only advances DBDateTime::now()
     *
     * @param int $minutes
     */
    protected function sleep($minutes)
    {
        $now = DBDatetime::now();
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $now->getValue());
        $date->modify("+{$minutes} minutes");
        DBDatetime::set_mock_now($date->format('Y-m-d H:i:s'));
    }

    /**
     * Test basic findOwned() in stage mode
     */
    public function testFindOwned()
    {
        /** @var VersionedOwnershipTest\Subclass $subclass1 */
        $subclass1 = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass1_published');
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Attachment 1'],
                ['Title' => 'Attachment 2'],
                ['Title' => 'Attachment 5'],
                ['Title' => 'Related Many 1'],
                ['Title' => 'Related Many 2'],
                ['Title' => 'Related Many 3'],
            ],
            $subclass1->findOwned()
        );

        // Non-recursive search
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Related Many 1'],
                ['Title' => 'Related Many 2'],
                ['Title' => 'Related Many 3'],
            ],
            $subclass1->findOwned(false)
        );

        /** @var VersionedOwnershipTest\Subclass $subclass2 */
        $subclass2 = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass2_published');
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2'],
                ['Title' => 'Attachment 3'],
                ['Title' => 'Attachment 4'],
                ['Title' => 'Attachment 5'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2->findOwned()
        );

        // Non-recursive search
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2->findOwned(false)
        );

        /** @var VersionedOwnershipTest\Related $related1 */
        $related1 = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related1');
        $this->assertDOSEquals(
            [
                ['Title' => 'Attachment 1'],
                ['Title' => 'Attachment 2'],
                ['Title' => 'Attachment 5'],
            ],
            $related1->findOwned()
        );

        /** @var VersionedOwnershipTest\Related $related2 */
        $related2 = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related2_published');
        $this->assertDOSEquals(
            [
                ['Title' => 'Attachment 3'],
                ['Title' => 'Attachment 4'],
                ['Title' => 'Attachment 5'],
            ],
            $related2->findOwned()
        );
    }

    /**
     * Test findOwners
     */
    public function testFindOwners()
    {
        /** @var VersionedOwnershipTest\Attachment $attachment1 */
        $attachment1 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment1');
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Subclass 1'],
            ],
            $attachment1->findOwners()
        );

        // Non-recursive search
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
            ],
            $attachment1->findOwners(false)
        );

        /** @var VersionedOwnershipTest\Attachment $attachment5 */
        $attachment5 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment5_published');
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Related 2'],
                ['Title' => 'Subclass 1'],
                ['Title' => 'Subclass 2'],
            ],
            $attachment5->findOwners()
        );

        // Non-recursive
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 1'],
                ['Title' => 'Related 2'],
            ],
            $attachment5->findOwners(false)
        );

        /** @var VersionedOwnershipTest\Related $related1 */
        $related1 = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related1');
        $this->assertDOSEquals(
            [
                ['Title' => 'Subclass 1'],
            ],
            $related1->findOwners()
        );
    }

    /**
     * Test findOwners on Live stage
     */
    public function testFindOwnersLive()
    {
        // Modify a few records on stage
        $related2 = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related2_published');
        $related2->Title .= ' Modified';
        $related2->write();
        $attachment3 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment3_published');
        $attachment3->Title .= ' Modified';
        $attachment3->write();
        $attachment4 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment4_published');
        $attachment4->delete();
        $subclass2ID = $this->idFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass2_published');

        // Check that stage record is ok
        /** @var VersionedOwnershipTest\Subclass $subclass2Stage */
        $subclass2Stage = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, 'Stage')->byID($subclass2ID);
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2 Modified'],
                ['Title' => 'Attachment 3 Modified'],
                ['Title' => 'Attachment 5'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2Stage->findOwned()
        );

        // Non-recursive
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2 Modified'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2Stage->findOwned(false)
        );

        // Live records are unchanged
        /** @var VersionedOwnershipTest\Subclass $subclass2Live */
        $subclass2Live = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, 'Live')->byID($subclass2ID);
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2'],
                ['Title' => 'Attachment 3'],
                ['Title' => 'Attachment 4'],
                ['Title' => 'Attachment 5'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2Live->findOwned()
        );

        // Test non-recursive
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2'],
                ['Title' => 'Related Many 4'],
            ],
            $subclass2Live->findOwned(false)
        );
    }

    /**
     * Test that objects are correctly published recursively
     */
    public function testRecursivePublish()
    {
        /** @var VersionedOwnershipTest\Subclass $parent */
        $parent = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass1_published');
        $parentID = $parent->ID;
        $banner1 = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany1_published');
        $banner2 = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany2_published');
        $banner2ID = $banner2->ID;

        // Modify, Add, and Delete banners on stage
        $banner1->Title = 'Renamed Banner 1';
        $banner1->write();

        $banner2->delete();

        $banner4 = new VersionedOwnershipTest\RelatedMany();
        $banner4->Title = 'New Banner';
        $parent->Banners()->add($banner4);

        // Check state of objects before publish
        $oldLiveBanners = [
            ['Title' => 'Related Many 1'],
            ['Title' => 'Related Many 2'], // Will be unlinked (but not deleted)
            // `Related Many 3` isn't published
        ];
        $newBanners = [
            ['Title' => 'Renamed Banner 1'], // Renamed
            ['Title' => 'Related Many 3'], // Published without changes
            ['Title' => 'New Banner'], // Created
        ];
        $parentDraft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($parentID);
        $this->assertDOSEquals($newBanners, $parentDraft->Banners());
        $parentLive = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::LIVE)
            ->byID($parentID);
        $this->assertDOSEquals($oldLiveBanners, $parentLive->Banners());

        // On publishing of owner, all children should now be updated
        $now = DBDatetime::now();
        DBDatetime::set_mock_now($now); // Lock 'now' to predictable time
        $parent->publishRecursive();

        // Now check each object has the correct state
        $parentDraft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($parentID);
        $this->assertDOSEquals($newBanners, $parentDraft->Banners());
        $parentLive = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::LIVE)
            ->byID($parentID);
        $this->assertDOSEquals($newBanners, $parentLive->Banners());

        // Check that the deleted banner hasn't actually been deleted from the live stage,
        // but in fact has been unlinked.
        $banner2Live = Versioned::get_by_stage(VersionedOwnershipTest\RelatedMany::class, Versioned::LIVE)
            ->byID($banner2ID);
        $this->assertEmpty($banner2Live->PageID);

        // Test that a changeset was created
        /** @var ChangeSet $changeset */
        $changeset = ChangeSet::get()->sort('"ChangeSet"."ID" DESC')->first();
        $this->assertNotEmpty($changeset);

        // Test that this changeset is inferred
        $this->assertTrue((bool)$changeset->IsInferred);
        $this->assertEquals(
            "Generated by publish of 'Subclass 1' at ".$now->Nice(),
            $changeset->getTitle()
        );

        // Test that this changeset contains all items
        $this->assertDOSContains(
            [
                [
                    'ObjectID' => $parent->ID,
                    'ObjectClass' => $parent->baseClass(),
                    'Added' => ChangeSetItem::EXPLICITLY
                ],
                [
                    'ObjectID' => $banner1->ID,
                    'ObjectClass' => $banner1->baseClass(),
                    'Added' => ChangeSetItem::IMPLICITLY
                ],
                [
                    'ObjectID' => $banner4->ID,
                    'ObjectClass' => $banner4->baseClass(),
                    'Added' => ChangeSetItem::IMPLICITLY
                ]
            ],
            $changeset->Changes()
        );

        // Objects that are unlinked should not need to be a part of the changeset
        $this->assertNotDOSContains(
            [[ 'ObjectID' => $banner2ID, 'ObjectClass' => $banner2->baseClass() ]],
            $changeset->Changes()
        );
    }

    /**
     * Test that owning objects get unpublished as needed
     */
    public function testRecursiveUnpublish()
    {
        // Unsaved objects can't be unpublished
        $unsaved = new VersionedOwnershipTest\Subclass();
        $this->assertFalse($unsaved->doUnpublish());

        // Draft-only objects can't be unpublished
        /** @var VersionedOwnershipTest\RelatedMany $banner3Unpublished */
        $banner3Unpublished = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany3');
        $this->assertFalse($banner3Unpublished->doUnpublish());

        // First test: mid-level unpublish; We expect that owners should be unpublished, but not
        // owned objects, nor other siblings shared by the same owner.
        $related2 = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related2_published');
        /** @var VersionedOwnershipTest\Attachment $attachment3 */
        $attachment3 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment3_published');
        /** @var VersionedOwnershipTest\RelatedMany $relatedMany4 */
        $relatedMany4 = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany4_published');
        /** @var VersionedOwnershipTest\Related $related2 */
        $this->assertTrue($related2->doUnpublish());
        $subclass2 = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass2_published');

        /** @var VersionedOwnershipTest\Subclass $subclass2 */
        $this->assertFalse($subclass2->isPublished()); // Owner IS unpublished
        $this->assertTrue($attachment3->isPublished()); // Owned object is NOT unpublished
        $this->assertTrue($relatedMany4->isPublished()); // Owned object by owner is NOT unpublished

        // Second test: multi-level unpublish should recursively cascade down all owning objects
        // Publish related2 again
        $subclass2->publishRecursive();
        $this->assertTrue($subclass2->isPublished());
        $this->assertTrue($related2->isPublished());
        $this->assertTrue($attachment3->isPublished());

        // Unpublish leaf node
        $this->assertTrue($attachment3->doUnpublish());

        // Now all owning objects (only) are unpublished
        $this->assertFalse($attachment3->isPublished()); // Unpublished because we just unpublished it
        $this->assertFalse($related2->isPublished()); // Unpublished because it owns attachment3
        $this->assertFalse($subclass2->isPublished()); // Unpublished ecause it owns related2
        $this->assertTrue($relatedMany4->isPublished()); // Stays live because recursion only affects owners not owned.
    }

    public function testRecursiveArchive()
    {
        // When archiving an object, any published owners should be unpublished at the same time
        // but NOT achived

        /** @var VersionedOwnershipTest\Attachment $attachment3 */
        $attachment3 = $this->objFromFixture(VersionedOwnershipTest\Attachment::class, 'attachment3_published');
        $attachment3ID = $attachment3->ID;
        $this->assertTrue($attachment3->doArchive());

        // This object is on neither stage nor live
        $stageAttachment = Versioned::get_by_stage(VersionedOwnershipTest\Attachment::class, Versioned::DRAFT)
            ->byID($attachment3ID);
        $liveAttachment = Versioned::get_by_stage(VersionedOwnershipTest\Attachment::class, Versioned::LIVE)
            ->byID($attachment3ID);
        $this->assertEmpty($stageAttachment);
        $this->assertEmpty($liveAttachment);

        // Owning object is unpublished only
        /** @var VersionedOwnershipTest\Related $stageOwner */
        $stageOwner = $this->objFromFixture(VersionedOwnershipTest\Related::class, 'related2_published');
        $this->assertTrue($stageOwner->isOnDraft());
        $this->assertFalse($stageOwner->isPublished());

        // Bottom level owning object is also unpublished
        /** @var VersionedOwnershipTest\Subclass $stageTopOwner */
        $stageTopOwner = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass2_published');
        $this->assertTrue($stageTopOwner->isOnDraft());
        $this->assertFalse($stageTopOwner->isPublished());
    }

    public function testRecursiveRevertToLive()
    {
        /** @var VersionedOwnershipTest\Subclass $parent */
        $parent = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass1_published');
        $parentID = $parent->ID;
        $banner1 = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany1_published');
        $banner2 = $this->objFromFixture(VersionedOwnershipTest\RelatedMany::class, 'relatedmany2_published');
        $banner2ID = $banner2->ID;

        // Modify, Add, and Delete banners on stage
        $banner1->Title = 'Renamed Banner 1';
        $banner1->write();

        $banner2->delete();

        $banner4 = new VersionedOwnershipTest\RelatedMany();
        $banner4->Title = 'New Banner';
        $banner4->write();
        $parent->Banners()->add($banner4);

        // Check state of objects before publish
        $liveBanners = [
            ['Title' => 'Related Many 1'],
            ['Title' => 'Related Many 2'],
        ];
        $modifiedBanners = [
            ['Title' => 'Renamed Banner 1'], // Renamed
            ['Title' => 'Related Many 3'], // Published without changes
            ['Title' => 'New Banner'], // Created
        ];
        $parentDraft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($parentID);
        $this->assertDOSEquals($modifiedBanners, $parentDraft->Banners());
        $parentLive = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::LIVE)
            ->byID($parentID);
        $this->assertDOSEquals($liveBanners, $parentLive->Banners());

        // When reverting parent, all records should be put back on stage
        $this->assertTrue($parent->doRevertToLive());

        // Now check each object has the correct state
        $parentDraft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($parentID);
        $this->assertDOSEquals($liveBanners, $parentDraft->Banners());
        $parentLive = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::LIVE)
            ->byID($parentID);
        $this->assertDOSEquals($liveBanners, $parentLive->Banners());

        // Check that the newly created banner, even though it still exist, has been
        // unlinked from the reverted draft record
        /** @var VersionedOwnershipTest\RelatedMany $banner4Draft */
        $banner4Draft = Versioned::get_by_stage(VersionedOwnershipTest\RelatedMany::class, Versioned::DRAFT)
            ->byID($banner4->ID);
        $this->assertTrue($banner4Draft->isOnDraft());
        $this->assertFalse($banner4Draft->isPublished());
        $this->assertEmpty($banner4Draft->PageID);
    }

    /**
     * Test that rolling back to a single version works recursively
     */
    public function testRecursiveRollback()
    {
        /** @var VersionedOwnershipTest\Subclass $subclass2 */
        $this->sleep(1);
        $subclass2 = $this->objFromFixture(VersionedOwnershipTest\Subclass::class, 'subclass2_published');

        // Create a few new versions
        $versions = [];
        for ($version = 1; $version <= 3; $version++) {
            // Write owned objects
            $this->sleep(1);
            foreach ($subclass2->findOwned(true) as $obj) {
                $obj->Title .= " - v{$version}";
                $obj->write();
            }
            // Write parent
            $this->sleep(1);
            $subclass2->Title .= " - v{$version}";
            $subclass2->write();
            $versions[$version] = $subclass2->Version;
        }


        // Check reverting to first version
        $this->sleep(1);
        $subclass2->doRollbackTo($versions[1]);
        /** @var VersionedOwnershipTest\Subclass $subclass2Draft */
        $subclass2Draft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($subclass2->ID);
        $this->assertEquals('Subclass 2 - v1', $subclass2Draft->Title);
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2 - v1'],
                ['Title' => 'Attachment 3 - v1'],
                ['Title' => 'Attachment 4 - v1'],
                ['Title' => 'Attachment 5 - v1'],
                ['Title' => 'Related Many 4 - v1'],
            ],
            $subclass2Draft->findOwned(true)
        );

        // Check rolling forward to a later version
        $this->sleep(1);
        $subclass2->doRollbackTo($versions[3]);
        /** @var VersionedOwnershipTest\Subclass $subclass2Draft */
        $subclass2Draft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($subclass2->ID);
        $this->assertEquals('Subclass 2 - v1 - v2 - v3', $subclass2Draft->Title);
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2 - v1 - v2 - v3'],
                ['Title' => 'Attachment 3 - v1 - v2 - v3'],
                ['Title' => 'Attachment 4 - v1 - v2 - v3'],
                ['Title' => 'Attachment 5 - v1 - v2 - v3'],
                ['Title' => 'Related Many 4 - v1 - v2 - v3'],
            ],
            $subclass2Draft->findOwned(true)
        );

        // And rolling back one version
        $this->sleep(1);
        $subclass2->doRollbackTo($versions[2]);
        /** @var VersionedOwnershipTest\Subclass $subclass2Draft */
        $subclass2Draft = Versioned::get_by_stage(VersionedOwnershipTest\Subclass::class, Versioned::DRAFT)
            ->byID($subclass2->ID);
        $this->assertEquals('Subclass 2 - v1 - v2', $subclass2Draft->Title);
        $this->assertDOSEquals(
            [
                ['Title' => 'Related 2 - v1 - v2'],
                ['Title' => 'Attachment 3 - v1 - v2'],
                ['Title' => 'Attachment 4 - v1 - v2'],
                ['Title' => 'Attachment 5 - v1 - v2'],
                ['Title' => 'Related Many 4 - v1 - v2'],
            ],
            $subclass2Draft->findOwned(true)
        );
    }

    /**
     * Test that you can find owners without owned_by being defined explicitly
     */
    public function testInferedOwners()
    {
        // Make sure findOwned() works
        /** @var VersionedOwnershipTest\TestPage $page1 */
        $page1 = $this->objFromFixture(VersionedOwnershipTest\TestPage::class, 'page1_published');
        /** @var VersionedOwnershipTest\TestPage $page2 */
        $page2 = $this->objFromFixture(VersionedOwnershipTest\TestPage::class, 'page2_published');
        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 1'],
                ['Title' => 'Image 1'],
                ['Title' => 'Custom 1'],
            ],
            $page1->findOwned()
        );
        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 2'],
                ['Title' => 'Banner 3'],
                ['Title' => 'Image 1'],
                ['Title' => 'Image 2'],
                ['Title' => 'Custom 2'],
            ],
            $page2->findOwned()
        );

        // Check that findOwners works
        /** @var VersionedOwnershipTest\Image $image1 */
        $image1 = $this->objFromFixture(VersionedOwnershipTest\Image::class, 'image1_published');
        /** @var VersionedOwnershipTest\Image $image2 */
        $image2 = $this->objFromFixture(VersionedOwnershipTest\Image::class, 'image2_published');

        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 1'],
                ['Title' => 'Banner 2'],
                ['Title' => 'Page 1'],
                ['Title' => 'Page 2'],
            ],
            $image1->findOwners()
        );
        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 1'],
                ['Title' => 'Banner 2'],
            ],
            $image1->findOwners(false)
        );
        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 3'],
                ['Title' => 'Page 2'],
            ],
            $image2->findOwners()
        );
        $this->assertDOSEquals(
            [
                ['Title' => 'Banner 3'],
            ],
            $image2->findOwners(false)
        );

        // Test custom relation can findOwners()
        /** @var VersionedOwnershipTest\CustomRelation $custom1 */
        $custom1 = $this->objFromFixture(VersionedOwnershipTest\CustomRelation::class, 'custom1_published');
        $this->assertDOSEquals(
            [['Title' => 'Page 1']],
            $custom1->findOwners()
        );
    }
}
