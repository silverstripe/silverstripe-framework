<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Category;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\CategoryController;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\TestController;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\GroupController;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\PeopleGroup;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest\Person;

/**
 * @skipUpgrade
 */
class GridFieldDetailFormTest extends FunctionalTest
{
    protected static $fixture_file = 'GridFieldDetailFormTest.yml';

    protected static $extra_dataobjects = array(
        Person::class,
        PeopleGroup::class,
        Category::class,
    );

    protected static $extra_controllers = [
        CategoryController::class,
        TestController::class,
        GroupController::class,
    ];

    public function testValidator()
    {
        $this->logInWithPermission('ADMIN');

        $response = $this->get('GridFieldDetailFormTest_Controller');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $addlinkitem = $parser->getBySelector('.grid-field .new-link');
        $addlink = (string) $addlinkitem[0]['href'];

        $response = $this->get($addlink);
        $this->assertFalse($response->isError());

        $parser = new CSSContentParser($response->getBody());
        $addform = $parser->getBySelector('#Form_ItemEditForm');
        $addformurl = (string) $addform[0]['action'];

        $response = $this->post(
            $addformurl,
            array(
                'FirstName' => 'Jeremiah',
                'ajax' => 1,
                'action_doSave' => 1
            )
        );

        $parser = new CSSContentParser($response->getBody());
        $errors = $parser->getBySelector('span.required');
        $this->assertEquals(1, count($errors));

        $response = $this->post(
            $addformurl,
            array(
                'ajax' => 1,
                'action_doSave' => 1
            )
        );

        $parser = new CSSContentParser($response->getBody());
        $errors = $parser->getBySelector('span.required');
        $this->assertEquals(2, count($errors));
    }

    public function testAddForm()
    {
        $this->logInWithPermission('ADMIN');
        $group = PeopleGroup::get()
            ->filter('Name', 'My Group')
            ->sort('Name')
            ->First();
        $count = $group->People()->Count();

        $response = $this->get('GridFieldDetailFormTest_Controller');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $addlinkitem = $parser->getBySelector('.grid-field .new-link');
        $addlink = (string) $addlinkitem[0]['href'];

        $response = $this->get($addlink);
        $this->assertFalse($response->isError());

        $parser = new CSSContentParser($response->getBody());
        $addform = $parser->getBySelector('#Form_ItemEditForm');
        $addformurl = (string) $addform[0]['action'];

        $response = $this->post(
            $addformurl,
            array(
                'FirstName' => 'Jeremiah',
                'Surname' => 'BullFrog',
                'action_doSave' => 1
            )
        );
        $this->assertFalse($response->isError());

        $group = PeopleGroup::get()
            ->filter('Name', 'My Group')
            ->sort('Name')
            ->First();
        $this->assertEquals($count + 1, $group->People()->Count());
    }

    public function testViewForm()
    {
        $this->logInWithPermission('ADMIN');

        $response = $this->get('GridFieldDetailFormTest_Controller');
        $parser   = new CSSContentParser($response->getBody());

        $viewLink = $parser->getBySelector('.ss-gridfield-items .first .view-link');
        $viewLink = (string) $viewLink[0]['href'];

        $response = $this->get($viewLink);
        $parser   = new CSSContentParser($response->getBody());

        $firstName = $parser->getBySelector('#Form_ItemEditForm_FirstName');
        $surname   = $parser->getBySelector('#Form_ItemEditForm_Surname');

        $this->assertFalse($response->isError());
        $this->assertEquals('Jane', (string) $firstName[0]);
        $this->assertEquals('Doe', (string) $surname[0]);
    }

