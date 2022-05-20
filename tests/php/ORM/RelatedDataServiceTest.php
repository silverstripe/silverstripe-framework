<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\Base;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\Belongs;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\HasMany;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\Hub;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\HubExtension;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\HubSub;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ManyMany;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ManyManyNoBelongs;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ManyManyThrough;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ManyManyThroughNoBelongs;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\Node;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\Polymorphic;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\SelfReferentialNode;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ThroughObject;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ThroughObjectPolymorphic;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ThroughObjectMMT;
use SilverStripe\ORM\Tests\RelatedDataServiceTest\ThroughObjectMMTNB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLDelete;

class RelatedDataServiceTest extends SapphireTest
{

    protected $usesDatabase = true;

    // This static is required to get Config to populate
    // Config is looped within RelatedDataService::findAll()
    protected static $extra_data_objects = [
        Base::class,
        Belongs::class,
        HasMany::class,
        Hub::class,
        HubSub::class,
        ManyMany::class,
        ManyManyNoBelongs::class,
        ManyManyThrough::class,
        ManyManyThroughNoBelongs::class,
        Node::class,
        Polymorphic::class,
        SelfReferentialNode::class,
        ThroughObject::class,
        ThroughObjectMMT::class,
        ThroughObjectMMTNB::class,
        ThroughObjectPolymorphic::class,
    ];

    // This is static is required to get the database tables to get created
    protected static $extra_dataobjects = [
        Base::class,
        Belongs::class,
        HasMany::class,
        Hub::class,
        HubSub::class,
        ManyMany::class,
        ManyManyNoBelongs::class,
        ManyManyThrough::class,
        ManyManyThroughNoBelongs::class,
        Node::class,
        Polymorphic::class,
        SelfReferentialNode::class,
        ThroughObject::class,
        ThroughObjectMMT::class,
        ThroughObjectMMTNB::class,
        ThroughObjectPolymorphic::class,
    ];

    public function testUnsaved()
    {
        $myFile = new Node();
        // don't write()
        $list = $myFile->findAllRelatedData();
        $this->assertTrue($list instanceof SS_List);
        $this->assertSame(0, $list->count());
    }

    public function testUsageUnrelated()
    {
        $myFile = new Node();
        $myFile->write();
        $myPage = new Hub();
        $myPage->Title = 'Unrelated page';
        $myPage->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(0, $list->count());
    }

