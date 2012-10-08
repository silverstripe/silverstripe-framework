<?php
class CMSProfileController extends LeftAndMain {

	static $url_segment = 'myprofile';
	static $menu_title = 'Member Profile';
	static $required_permission_codes = false;

	public function getEditForm($id = null, $fields = null) {
		$form = new Member_ProfileForm($this, 'EditForm', Member::currentUser());;
		$form->addExtraClass('root-form');
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setHTMLID('Form_EditForm');
		$this->extend('updateEditForm', $form);
		
		return $form;
	}

	public function dosave($data, $form) {
		$form->doSave($data, $form);
		$this->response->addHeader('X-Status', $form->Message());		
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
}
