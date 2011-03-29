<?php
/**
 * Enhances {ComplexTableField} with the ability to list groups and given members.
 * It is based around groups, so it deletes Members from a Group rather than from the entire system.
 *
 * In contrast to the original implementation, the URL-parameters "ParentClass" and "ParentID" are used
 * to specify "Group" (hardcoded) and the GroupID-relation.
 *
 *	@todo write a better description about what this field does.
 *
 * Returns either:
 * - provided members
 * - members of a provided group
 * - all members
 * - members based on a search-query
 * 
 * @package cms
 * @subpackage security
 */
class MemberTableField extends ComplexTableField {
	
	protected $members;
	
	protected $hidePassword;
	
	protected $detailFormValidator;
	
	protected $group;

	protected $template = 'MemberTableField';

	public $popupClass = 'MemberTableField_Popup';
	
	public $itemClass = 'MemberTableField_Item';
	
	static $data_class = 'Member';
	
	/**
	 * Set the page size for this table. 
	 * @var int 
	 */ 
	public static $page_size = 20; 
	
	protected $permissions = array(
		"add",
		"edit",
		"show",
		"delete",
		'inlineadd'
		//"export",
	);

  	/**
  	 * Constructor method for MemberTableField.
  	 * 
  	 * @param Controller $controller Controller class which created this field
  	 * @param string $name Name of the field (e.g. "Members")
  	 * @param mixed $group Can be the ID of a Group instance, or a Group instance itself
  	 * @param DataObjectSet $members Optional set of Members to set as the source items for this field
  	 * @param boolean $hidePassword Hide the password field or not in the summary?
  	 */
	function __construct($controller, $name, $group = null, $members = null, $hidePassword = true) {
		$sourceClass = self::$data_class;
		$SNG_member = singleton($sourceClass);
		$fieldList = $SNG_member->summaryFields();
		$memberDbFields = $SNG_member->db();
		$csvFieldList = array();

		foreach($memberDbFields as $field => $dbFieldType) {
			$csvFieldList[$field] = $field;
		}
		
		if($group) {
			if(is_object($group)) {
				$this->group = $group;
			} elseif(is_numeric($group)) {
				$this->group = DataObject::get_by_id('Group', $group);
			}
		} else if(isset($_REQUEST['ctf'][$this->Name()]["ID"]) && is_numeric($_REQUEST['ctf'][$this->Name()]["ID"])) {
			$this->group = DataObject::get_by_id('Group', $_REQUEST['ctf'][$this->Name()]["ID"]);
		}

		if(!$hidePassword) {
			$fieldList["SetPassword"] = "Password"; 
		}

		$this->hidePassword = $hidePassword;
		
		// @todo shouldn't this use $this->group? It's unclear exactly
		// what group it should be customising the custom Member set with.
		if($members && $group) {
			$this->setCustomSourceItems($this->memberListWithGroupID($members, $group));
		}

		parent::__construct($controller, $name, $sourceClass, $fieldList);
		
		$SQL_search = isset($_REQUEST['MemberSearch']) ? Convert::raw2sql($_REQUEST['MemberSearch']) : null;
		if(!empty($_REQUEST['MemberSearch'])) {
			$searchFilters = array();
			foreach($SNG_member->searchableFields() as $fieldName => $fieldSpec) {
				if(strpos($fieldName, '.') === false) $searchFilters[] = "\"$fieldName\" LIKE '%{$SQL_search}%'";
			}
			$this->sourceFilter[] = '(' . implode(' OR ', $searchFilters) . ')';
		}
		
		if($this->group) {
			$groupIDs = array($this->group->ID);
			if($this->group->AllChildren()) $groupIDs = array_merge($groupIDs, $this->group->AllChildren()->column('ID'));
			$this->sourceFilter[] = sprintf(
				'"Group_Members"."GroupID" IN (%s)', 
				implode(',', $groupIDs)
			);
		}

		$this->sourceJoin = " INNER JOIN \"Group_Members\" ON \"MemberID\"=\"Member\".\"ID\"";
		$this->setFieldListCsv($csvFieldList);
		$this->setPageSize($this->stat('page_size'));
	}
	
