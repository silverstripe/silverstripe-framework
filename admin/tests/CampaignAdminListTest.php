<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\Admin\CampaignAdminList;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;

class CampaignAdminListTest extends SapphireTest
{
    public function testSchema()
    {
        $fields = new FieldList(
            $changesets = CampaignAdminList::create('ChangeSets')
        );
        $actions = new FieldList();
        Form::create(new Controller(), 'EditForm', $fields, $actions);

        $schema = $changesets->getSchemaData();

        // Check endpoint urls
        $this->assertEquals('admin/campaigns/sets', $schema['data']['collectionReadEndpoint']['url']);
        $this->assertEquals('admin/campaigns/set/:id', $schema['data']['itemReadEndpoint']['url']);
        $this->assertEquals('admin/campaigns/set/:id', $schema['data']['itemUpdateEndpoint']['url']);
        $this->assertEquals('admin/campaigns/set/:id', $schema['data']['itemCreateEndpoint']['url']);
        $this->assertEquals('admin/campaigns/set/:id', $schema['data']['itemDeleteEndpoint']['url']);
        $this->assertEquals('admin/campaigns/schema/DetailEditForm', $schema['data']['editFormSchemaEndpoint']);

        // Check summary fields
        $this->assertEquals([
            [
                'field' => 'Name',
                'name' => 'Title',
            ],
            [
                'field' => 'ChangesCount',
                'name' => 'Changes',
            ],
            [
                'field' => 'Description',
                'name' => 'Description',
            ]
        ], $schema['data']['columns']);
    }
}
