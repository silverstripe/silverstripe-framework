<?php

/**
 * @package framework
 * @subpackage admin
 */
class CMSProfileController extends LeftAndMain {

	private static $url_segment = 'myprofile';

	private static $menu_title = 'My Profile';

	private static $required_permission_codes = false;

	private static $tree_class = 'Member';

	public function getEditForm($id = null, $fields = null) {
		$this->setCurrentPageID(Member::currentUserID());

		$form = parent::getEditForm($id, $fields);
		
		if($form instanceof SS_HTTPResponse) {
			return $form;
		}

		$form->Fields()->removeByName('LastVisited');
		$form->Fields()->push(new HiddenField('ID', null, Member::currentUserID()));
		$form->Actions()->push(
			FormAction::create('save',_t('CMSMain.SAVE', 'Save'))
				->addExtraClass('ss-ui-button ss-ui-action-constructive')
				->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true)
		);

		$form->Actions()->removeByName('action_delete');

		if($member = Member::currentUser()) {
			$form->setValidator($member->getValidator());
		} else {
			$form->setValidator(Injector::inst()->get('Member')->getValidator());
		}

		if($form->Fields()->hasTabset()) {
			$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		}

		$form->addExtraClass('member-profile-form root-form cms-edit-form center');
		
		return $form;
	}

	public function canView($member = null) {
		if(!$member && $member !== FALSE) $member = Member::currentUser();
		
		// cms menus only for logged-in members
		if(!$member) return false;
		
		// Only check for generic CMS permissions
		if(
			!Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")
			&& !Permission::checkMember($member, "CMS_ACCESS_CMSMain")
		) {
			return false;
		}
		
		return true;
	}

	public function save($data, $form) {
		$member = DataObject::get_by_id("Member", $data['ID']);
		if(!$member) return $this->httpError(404);
		$origLocale = $member->Locale;

		if(!$member->canEdit()) {
			$form->sessionMessage(_t('Member.CANTEDIT', 'You don\'t have permission to do that'), 'bad');
			return $this->redirectBack();
		}

		$response = parent::save($data, $form);

		if($origLocale != $data['Locale']) {
			$response->addHeader('X-Reload', true);
			$response->addHeader('X-ControllerURL', $this->Link());
		}
		
		return $response;
	}

	/**
	 * Only show first element, as the profile form is limited to editing
	 * the current member it doesn't make much sense to show the member name
	 * in the breadcrumbs.
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);
		return new ArrayList(array($items[0]));
	}

}
