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
class OpenIDAuthenticatedRole extends DataObjectDecorator{

	/**
	 * Edit the given query object to support queries for this extension
	 */
	function augmentSQL(SQLQuery &$query) {
	}


	/**
	 * Update the database schema as required by this extension
	 */
	function augmentDatabase() {
/*		if(Permission::check('ADMIN')) {
			$exist = DB::query( "SHOW TABLES LIKE 'ForumMember'" )->numRecords();

			if($exist > 0) {
				DB::query( "UPDATE `Member`, `ForumMember` " .
					"SET `Member`.`ClassName` = 'Member'," . "`Member`.`ForumRank` =
					`ForumMember`.`ForumRank`," . "`Member`.`Occupation` =
					`ForumMember`.`Occupation`," . "`Member`.`Country` =
					`ForumMember`.`Country`," . "`Member`.`Nickname` =
					`ForumMember`.`Nickname`," . "`Member`.`FirstNamePublic` =
					`ForumMember`.`FirstNamePublic`," . "`Member`.`SurnamePublic` =
					`ForumMember`.`SurnamePublic`," . "`Member`.`OccupationPublic` =
					`ForumMember`.`OccupationPublic`," . "`Member`.`CountryPublic` =
					`ForumMember`.`CountryPublic`," . "`Member`.`EmailPublic` =
					`ForumMember`.`EmailPublic`," . "`Member`.`AvatarID` =
					`ForumMember`.`AvatarID`," . "`Member`.`LastViewed` =
					`ForumMember`.`LastViewed`" . "WHERE `Member`.`ID` =
					`ForumMember`.`ID`"
				);
				echo( "<div style=\"padding:5px; color:white;
					background-color:blue;\">The data transfer has succeeded. However,
					to complete it, you must delete the ForumMember table. To do this,
					execute the query \"DROP TABLE 'ForumMember'\".</div>" );
			}
		}*/
	}


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
	 * Change the member dialog in the CMS
	 *
	 * This method updates the form in the member dialog to make it possible
	 * to edit the new database fields.
	 */
	function updateCMSFields(FieldSet &$fields) {
		//if(Permission::checkMember($this->owner->ID, "ACCESS_FORUM")) {
			$fields->push(new HeaderField("OpenID/i-name credentials"), "OpenIDHeader");
			$fields->push(new LiteralField("OpenIDDescription",
				"<p>Make sure you enter your normalized OpenID/i-name credentials here, i.e. with protocol and trailing slash for OpenID (e.g. http://openid.silverstripe.com/).</p>"));
			$fields->push(new TextField("IdentityURL", "OpenID URL/i-name"), "IdentityURL");

/*
			$fields->push(new PasswordField("ConfirmPassword", "Confirm Password"));
			$fields->push(new ImageField("Avatar", "Upload avatar"));
			$fields->push(new DropdownField("ForumRank", "User rating",
																			array("Community Member" => "Community Member",
																						"Administrator" => "Administrator",
																						"Moderator" => "Moderator",
																						"SilverStripe User" => "SilverStripe User",
																						"SilverStripe Developer" => "SilverStripe Developer",
																						"Core Development Team" => "Core Development Team",
																						"Google Summer of Code Hacker" => "Google Summer of Code Hacker",
																						"Lead Developer" => "Lead Developer")
																			)
										);
		}*/
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




	/**
	 * Factory method for the member validator
	 *
	 * @return Member_Validator Returns an instance of a
	 *                          {@link Member_Validator} object.
	 */
	function getValidator() {
		die('<p style="color: red;">Called getValidator()</p>');
		return new Member_Validator();
	}
}


?>