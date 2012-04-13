<?php
class CMSProfileController extends LeftAndMain {

	static $url_segment = 'myprofile';
	static $required_permission_codes = false;

	public function index($request) {
		$form = $this->Member_ProfileForm();
		return $this->customise(array(
			'Content' => ' ',
			'Form' => $form
		))->renderWith('CMSDialog');
	}
	
	public function Member_ProfileForm() {
		return new Member_ProfileForm($this, 'Member_ProfileForm', Member::currentUser());
	}

	function canView($member = null) {
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
