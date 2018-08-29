<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;

class GridFieldReadonlyTest extends SapphireTest
{
    protected static $fixture_file = 'GridFieldReadonlyTest.yml';

    protected static $extra_dataobjects = array(
        Team::class,
        Cheerleader::class,
    );

    /**
     * The CMS can set the value of a GridField to be a hasMany relation, which needs a readonly state.
     * This test ensures GridField has a readonly transformation.
     */
    public function testReadOnlyTransformation()
    {
        // Build a hasMany Relation via getComponents like ModelAdmin does.
        $components = Team::get_one(Team::class)
            ->getComponents('Cheerleaders');

        $gridConfig = GridFieldConfig_RelationEditor::create();

        // Build some commonly used components to make sure we're only allowing the correct components
        $gridConfig->addComponent(new GridFieldButtonRow('before'));
        $gridConfig->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $gridConfig->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-right'));
        $gridConfig->addComponent(new GridFieldToolbarHeader());
        $gridConfig->addComponent($sort = new GridFieldSortableHeader());
        $gridConfig->addComponent($filter = new GridFieldFilterHeader());
        $gridConfig->addComponent(new GridFieldDataColumns());
        $gridConfig->addComponent(new GridFieldEditButton());
        $gridConfig->addComponent(new GridFieldDeleteAction(true));
        $gridConfig->addComponent(new GridField_ActionMenu());
        $gridConfig->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $gridConfig->addComponent($pagination = new GridFieldPaginator(2));
        $gridConfig->addComponent(new GridFieldDetailForm());
        $gridConfig->addComponent(new GridFieldDeleteAction());
        $gridConfig->addComponent(new VersionedGridFieldState());

        $gridField = GridField::create(
            'Cheerleaders',
            'Cheerleaders',
            $components,
            $gridConfig
        );

        // Model Admin sets the value of the GridField directly to the relation, which doesn't have a forTemplate()
        // function, if we rely on FormField to render into a ReadonlyField we'll get an error as HasManyRelation
        // doesn't have a forTemplate() function.
        $gridField->setValue($components);
        $gridField->setModelClass(Cheerleader::class);

        // This function is called by $form->makeReadonly().
        $readonlyGridField = $gridField->performReadonlyTransformation();

        // if we've made it this far, then the GridField is at least transforming correctly.
        $readonlyComponents = $readonlyGridField->getReadonlyComponents();

        // assert that all the components in the readonly version are present in the whitelist.
        foreach($readonlyGridField->getConfig()->getComponents() as $component){
            $this->assertTrue(in_array(get_class($component), $readonlyComponents));
        }
    }
}