    public function testUsageHasOne()
    {
        $pageTitle = 'My Page that has_one File';
        $myFile = new Node();
        $myFile->write();
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->HO = $myFile;
        $myPage->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageHasOneHubExtension()
    {
        // Add DataExtension and reset database so that tables + columns get added
        Hub::add_extension(HubExtension::class);
        DataObject::reset();
        self::resetDBSchema(true, true);
        //
        $pageTitle = 'My Page that has_one File using HubExtension';
        $myFile = new Node();
        $myFile->write();
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->ExtHO = $myFile;
        $myPage->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageHubSub()
    {
        $pageTitle = 'My Sub Page';
        $pageSubTitle = 'My SubTitle';
        $myFile = new Node();
        $myFile->write();
        $myPage = new HubSub();
        $myPage->Title = $pageTitle;
        $myPage->SubTitle = $pageSubTitle;
        $myPage->HO = $myFile;
        $myPage->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
        $this->assertSame($pageSubTitle, $list->first()->SubTitle);
    }

    public function testUsageHasOnePolymorphic()
    {
        $pageTitle = 'My Page that has_one File polymorphic';
        $myFile = new Node();
        $myFile->write();
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->Parent = $myFile;
        $myPage->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageHasOnePolymorphicOnNode()
    {
        $pageTitle = 'My Page that that belongs to a polymorphic File';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Polymorphic();
        $myFile->Parent = $myPage;
        $myFile->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageHasMany()
    {
        $pageTitle = 'My Page that has_many File';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new HasMany();
        $myFile->write();
        $myPage->HM()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageManyManyWithBelongs()
    {
        $pageTitle = 'My Page that many_many File with belong_many_many Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Belongs();
        $myFile->write();
        $myPage->MMtoBMM()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageManyManyWithoutBelongs()
    {
        $pageTitle = 'My Page that many_many File without belong_many_many Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Node();
        $myFile->write();
        $myPage->MMtoNoBMM()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageManyManyWithoutBelongsHubExtension()
    {
        // Add DataExtension and reset database so that tables + columns get added
        Hub::add_extension(HubExtension::class);
        DataObject::reset();
        self::resetDBSchema(true, true);
        //
        $pageTitle = 'My Page that many_many File without belong_many_many Page using HubExtension';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Node();
        $myFile->write();
        $myPage->ExtMMtoNoBMM()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageManyManyWithoutBelongsOrphanedJoinTable()
    {
        $pageTitle = 'My Page that many_many File without belong_many_many Page orphaned join table';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Node();
        $myFile->write();
        $myPage->MMtoNoBMM()->add($myFile);
        // manually delete Page record from database, leaving join table record intact
        SQLDelete::create('"TestOnly_RelatedDataServiceTest_Hub"', sprintf('"ID" = %s', $myPage->ID))->execute();
        SQLDelete::create('"TestOnly_RelatedDataServiceTest_Base"', sprintf('"ID" = %s', $myPage->ID))->execute();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(0, $list->count());
    }

    public function testUsageBelongsManyMany()
    {
        $pageTitle = 'My Page that belongs_many_many File with many_many Page';
        $pageTitle2 = 'My other Page that belongs_many_many File with many_many Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myPage2 = new Hub();
        $myPage2->Title = $pageTitle2;
        $myPage2->write();
        $myFile = new ManyMany();
        $myFile->write();
        // add from both pages from different directions
        $myPage->BMMtoMM()->add($myFile);
        $myFile->Hubs()->add($myPage2);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(2, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
        $this->assertSame($pageTitle2, $list->last()->Title);
    }

    public function testUsageManyManyThrough()
    {
        $pageTitle = 'My Page that many_many_through File';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Node();
        $myFile->write();
        $myPage->MMT()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageManyManyThroughPolymorphic()
    {
        $pageTitle = 'My Page that many_many_through_parent_class File';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new Node();
        $myFile->write();
        $myPage->MMTP()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageFileManyManyWithoutPageBelongs()
    {
        $pageTitle = 'My Page that not belongs_many_many File with many_many Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new ManyManyNoBelongs();
        $myFile->write();
        $myFile->Hubs()->add($myPage);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testUsageFileManyManyThroughWithPageBelongs()
    {
        $pageTitle = 'My Page that many_many_belongs File with many_many_through Page';
        $pageTitle2 = 'My other Page that many_many_belongs File with many_many_through Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myPage2 = new Hub();
        $myPage2->Title = $pageTitle2;
        $myPage2->write();
        $myFile = new ManyManyThrough();
        $myFile->write();
        // add from both pages from different directions
        $myPage->BMMtoMMT()->add($myFile);
        $myFile->Hubs()->add($myPage2);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(2, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
        $this->assertSame($pageTitle2, $list->last()->Title);
    }

    public function testUsageFileManyManyThroughWithoutPageBelongs()
    {
        $pageTitle = 'My Page that does not many_many_belongs File that many_many_through Page';
        $myPage = new Hub();
        $myPage->Title = $pageTitle;
        $myPage->write();
        $myFile = new ManyManyThroughNoBelongs();
        $myFile->write();
        $myFile->Hubs()->add($myPage);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(1, $list->count());
        $this->assertSame($pageTitle, $list->first()->Title);
    }

    public function testSelfReferentialHasOne()
    {
        $myFile = new SelfReferentialNode();
        $myFile->Title = 'My self referential file has_one';
        $myFile->write();
        $myFile->HOA = $myFile;
        $myFile->HOB = $myFile;
        $myFile->write();
        $list = $myFile->findAllRelatedData();
        $this->assertSame(2, $list->count());
        $this->assertSame($myFile->Title, $list->first()->Title);
        $this->assertTrue($list->first() instanceof SelfReferentialNode);
        $this->assertSame($myFile->Title, $list->last()->Title);
        $this->assertTrue($list->last() instanceof SelfReferentialNode);
    }

    public function testSelfReferentialManyMany()
    {
        $myFile = new SelfReferentialNode();
        $myFile->Title = 'My self referential file many_many';
        $myFile->write();
        $myFile->MMA()->add($myFile);
        $myFile->MMB()->add($myFile);
        $list = $myFile->findAllRelatedData();
        $this->assertSame(2, $list->count());
        $this->assertSame($myFile->Title, $list->first()->Title);
        $this->assertTrue($list->first() instanceof SelfReferentialNode);
        $this->assertSame($myFile->Title, $list->last()->Title);
        $this->assertTrue($list->last() instanceof SelfReferentialNode);
    }
}