    public function testEditForm()
    {
        $this->logInWithPermission('ADMIN');
        $group = PeopleGroup::get()
            ->filter('Name', 'My Group')
            ->sort('Name')
            ->First();
        $firstperson = $group->People()->First();
        $this->assertTrue($firstperson->Surname != 'Baggins');

        $response = $this->get('GridFieldDetailFormTest_Controller');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $editlinkitem = $parser->getBySelector('.ss-gridfield-items .first .edit-link');
        $editlink = (string) $editlinkitem[0]['href'];

        $response = $this->get($editlink);
        $this->assertFalse($response->isError());

        $parser = new CSSContentParser($response->getBody());
        $editform = $parser->getBySelector('#Form_ItemEditForm');
        $editformurl = (string) $editform[0]['action'];

        $response = $this->post(
            $editformurl,
            array(
                'FirstName' => 'Bilbo',
                'Surname' => 'Baggins',
                'action_doSave' => 1
            )
        );
        $this->assertFalse($response->isError());

        $group = PeopleGroup::get()
            ->filter('Name', 'My Group')
            ->sort('Name')
            ->First();
        $this->assertListContains(array(array('Surname' => 'Baggins')), $group->People());
    }

    public function testEditFormWithManyMany()
    {
        $this->logInWithPermission('ADMIN');

        // Edit the first person
        $response = $this->get('GridFieldDetailFormTest_CategoryController');
        // Find the link to add a new favourite group
        $parser = new CSSContentParser($response->getBody());
        $addLink = $parser->getBySelector('#Form_Form_testgroupsfield .new-link');
        $addLink = (string) $addLink[0]['href'];

        // Add a new favourite group
        $response = $this->get($addLink);
        $parser = new CSSContentParser($response->getBody());
        $addform = $parser->getBySelector('#Form_ItemEditForm');
        $addformurl = (string) $addform[0]['action'];

        $response = $this->post(
            $addformurl,
            array(
                'Name' => 'My Favourite Group',
                'ajax' => 1,
                'action_doSave' => 1
            )
        );
        $this->assertFalse($response->isError());

        $person = Person::get()->sort('FirstName')->First();
        $favouriteGroup = $person->FavouriteGroups()->first();

        $this->assertInstanceOf(PeopleGroup::class, $favouriteGroup);
    }

    public function testEditFormWithManyManyExtraData()
    {
        $this->logInWithPermission('ADMIN');

        // Lists all categories for a person
        $response = $this->get('GridFieldDetailFormTest_CategoryController');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $editlinkitem = $parser->getBySelector('.ss-gridfield-items .first .edit-link');
        $editlink = (string) $editlinkitem[0]['href'];

        // Edit a single category, incl. manymany extrafields added manually
        // through GridFieldDetailFormTest_CategoryController
        $response = $this->get($editlink);
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $editform = $parser->getBySelector('#Form_ItemEditForm');
        $editformurl = (string) $editform[0]['action'];

        $manyManyField = $parser->getByXpath('//*[@id="Form_ItemEditForm"]//input[@name="ManyMany[IsPublished]"]');
        $this->assertTrue((bool)$manyManyField);

        // Test save of IsPublished field
        $response = $this->post(
            $editformurl,
            array(
                'Name' => 'Updated Category',
                'ManyMany' => array(
                    'IsPublished' => 1,
                    'PublishedBy' => 'Richard'
                ),
                'action_doSave' => 1
            )
        );
        $this->assertFalse($response->isError());
        $person = Person::get()->sort('FirstName')->First();
        $category = $person->Categories()->filter(array('Name' => 'Updated Category'))->First();
        $this->assertEquals(
            array(
                'IsPublished' => 1,
                'PublishedBy' => 'Richard'
            ),
            $person->Categories()->getExtraData('', $category->ID)
        );

        // Test update of value with falsey value
        $response = $this->post(
            $editformurl,
            array(
                'Name' => 'Updated Category',
                'ManyMany' => array(
                    'PublishedBy' => ''
                ),
                'action_doSave' => 1
            )
        );
        $this->assertFalse($response->isError());

        $person = Person::get()->sort('FirstName')->First();
        $category = $person->Categories()->filter(array('Name' => 'Updated Category'))->First();
        $this->assertEquals(
            array(
                'IsPublished' => 0,
                'PublishedBy' => ''
            ),
            $person->Categories()->getExtraData('', $category->ID)
        );
    }

