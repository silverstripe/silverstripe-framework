<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Dev\Deprecation;

/**
 * Similar to {@link GridFieldConfig_RecordEditor}, but adds features to work
 * on has-many or many-many relationships.
 *
 * Allows to search for existing records to add to the relationship, detach
 * listed records from the relationship (rather than removing them from the
 * database), and automatically add newly created records to it.
 *
 * To further configure the field, use {@link getComponentByType()}, for
 * example to change the field to search.
 *
 * <code>
 * GridFieldConfig_RelationEditor::create()
 *    ->getComponentByType('GridFieldAddExistingAutocompleter')
 *    ->setSearchFields('MyField');
 * </code>
 */
class GridFieldConfig_RelationEditor extends GridFieldConfig
{

    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct();

        $this->addComponent(GridFieldButtonRow::create('before'));
        $this->addComponent(GridFieldAddNewButton::create('buttons-before-left'));
        $this->addComponent(GridFieldAddExistingAutocompleter::create('buttons-before-right'));
        $this->addComponent(GridFieldToolbarHeader::create());
        $this->addComponent($sort = GridFieldSortableHeader::create());
        $this->addComponent($filter = GridFieldFilterHeader::create());
        $this->addComponent(GridFieldDataColumns::create());
        $this->addComponent(GridFieldEditButton::create());
        $this->addComponent(GridFieldDeleteAction::create(true));
        $this->addComponent(GridField_ActionMenu::create());
        $this->addComponent(GridFieldPageCount::create('toolbar-header-right'));
        $this->addComponent($pagination = GridFieldPaginator::create($itemsPerPage));
        $this->addComponent(GridFieldDetailForm::create());

        Deprecation::withNoReplacement(function () use ($sort, $filter, $pagination) {
            $sort->setThrowExceptionOnBadDataType(false);
            $filter->setThrowExceptionOnBadDataType(false);
            $pagination->setThrowExceptionOnBadDataType(false);
        });

        $this->extend('updateConfig');
    }
}
