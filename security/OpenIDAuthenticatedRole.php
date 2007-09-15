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
			'indexes' => array('IdentityURL', 'unique (IdentityURL)')
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
		$fields->push(new HeaderField("OpenID/i-name credentials"), "OpenIDHeader");
		$fields->push(new LiteralField("OpenIDDescription",
			"<p>Make sure you enter your normalized OpenID/i-name credentials " .
			"here, i.e. with protocol and trailing slash for OpenID (e.g. " .
			"http://openid.silverstripe.com/).</p>"));
		$fields->push(new TextField("IdentityURL", "OpenID URL/i-name"),
									              "IdentityURL");
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


?>