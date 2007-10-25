<?php

/**
 * OpenID authentication decorator
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */



/**
 * Decorator for the member class to support OpenID authentication
 *
 * This class adds the needed fields to the default member class to support
 * authentication via OpenID.
 *
 * @author Markus Lanthaler <markus@silverstripe.com
 */
class OpenIDAuthenticatedRole extends DataObjectDecorator {

	/**
	 * Define extra database fields
	 *
	 * Returns a map where the keys are db, has_one, etc, and the values are
	 * additional fields/relations to be defined
	 *
	 * @return array Returns a map where the keys are db, has_one, etc, and
	 *               the values are additional fields/relations to be defined
	 */
	function extraDBFields() {
		return array(
			'db' => array('IdentityURL' => 'Varchar(255)'),
			'has_one' => array(),
			'defaults' => array('IdentityURL' => null),
			'indexes' => array('IdentityURL' => 'unique (IdentityURL)')
		);
	}


	/**
	 * Edit the given query object to support queries for this extension
	 *
	 * At the moment this method does nothing.
	 *
	 * @param SQLQuery $query Query to augment.
	 */
	function augmentSQL(SQLQuery &$query) {
	}


	/**
	 * Update the database schema as required by this extension
	 *
	 * At the moment this method does nothing.
	 */
	function augmentDatabase() {
	}


	/**
	 * Change the member dialog in the CMS
	 *
	 * This method updates the form in the member dialog to make it possible
	 * to edit the new database fields.
	 */
	function updateCMSFields(FieldSet &$fields) {
		$fields->push(
			new HeaderField(_t('Security.OPENIDHEADER', "OpenID/i-name credentials")), 
			"OpenIDHeader"
		);
		$fields->push(
			new LiteralField("OpenIDDescription",
				_t('Security.OPENIDDESC',"<p>Make sure you enter your normalized OpenID/i-name credentials 
				here, i.e. with protocol and trailing slash for OpenID (e.g. http://openid.silverstripe.com/).</p>")
			)
		);
		$fields->push(
			new TextField("IdentityURL", _t('Security.OPENIDURL', "OpenID URL/i-name")),
			"IdentityURL"
		);
	}


	/**
	 * Can the current user edit the given member?
	 *
	 * Only the user itself or an administrator can edit an user account.
	 *
	 * @return bool Returns TRUE if this member can be edited, FALSE otherwise
	 */
	function canEdit() {
		if($this->owner->ID == Member::currentUserID())
			return true;

		$member = Member::currentUser();
		if($member)
			return $member->isAdmin();

		return false;
	}
}



/**
 * Validator of the decorator for the member class to support OpenID
 * authentication
 *
 * @author Markus Lanthaler <markus@silverstripe.com
 */
class OpenIDAuthenticatedRole_Validator extends Extension {

	/**
	 * Server-side validation
	 *
	 * This method checks if the entered identity URL is unique.
	 *
	 * @param array $data User submitted data
	 * @param Form $form The used form
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function updatePHP(array $data, Form &$form) {
		if(!isset($data['IdentityURL']) || strlen(trim($data['IdentityURL'])) == 0)
			return true;

		$member = DataObject::get_one('Member',
			"IdentityURL = '". Convert::raw2sql($data['IdentityURL']) ."'");

		// if we are in a complex table field popup, use ctf[childID], else use
		// ID
		$id = null;
		if(isset($_REQUEST['ctf']['childID'])) {
			$id = $_REQUEST['ctf']['childID'];
		} elseif(isset($_REQUEST['ID'])) {
			$id = $_REQUEST['ID'];
		}

		if(is_object($member) && $member->ID != $id) {
			$field = $form->dataFieldByName('IdentityURL');
			$this->owner->validationError($field->id(),
				_t('Security.MEMBERALREADYEXISTS', "There already exists a member with this identity URL"),
				"required");
			return false;
		}

		return true;
	}
}


?>