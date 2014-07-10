<?php
/**
 * This class is a {@link GridField} component that adds a delete action for 
 * objects.
 *
 * This component also supports unlinking a relation instead of deleting the 
 * object.
 *
 * Use the {@link $removeRelation} property set in the constructor.
 *
 * <code>
 * $action = new GridFieldDeleteAction(); // delete objects permanently
 *
 * // removes the relation to object instead of deleting
 * $action = new GridFieldDeleteAction(true); 
 * </code>
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldDeleteAction implements GridField_ColumnProvider, GridField_ActionProvider {
	
	/**
	 * If this is set to true, this {@link GridField_ActionProvider} will 
	 * remove the object from the list, instead of deleting. 
	 *
	 * In the case of a has one, has many or many many list it will uncouple 
	 * the item from the list.
	 *
	 * @var boolean
	 */
	protected $removeRelation = false;
	
	/**
	 *
	 * @param boolean $removeRelation - true if removing the item from the list, but not deleting it
	 */
	public function __construct($removeRelation = false) {
		$this->removeRelation = $removeRelation;
	}
	
	/**
	 * Add a column 'Delete'
	 * 
	 * @param type $gridField
	 * @param array $columns 
	 */
	public function augmentColumns($gridField, &$columns) {
		if(!in_array('Actions', $columns)) {
			$columns[] = 'Actions';
		}
	}
	
	/**
	 * Return any special attributes that will be used for FormField::create_tag()
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName) {
		return array('class' => 'col-buttons');
	}
	
	/**
	 * Add the title 
	 * 
	 * @param GridField $gridField
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnMetadata($gridField, $columnName) {
		if($columnName == 'Actions') {
			return array('title' => '');
		}
	}
	
	/**
	 * Which columns are handled by this component
	 * 
	 * @param type $gridField
	 * @return type 
	 */
	public function getColumnsHandled($gridField) {
		return array('Actions');
	}
	
	/**
	 * Which GridField actions are this component handling
	 *
	 * @param GridField $gridField
	 * @return array 
	 */
	public function getActions($gridField) {
		return array('deleterecord', 'unlinkrelation');
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		if($this->removeRelation) {
			// check if we can write to the parent, that means we'll be able
			// to unlink the relation from that. canEdit() on the actual
			// record is only really for editing the record directly, not
			// unlinking it from the parent record's relationship.
			// If there is no parent, it will fallback to checking canEdit()
			// on the record.
			$parent = $gridField->getForm()->getRecord();
			if($parent) {
				if(!$parent->canEdit()) return;
			} else {
				if(!$record->canEdit()) return;
			}

			$field = GridField_FormAction::create($gridField, 'UnlinkRelation'.$record->ID, false,
					"unlinkrelation", array('RecordID' => $record->ID))
				->addExtraClass('gridfield-button-unlink')
				->setAttribute('title', _t('GridAction.UnlinkRelation', "Unlink"))
				->setAttribute('data-icon', 'chain--minus');
		} else {
			if(!$record->canDelete()) return;
			
			$field = GridField_FormAction::create($gridField,  'DeleteRecord'.$record->ID, false, "deleterecord",
					array('RecordID' => $record->ID))
				->addExtraClass('gridfield-button-delete')
				->setAttribute('title', _t('GridAction.Delete', "Delete"))
				->setAttribute('data-icon', 'cross-circle')
				->setDescription(_t('GridAction.DELETE_DESCRIPTION','Delete'));
		}
		return $field->Field();
	}
	
	/**
	 * Handle the actions and apply any changes to the GridField
	 *
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param mixed $arguments
	 * @param array $data - form data
	 * @return void
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'deleterecord' || $actionName == 'unlinkrelation') {
			$item = $gridField->getList()->byID($arguments['RecordID']);
			if(!$item) {
				return;
			}
			
			if($actionName == 'deleterecord') {
				if(!$item->canDelete()) {
					throw new ValidationException(
						_t('GridFieldAction_Delete.DeletePermissionsFailure',"No delete permissions"),0);
				}

				$item->delete();
			} else {
				// check if we can write to the parent, that means we'll be able
				// to unlink the relation from that. canEdit() on the actual
				// record is only really for editing the record directly, not
				// unlinking it from the parent record's relationship.
				// If there is no parent, it will fallback to checking canEdit()
				// on the record.
				$parent = $gridField->getForm()->getRecord();
				$canRemove = false;
				if($parent) {
					$canRemove = $parent->canEdit();
				} else {
					$canRemove = $record->canEdit();
				}

				if(!$canRemove) {
					throw new ValidationException(
						_t('GridFieldAction_Delete.EditPermissionsFailure',"No permission to unlink record"),0);
				}

				$gridField->getList()->remove($item);
			}
		} 
	}
}
