<?php

/**
 * Security section of the CMS
 *
 * @package framework
 * @subpackage admin
 */
class SecurityAdmin extends LeftAndMain implements PermissionProvider {

	private static $url_segment = 'security';

	private static $url_rule = '/$Action/$ID/$OtherID';

	private static $menu_title = 'Security';

	private static $tree_class = 'Group';

	private static $subitem_class = 'Member';

	private static $allowed_actions = array(
		'EditForm',
		'MemberImportForm',
		'memberimport',
		'GroupImportForm',
		'groupimport',
		'groups',
		'users',
		'roles'
	);

	public function init() {
		parent::init();
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/SecurityAdmin.js');
	}

	/**
	 * Shortcut action for setting the correct active tab.
	 */
	public function users($request) {
		return $this->index($request);
	}

	/**
	 * Shortcut action for setting the correct active tab.
	 */
	public function groups($request) {
		return $this->index($request);
	}

	/**
	 * Shortcut action for setting the correct active tab.
	 */
	public function roles($request) {
		return $this->index($request);
	}

	public function getEditForm($id = null, $fields = null) {
		// TODO Duplicate record fetching (see parent implementation)
		if(!$id) $id = $this->currentPageID();
		$form = parent::getEditForm($id);

		// TODO Duplicate record fetching (see parent implementation)
		$record = $this->getRecord($id);

		if($record && !$record->canView()) {
			return Security::permissionFailure($this);
		}

		$memberList = GridField::create(
			'Members',
			false,
			Member::get(),
			$memberListConfig = GridFieldConfig_RecordEditor::create()
				->addComponent(new GridFieldButtonRow('after'))
				->addComponent(new GridFieldExportButton('buttons-after-left'))
		)->addExtraClass("members_grid");

		if($record && method_exists($record, 'getValidator')) {
			$validator = $record->getValidator();
		} else {
			$validator = Injector::inst()->get('Member')->getValidator();
		}

		$memberListConfig
			->getComponentByType('GridFieldDetailForm')
			->setValidator($validator);

		$groupList = GridField::create(
			'Groups',
			false,
			Group::get(),
			GridFieldConfig_RecordEditor::create()
		);
		$columns = $groupList->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields(array(
			'Breadcrumbs' => singleton('Group')->fieldLabel('Title')
		));
		$columns->setFieldFormatting(array(
			'Breadcrumbs' => function($val, $item) {
				return Convert::raw2xml($item->getBreadcrumbs(' > '));
			}
		));

		$fields = new FieldList(
			$root = new TabSet(
				'Root',
				$usersTab = new Tab('Users', _t('SecurityAdmin.Users', 'Users'),
					$memberList,
					new LiteralField('MembersCautionText',
						sprintf('<p class="caution-remove"><strong>%s</strong></p>',
							_t(
								'SecurityAdmin.MemberListCaution',
								'Caution: Removing members from this list will remove them from all groups and the'
									. ' database'
							)
						)
					)
				),
				$groupsTab = new Tab('Groups', singleton('Group')->i18n_plural_name(),
					$groupList
				)
			),
			// necessary for tree node selection in LeftAndMain.EditForm.js
			new HiddenField('ID', false, 0)
		);

		// Add import capabilities. Limit to admin since the import logic can affect assigned permissions
		if(Permission::check('ADMIN')) {
			$fields->addFieldsToTab('Root.Users', array(
				new HeaderField(_t('SecurityAdmin.IMPORTUSERS', 'Import users'), 3),
				new LiteralField(
					'MemberImportFormIframe',
					sprintf(
							'<iframe src="%s" id="MemberImportFormIframe" width="100%%" height="250px" frameBorder="0">'
						. '</iframe>',
						$this->Link('memberimport')
					)
				)
			));
			$fields->addFieldsToTab('Root.Groups', array(
				new HeaderField(_t('SecurityAdmin.IMPORTGROUPS', 'Import groups'), 3),
				new LiteralField(
					'GroupImportFormIframe',
					sprintf(
							'<iframe src="%s" id="GroupImportFormIframe" width="100%%" height="250px" frameBorder="0">'
						. '</iframe>',
						$this->Link('groupimport')
					)
				)
			));
		}

		// Tab nav in CMS is rendered through separate template
		$root->setTemplate('CMSTabSet');

		// Add roles editing interface
		if(Permission::check('APPLY_ROLES')) {
			$rolesField = GridField::create('Roles',
				false,
				PermissionRole::get(),
				GridFieldConfig_RecordEditor::create()
			);

			$rolesTab = $fields->findOrMakeTab('Root.Roles', _t('SecurityAdmin.TABROLES', 'Roles'));
			$rolesTab->push($rolesField);
		}

		$actionParam = $this->getRequest()->param('Action');
		if($actionParam == 'groups') {
			$groupsTab->addExtraClass('ui-state-active');
		} elseif($actionParam == 'users') {
			$usersTab->addExtraClass('ui-state-active');
		} elseif($actionParam == 'roles') {
			$rolesTab->addExtraClass('ui-state-active');
		}

		$actions = new FieldList();

		$form = CMSForm::create(
			$this,
			'EditForm',
			$fields,
			$actions
		)->setHTMLID('Form_EditForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
		$form->addExtraClass('cms-edit-form');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		// Tab nav in CMS is rendered through separate template
		if($form->Fields()->hasTabset()) {
			$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		}
		$form->addExtraClass('center ss-tabset cms-tabset ' . $this->BaseCSSClasses());
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}

	public function memberimport() {
		Requirements::clear();
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/MemberImportForm.css');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/MemberImportForm.js');

		return $this->renderWith('BlankPage', array(
			'Form' => $this->MemberImportForm()->forTemplate(),
			'Content' => ' '
		));
	}