    public function testNestedEditForm()
    {
        $this->logInWithPermission('ADMIN');

        $group = $this->objFromFixture(PeopleGroup::class, 'group');
        $person = $group->People()->First();
        $category = $person->Categories()->First();

        // Get first form (GridField managing PeopleGroup)
        $response = $this->get('GridFieldDetailFormTest_GroupController');
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());

        $groupEditLink = $parser->getByXpath(
            '//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "'
            . $group->ID . '")]//a'
        );
        $this->assertEquals(
            'GridFieldDetailFormTest_GroupController/Form/field/testfield/item/' . $group->ID . '/edit',
            (string)$groupEditLink[0]['href']
        );

        // Get second level form (GridField managing Person)
        $response = $this->get((string)$groupEditLink[0]['href']);
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $personEditLink = $parser->getByXpath(
            '//fieldset[@id="Form_ItemEditForm_People"]' .
            '//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $person->ID . '")]//a'
        );
        $this->assertEquals(
            sprintf(
                'GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People'
                . '/item/%d/edit',
                $group->ID,
                $person->ID
            ),
            (string)$personEditLink[0]['href']
        );

        // Get third level form (GridField managing Category)
        $response = $this->get((string)$personEditLink[0]['href']);
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $categoryEditLink = $parser->getByXpath(
            '//fieldset[@id="Form_ItemEditForm_Categories"]'
            . '//tr[contains(@class, "ss-gridfield-item") and contains(@data-id, "' . $category->ID . '")]//a'
        );
        $this->assertEquals(
            sprintf(
                'GridFieldDetailFormTest_GroupController/Form/field/testfield/item/%d/ItemEditForm/field/People'
                . '/item/%d/ItemEditForm/field/Categories/item/%d/edit',
                $group->ID,
                $person->ID,
                $category->ID
            ),
            (string)$categoryEditLink[0]['href']
        );

        // Fourth level form would be a Category detail view
    }

    public function testCustomItemRequestClass()
    {
        $this->logInWithPermission('ADMIN');

        $component = new GridFieldDetailForm();
        $this->assertEquals('SilverStripe\\Forms\\GridField\\GridFieldDetailForm_ItemRequest', $component->getItemRequestClass());
        $component->setItemRequestClass('GridFieldDetailFormTest_ItemRequest');
        $this->assertEquals('GridFieldDetailFormTest_ItemRequest', $component->getItemRequestClass());
    }

    public function testItemEditFormCallback()
    {
        $this->logInWithPermission('ADMIN');

        $category = new Category();
        $component = new GridFieldDetailForm();
        $component->setItemEditFormCallback(
            function ($form, $component) {
                $form->Fields()->push(new HiddenField('Callback'));
            }
        );
        // Note: A lot of scaffolding to execute the tested logic,
        // due to the coupling of form creation with itemRequest handling (and its context)
        /** @skipUpgrade */
        $itemRequest = new GridFieldDetailForm_ItemRequest(
            GridField::create('Categories', 'Categories'),
            $component,
            $category,
            Controller::curr(),
            'Form'
        );
        $itemRequest->setRequest(Controller::curr()->getRequest());
        $form = $itemRequest->ItemEditForm();
        $this->assertNotNull($form->Fields()->fieldByName('Callback'));
    }

    /**
     * Tests that a has-many detail form is pre-populated with the parent ID.
     */
    public function testHasManyFormPrePopulated()
    {
        $group = $this->objFromFixture(
            PeopleGroup::class,
            'group'
        );

        $this->logInWithPermission('ADMIN');

        $response = $this->get('GridFieldDetailFormTest_Controller');
        $parser = new CSSContentParser($response->getBody());
        $addLink = $parser->getBySelector('.grid-field .new-link');
        $addLink = (string) $addLink[0]['href'];

        $response = $this->get($addLink);
        $parser = new CSSContentParser($response->getBody());
        $title = $parser->getBySelector('#Form_ItemEditForm_GroupID_Holder span');
        $id = $parser->getBySelector('#Form_ItemEditForm_GroupID_Holder input');

        $this->assertEquals($group->Name, (string) $title[0]);
        $this->assertEquals($group->ID, (string) $id[0]['value']);
    }
}