	function FieldHolder() {
		$ret = parent::FieldHolder();
		
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/scriptaculous/controls.js");
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/MemberTableField.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . "/javascript/MemberTableField_popup.js");
		
		return $ret;
	}

	function sourceID() {
		return ($this->group) ? $this->group->ID : 0;
	}

	function AddLink() {
		return Controller::join_links($this->Link(), 'add');
	}

	function SearchForm() {
		$groupID = (isset($this->group)) ? $this->group->ID : 0;
		$query = isset($_GET['MemberSearch']) ? $_GET['MemberSearch'] : null;
		
		$searchFields = new FieldGroup(
			new TextField('MemberSearch', _t('MemberTableField.SEARCH', 'Search'), $query),
			new HiddenField("ctf[ID]", '', $groupID),
			new HiddenField('MemberFieldName', '', $this->name),
			new HiddenField('MemberDontShowPassword', '', $this->hidePassword)
		);

		$actionFields = new LiteralField('MemberFilterButton','<input type="submit" class="action" name="MemberFilterButton" value="'._t('MemberTableField.FILTER', 'Filter').'" id="MemberFilterButton"/>');

		$fieldContainer = new FieldGroup(
			$searchFields,
			$actionFields
		);

		return $fieldContainer->FieldHolder();
	}

	/**
	 * Add existing member to group rather than creating a new member
	 */
	function addtogroup() {
		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		if(!$token->checkRequest($this->controller->getRequest())) return $this->httpError(400);

		$data = $_REQUEST;
		$groupID = (isset($data['ctf']['ID'])) ? $data['ctf']['ID'] : null;

		if(!is_numeric($groupID)) {
			FormResponse::status_messsage(_t('MemberTableField.ADDINGFIELD', 'Adding failed'), 'bad');
			return;
		}

		// Get existing record either by ID or unique identifier.
		$identifierField = Member::get_unique_identifier_field();
		$className = self::$data_class;
		$record = null;
		if(isset($data[$identifierField])) {
			$record = DataObject::get_one(
				$className, 
				sprintf('"%s" = \'%s\'', $identifierField, $data[$identifierField])
			);
			
			if($record && !$record->canEdit()) return $this->httpError('401');
		} 
			
		// Fall back to creating a new record
		if(!$record) $record = new $className();
		
		// Update an existing record, or populate a new one.
		// If values on an existing (autocompleted) record have been changed,
		// they will overwrite current data. We need to unset 'ID'
		// record as it points to the group rather than the member record, and would
		// cause the member to be written to a potentially existing record.
		unset($data['ID']);
		$record->update($data);
		
		// Validate record, mainly password restrictions.
		// Note: Doesn't use Member_Validator
		$valid = $record->validate();
		if($valid->valid()) {
			$record->write();
			$record->Groups()->add($groupID);

			$this->sourceItems();

			// TODO add javascript to highlight added row (problem: might not show up due to sorting/filtering)
			FormResponse::update_dom_id($this->id(), $this->renderWith($this->template), true);
			FormResponse::status_message(
				_t(
					'MemberTableField.ADDEDTOGROUP','Added member to group'
				),
				'good'
			);
		
		} else {
			$message = sprintf(
				_t(
					'MemberTableField.ERRORADDINGUSER',
					'There was an error adding the user to the group: %s'
				),
				Convert::raw2xml($valid->starredList())
			);
			
			FormResponse::status_message($message, 'bad');
		}

		return FormResponse::respond();
	}

	/**
	 * Custom delete implementation:
	 * Remove member from group rather than from the database
	 */
	function delete() {
		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		// TODO Not sure how this is called, using $_REQUEST to be on the safe side
		if(!$token->check($_REQUEST['SecurityID'])) return $this->httpError(400);
		
		$groupID = Convert::raw2sql($_REQUEST['ctf']['ID']);
		$memberID = Convert::raw2sql($_REQUEST['ctf']['childID']);
		if(is_numeric($groupID) && is_numeric($memberID)) {
			$member = DataObject::get_by_id('Member', $memberID);
			$member->Groups()->remove($groupID);
		} else {
			user_error("MemberTableField::delete: Bad parameters: Group=$groupID, Member=$memberID", E_USER_ERROR);
		}

		return FormResponse::respond();

	}

	/**
	 * #################################
	 *           Utility Functions
	 * #################################
	 */
	function getParentClass() {
		return 'Group';
	}

	function getParentIdName($childClass, $parentClass) {
		return 'GroupID';
	}

	/**
	 * #################################
	 *           Custom Functions
	 * #################################
	 */
	
	/**
	 * Customise an existing DataObjectSet of Member
	 * objects with a GroupID.
	 * 
	 * @param DataObjectSet $members Set of Member objects to customise
	 * @param Group $group Group object to customise with
	 * @return DataObjectSet Customised set of Member objects
	 */
	function memberListWithGroupID($members, $group) {
		$newMembers = new DataObjectSet();
		foreach($members as $member) {
			$newMembers->push($member->customise(array('GroupID' => $group->ID)));
		}
		return $newMembers;
	}

	function setGroup($group) {
		$this->group = $group;
	}
	
	/**
	 * @return Group
	 */
	function getGroup() {
		return $this->group;
	}
	
	function setController($controller) {
		$this->controller = $controller;
	}

	function GetControllerName() {
		return $this->controller->class;
	}

	/**
	 * Add existing member to group by name (with JS-autocompletion)
	 */
	function AddRecordForm() {
		$fields = new FieldSet();
		foreach($this->FieldList() as $fieldName => $fieldTitle) {
			// If we're adding the set password field, we want to hide the text from any peeping eyes
			if($fieldName == 'SetPassword') {
				$fields->push(new PasswordField($fieldName));
			} else {
				$fields->push(new TextField($fieldName));
			}
		}
		if($this->group) {
			$fields->push(new HiddenField('ctf[ID]', null, $this->group->ID));
		}
		$actions = new FieldSet(
			new FormAction('addtogroup', _t('MemberTableField.ADD','Add'))
		);
		
		return new TabularStyle(
			new NestedForm(
				new Form(
					$this,
					'AddRecordForm',
					$fields,
					$actions
				)
			)
		);
	}
	
	function AddForm() {
		$form = parent::AddForm();
		
		// Set default groups - also implemented in MemberTableField_Popup::__construct()
		if($this->group) {
			$groupsField = $form->Fields()->dataFieldByName('Groups');
			// TODO Needs to be a string value (not int) because of TreeMultiselectField->getItems(),
			// see http://open.silverstripe.org/ticket/5836
			if($groupsField) $groupsField->setValue((string)$this->group->ID);
		}
		
		return $form;
	}

	/**
	 * Same behaviour as parent class, but adds the
	 * member to the passed GroupID.
	 *
	 * @return string
	 */
	function saveComplexTableField($data, $form, $params) {		
		$className = $this->sourceClass();
		$childData = new $className();
		
		// Needs to write before saveInto() to ensure the 'Groups' TreeMultiselectField saves
		$childData->write();
		
		try {
			$form->saveInto($childData);
			$childData->write();
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			return Director::redirectBack();
		}
		
		$closeLink = sprintf(
			'<small><a href="' . $_SERVER['HTTP_REFERER'] . '" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		$message = sprintf(
			_t('ComplexTableField.SUCCESSADD', 'Added %s %s %s'),
			$childData->singular_name(),
			'<a href="' . $this->Link() . '">' . htmlspecialchars($childData->Title, ENT_QUOTES, 'UTF-8') . '</a>',
			$closeLink
		);
		$form->sessionMessage($message, 'good');

		$this->controller->redirectBack();
	}	
	
	/**
	 * Cached version for getting the appropraite members for this particular group.
	 *
	 * This includes getting inherited groups, such as groups under groups.
	 */
	function sourceItems() {
		// Caching.
		if($this->sourceItems) {
			return $this->sourceItems;
		}

		// Setup limits
		$limitClause = '';
		if(isset($_REQUEST['ctf'][$this->Name()]['start']) && is_numeric($_REQUEST['ctf'][$this->Name()]['start'])) {
			$limitClause = ($_REQUEST['ctf'][$this->Name()]['start']) . ", {$this->pageSize}";
		} else {
			$limitClause = "0, {$this->pageSize}";
		}
				
		// We use the group to get the members, as they already have the bulk of the look up functions
		$start = isset($_REQUEST['ctf'][$this->Name()]['start']) ? $_REQUEST['ctf'][$this->Name()]['start'] : 0; 
		
		$this->sourceItems = false;

		if($this->group) {
			$this->sourceItems = $this->group->Members( 
				$this->pageSize, // limit 
				$start, // offset 
				$this->sourceFilter,
				$this->sourceSort
			);	
		} else {
			$this->sourceItems = DataObject::get(self::$data_class,
				$this->sourceFilter,
				$this->sourceSort,
				null,
				array('limit' => $this->pageSize, 'start' => $start)
			);
		}
		// Because we are not used $this->upagedSourceItems any more, and the DataObjectSet is usually the source
		// that a large member set runs out of memory. we disable it here.
		//$this->unpagedSourceItems = $this->group->Members('', '', $this->sourceFilter, $this->sourceSort);
		$this->totalCount = ($this->sourceItems) ? $this->sourceItems->TotalItems() : 0;
		
		return $this->sourceItems;
	}

	function TotalCount() {
		$this->sourceItems(); // Called for its side-effect of setting total count
		return $this->totalCount;
	}

	/**
	 * Handles item requests
	 * MemberTableField needs its own item request class so that it can overload the delete method
	 */
	function handleItem($request) {
		return new MemberTableField_ItemRequest($this, $request->param('ID'));
	}
}

