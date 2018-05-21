<?php

namespace SilverStripe\Forms\GridField;

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

        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-right'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldEditButton());
        $this->addComponent(new GridFieldDeleteAction(true));
        $this->addComponent(new GridField_ActionMenu());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
        $this->addComponent(new GridFieldDetailForm());

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);

        $this->extend('updateConfig');
    }
}