	/**
	 * @see SecurityAdmin_MemberImportForm
	 *
	 * @return Form
	 */
	public function MemberImportForm() {
		if(!Permission::check('ADMIN')) return false;

		$group = $this->currentPage();
		$form = new MemberImportForm(
			$this,
			'MemberImportForm'
		);
		$form->setGroup($group);

		return $form;
	}

	public function groupimport() {
		Requirements::clear();
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/MemberImportForm.css');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/MemberImportForm.js');

		return $this->renderWith('BlankPage', array(
			'Content' => ' ',
			'Form' => $this->GroupImportForm()->forTemplate()
		));
	}

	/**
	 * @see SecurityAdmin_MemberImportForm
	 *
	 * @return Form
	 */
	public function GroupImportForm() {
		if(!Permission::check('ADMIN')) return false;

		$form = new GroupImportForm(
			$this,
			'GroupImportForm'
		);

		return $form;
	}

	/**
	 * Disable GridFieldDetailForm backlinks for this view, as its
	 */
	public function Backlink() {
		return false;
	}

	public function Breadcrumbs($unlinked = false) {
		$crumbs = parent::Breadcrumbs($unlinked);

		// Name root breadcrumb based on which record is edited,
		// which can only be determined by looking for the fieldname of the GridField.
		// Note: Titles should be same titles as tabs in RootForm().
		$params = $this->getRequest()->allParams();
		if(isset($params['FieldName'])) {
			// TODO FieldName param gets overwritten by nested GridFields,
			// so shows "Members" rather than "Groups" for the following URL:
			// admin/security/EditForm/field/Groups/item/2/ItemEditForm/field/Members/item/1/edit
			$firstCrumb = $crumbs->shift();
			if($params['FieldName'] == 'Groups') {
				$crumbs->unshift(new ArrayData(array(
					'Title' => singleton('Group')->i18n_plural_name(),
					'Link' => $this->Link('groups')
				)));
			} elseif($params['FieldName'] == 'Users') {
				$crumbs->unshift(new ArrayData(array(
					'Title' => _t('SecurityAdmin.Users', 'Users'),
					'Link' => $this->Link('users')
				)));
			} elseif($params['FieldName'] == 'Roles') {
				$crumbs->unshift(new ArrayData(array(
					'Title' => _t('SecurityAdmin.TABROLES', 'Roles'),
					'Link' => $this->Link('roles')
				)));
			}
			$crumbs->unshift($firstCrumb);
		}

		return $crumbs;
	}

	public function providePermissions() {
		$title = _t("SecurityAdmin.MENUTITLE", LeftAndMain::menu_title_for_class($this->class));
		return array(
			"CMS_ACCESS_SecurityAdmin" => array(
				'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'SecurityAdmin.ACCESS_HELP',
					'Allow viewing, adding and editing users, as well as assigning permissions and roles to them.'
				)
			),
			'EDIT_PERMISSIONS' => array(
				'name' => _t('SecurityAdmin.EDITPERMISSIONS', 'Manage permissions for groups'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SecurityAdmin.EDITPERMISSIONS_HELP',
					'Ability to edit Permissions and IP Addresses for a group.'
					. ' Requires the "Access to \'Security\' section" permission.'),
				'sort' => 0
			),
			'APPLY_ROLES' => array(
				'name' => _t('SecurityAdmin.APPLY_ROLES', 'Apply roles to groups'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SecurityAdmin.APPLY_ROLES_HELP', 'Ability to edit the roles assigned to a group.'
					. ' Requires the "Access to \'Users\' section" permission.'),
				'sort' => 0
			)
		);
	}

	/**
	 * The permissions represented in the $codes will not appearing in the form
	 * containing {@link PermissionCheckboxSetField} so as not to be checked / unchecked.
	 *
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 * @param $codes String|Array
	 */
	public static function add_hidden_permission($codes){
		if(is_string($codes)) $codes = array($codes);
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->update('Permission', 'hidden_permissions', $codes);
	}

	/**
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 * @param $codes String|Array
	 */
	public static function remove_hidden_permission($codes){
		if(is_string($codes)) $codes = array($codes);
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->remove('Permission', 'hidden_permissions', $codes);
	}

	/**
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 * @return Array
	 */
	public static function get_hidden_permissions(){
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->get('Permission', 'hidden_permissions', Config::FIRST_SET);
	}

	/**
	 * Clear all permissions previously hidden with {@link add_hidden_permission}
	 *
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 */
	public static function clear_hidden_permissions(){
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->remove('Permission', 'hidden_permissions', Config::anything());
	}
}