/**
 * Popup window for {@link MemberTableField}.
 * @package cms
 * @subpackage security
 */
class MemberTableField_Popup extends ComplexTableField_Popup {

	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		$group = ($controller instanceof MemberTableField) ? $controller->getGroup() : $controller->getParent()->getGroup();
		// Set default groups - also implemented in AddForm()
		if($group) {
			$groupsField = $fields->dataFieldByName('Groups');
			if($groupsField) $groupsField->setValue($group->ID);
		}
		
		parent::__construct($controller, $name, $fields, $validator, $readonly, $dataObject);
	}
	
	function forTemplate() {
		$ret = parent::forTemplate();
		
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/SecurityAdmin.css');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/MemberTableField.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/MemberTableField_popup.js');
		
		return $ret;
	}

}

/**
* @package cms
* @subpackage security
*/
class MemberTableField_Item extends ComplexTableField_Item {

	function Actions() {
		$actions = parent::Actions();
		
		foreach($actions as $action) {
			if($action->Name == 'delete') {
				if($this->parent->getGroup()) {
					$action->TitleText = _t('MemberTableField.DeleteTitleText',
						'Delete from this group',
						PR_MEDIUM,
						'Delete button hover text'
					);
				} else {
					$action->TitleText = _t('MemberTableField.DeleteTitleTextDatabase',
						'Delete from database and all groups',
						PR_MEDIUM,
						'Delete button hover text'
					);
				}
			}
		}

		return $actions;
	}
}

/**
* @package cms
* @subpackage security
*/

class MemberTableField_ItemRequest extends ComplexTableField_ItemRequest {

	/**
	 * Deleting an item from a member table field should just remove that member from the group
	 */
	function delete($request) {
		// Protect against CSRF on destructive action
		$token = $this->ctf->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError('400');
		
		if($this->ctf->Can('delete') !== true) {
			return false;
		}

		// if a group limitation is set on the table, remove relation.
		// otherwise remove the record from the database
		if($this->ctf->getGroup()) {
			$groupID = $this->ctf->sourceID();
			$group = DataObject::get_by_id('Group', $groupID);
			
			// Remove from group and all child groups
			foreach($group->getAllChildren() as $subGroup) {
				$this->dataObj()->Groups()->remove($subGroup);
			}
			$this->dataObj()->Groups()->remove($groupID);
		} else {
			$this->dataObj()->delete();
		}	
	}
	
	function getParent() {
		return $this->ctf;
	}
}

?>