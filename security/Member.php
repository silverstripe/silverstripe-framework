<?php
class Member extends DataObject {
	
	static $db = array(
		'FirstName' => "Varchar",
		'Surname' => "Varchar",
		'Email' => "Varchar",
		'Password' => "Varchar",
		'NumVisit' => "Int",
		'LastVisited' => 'Datetime',
   		'Bounced' => 'Boolean',
   		'AutoLoginHash' => 'Varchar(10)',
   		'AutoLoginExpired' => 'Datetime',
   		'BlacklistedEmail' => 'Boolean',
	);
	static $belongs_many_many = array(
		"Groups" => "Group",

	);
	static $has_one = array(
	);
  
	static $has_many = array(
		'UnsubscribedRecords' => 'Member_UnsubscribeRecord'
	);
  	static $many_many = array();
  	static $many_many_extraFields = array();
	static $default_sort = "Surname, FirstName";
	
	static $indexes = array(
		'Email' => true,
	);
	
	/**
	 * Logs this member in.
	 */
	function logIn() {
		Session::set("loggedInAs", $this->ID);
		$this->NumVisit++;
		$this->write();
	}
	
	/**
	 *Logs this member in.
	 */
	function logOut(){
		Cookie::set('alc_enc',null);
		Session::clear("loggedInAs");
	}
	
	function generateAutologinHash() {
		$linkHash = sprintf('%10d', time() );
		
		while( DataObject::get_one( 'Member', "`AutoLoginHash`='$linkHash'" ) )
			$linkHash = sprintf('%10d', abs( time() * rand( 1, 10 ) ) );
			
		$this->AutoLoginHash = $linkHash;
		$this->AutoLoginExpired = date('Y-m-d', time() + ( 60 * 60 * 24 * 14 ) );
		
		$this->write();
	}
	
	/**
	 * Log a member in with an auto login hash link
	 */
	static function autoLoginHash( $RAW_hash ) {
		
		$SQL_hash = Convert::raw2sql( $RAW_hash );
		
		$member = DataObject::get_one('Member',"`AutoLoginHash`='$SQL_hash' AND `AutoLoginExpired` > NOW()");
		
		if( $member )
			$member->logIn();
			
		return $member;
	}
	
	function sendInfo($type = 'signup'){
		switch($type) {
			case "signup": $e = new Member_SignupEmail(); break;
			case "changePassword": $e = new Member_ChangePasswordEmail(); break;
			case "forgotPassword": $e = new Member_ForgotPasswordEmail(); break;
		}
		$e->populateTemplate($this);
		$e->send();
	}
	
	function getMemberFormFields() {
		return new FieldSet(
			new TextField("FirstName", "First Name"),
			new TextField("Surname", "Surname"),
			new TextField("Email", "Email"),
			new TextField("Password", "Password")
		);
	}
	
	function getValidator() {
		return new Member_Validator();
	}

	/**
	 * Returns the currenly logged in user
	 * @todo get_one() is a bit funky.
	 */
	static function currentUser() {
		self::autoLogin();
		
		// Return the details
		if($id = Session::get("loggedInAs")) {
			return DataObject::get_one("Member", "Member.ID = $id");
		}
	}

	static function autoLogin() {
		// Auto-login
		if(isset($_COOKIE['alc_enc']) && !Session::get("loggedInAs")) {
			// Deliberately obscure...
			list($data['Email'], $data['Password']) = explode(':',base64_decode($_COOKIE['alc_enc']),2);

			$lf = new LoginForm(null, null, null, null, false);
			$lf->performLogin($data);
		
		}
	}
	
	static function currentUserID() {
		self::autoLogin();

		$id = Session::get("loggedInAs");
		return is_numeric($id) ? $id : 0;
	}
	
	/**
	 * before the save of this member, the blacklisted email table is updated to ensure no
	 * promotional material is sent to the member. (newsletters)
	 * standard system messages are still sent such as receipts.
	 */
	function setBlacklistedEmail($val){
		if($val && $this->Email){
			$blacklisting = new Email_BlackList();
	 		$blacklisting->BlockedEmail = $this->Email;
	 		$blacklisting->MemberID = $this->ID;
	 		$blacklisting->write();
		}
		return $this->setField("BlacklistedEmail",$val);
	}

	function onBeforeWrite() {
		// If an email's filled out
		if($this->Email) {
			// Look for a record with the same email
			if($this->ID) $idClause = "AND `Member`.ID <> $this->ID";
			else $idClause = "";
			
			$existingRecord = DataObject::get_one("Member", "Email = '" . addslashes($this->Email) . "' $idClause");
			
			// Debug::message("Found an existing member for email $this->Email");
			
			// If found
			if($existingRecord) {
				// Update this record to merge with that member
				$newID = $existingRecord->ID;
				if($this->ID) {
					DB::query("UPDATE Group_Members SET MemberID = $newID WHERE MemberID = $this->ID");
				}
				$this->ID = $newID;
				// Merge existing data into the local record
				
				foreach($existingRecord->getAllFields() as $k => $v) {
					if(!isset($this->changed[$k]) || !$this->changed[$k]) $this->record[$k] = $v;
				}
			}
		}
		
		parent::onBeforeWrite();
	}
	
	/**
	 * Check if the member is in one of the given groups
	 */
	public function inGroups( $groups ) {
		foreach( $this->Groups() as $group )
			$memberGroups[] = $group->Title;
		
		return count( array_intersect( $memberGroups, $groups ) ) > 0;
	}
    
    public function inGroup( $groupID ) {
        foreach( $this->Groups() as $group )
            if( $groupID == $group->ID )
                return true;
        
        return false;   
    }
		
	/*
	 * Generate a random password
	 * BDC - added randomiser to kick in if there's no words file on the filesystem.
	 */
	static function createNewPassword() {
		if(file_exists('/usr/share/silverstripe/wordlist.txt')) {
			$words = file('/usr/share/silverstripe/wordlist.txt');
	
			list($usec, $sec) = explode(' ', microtime());
			srand($sec + ((float) $usec * 100000));
			
			$word = trim($words[rand(0,sizeof($words)-1)]);
			$number = rand(10,999);
			
			return $word . $number;
		} else {
	    	$random = rand();
		    $string = md5($random);
    		$output = substr($string, 0, 6);
	    	return $output;
		}
	}
	/**
	 * Returns true if this user is an administrator.
	 * Administrators have access to everything.  The lucky bastards! ;-)
	 *
	 * @todo Should this function really exists? Is not {@link isAdmin()} the
	 *       only right name for this?
	 * @todo Is {@link Group}::CanCMSAdmin not deprecated?
	 */
	function _isAdmin() {
		if($groups = $this->Groups()) {
			foreach($groups as $group) if($group->CanCMSAdmin) return true;
		}

		return Permission::check('ADMIN');
	}


	/**
	 * Check if the user is an administrator
	 *
	 * Alias for {@link _isAdmin()} because the method is used in both ways
	 * all over the framework.
	 *
	 * @return Returns TRUE if this user is an administrator.
	 * @see _isAdmin()
	 */
	public function isAdmin() {
		return $this->_isAdmin();
	}
	function _isCMSUser() {
		if($groups = $this->Groups()) {
			foreach($groups as $group) if($group->CanCMS) return true;
		}
	}
	
	
	//----------------------------------------------------------------------------------------//

	public function getTitle() {
		if($this->getField('ID') === 0)
			return $this->getField('Surname');
		return $this->getField('Surname') . ', ' . $this->getField('FirstName');
	}	
	
	public function getName() {
		return $this->FirstName . ' ' . $this->Surname;
	}
	 
	public function setName( $name ) {
		$nameParts = explode( ' ', $name );
		$this->Surname = array_pop( $nameParts );
		$this->FirstName = join( ' ', $nameParts );
	}

	public function splitName( $name ) {
		return $this->setName( $name );
	}

	//----------------------------------------------------------------------------------------//

	public function Groups() {
		$groups = $this->getManyManyComponents("Groups");
		
		$unsecure = DataObject::get("Group_Unsecure", "");
		if($unsecure) foreach($unsecure as $unsecureItem) {
			$groups->push($unsecureItem);
		}
		
		$groupIDs = $groups->column();
		$collatedGroups = array();
		foreach($groups as $group) {
			$collatedGroups = array_merge((array)$collatedGroups, $group->collateAncestorIDs());
		}

		$table = "Group_Members";

		if($collatedGroups) {
			$collatedGroups = implode(", ", array_unique($collatedGroups));

			$result = singleton('Group')->instance_get("`ID` IN ($collatedGroups)", "ID", "", "", "Member_GroupSet");
		} else {
			$result = new Member_GroupSet();
		}

		$result->setComponentInfo("many-to-many", $this, "Member", $table, "Group");

		return $result;
	}

	public function isInGroup($groupID) {
		$groups = $this->Groups();
		foreach($groups as $group) {
			if($group->ID == $groupID) return true;
		}
    	return false;
	}

	public function map($filter = "", $sort = "", $blank="") {
		$ret = new SQLMap(singleton('Member')->extendedSQL($filter, $sort));
		if($blank){
			$blankMember = new Member();
			$blankMember->Surname = $blank;
			$blankMember->ID = 0;

			$ret->getItems()->shift($blankMember);
		}
		return $ret;
	}
	

	public static function mapInGroups( $groups = null ) {
		
		if( !$groups )
			return Member::map();
		
		$groupIDList = array();
		
		if( is_a( $groups, 'DataObjectSet' ) )
			foreach( $groups as $group )
				$groupIDList[] = $group->ID;
		elseif( is_array( $groups ) )
			$groupIDList = $groups;
		else
			$groupIDList[] = $groups;
			
		if( empty( $groupIDList ) )
			return Member::map();	
			
		return new SQLMap( singleton('Member')->extendedSQL( "`GroupID` IN (" . implode( ',', $groupIDList ) . ")", "Surname, FirstName", "", "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`") );
	}
	
	
	/**
	 * Return a map of all members in the groups given that have CMS permissions
	 * Defaults to all groups with CMS permissions
	 */
	public static function mapInCMSGroups( $groups = null ) {
		if( !$groups || $groups->Count() == 0 )
			$groups = DataObject::get('Group',"", "", "INNER JOIN `Permission` ON `Permission`.GroupID = `Group`.ID AND `Permission`.Code IN ('ADMIN', 'CMS_ACCESS_AssetAdmin')"); 
		
		$groupIDList = array();
		
		if( is_a( $groups, 'DataObjectSet' ) )
			foreach( $groups as $group )
				$groupIDList[] = $group->ID;
		elseif( is_array( $groups ) )
			$groupIDList = $groups;
			
		/*if( empty( $groupIDList ) )
			return Member::map();	*/
		
		$filterClause = ($groupIDList) ? "`GroupID` IN (" . implode( ',', $groupIDList ) . ")" : "";
			
		return new SQLMap( singleton('Member')->extendedSQL( $filterClause, "Surname, FirstName", "", "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID` INNER JOIN `Group` ON `Group`.`ID`=`GroupID`") );		
	}
		
	/** 
	 * When passed an array of groups, and a component set of groups, this function
	 * will return the array of groups the member is NOT in.
	 * @param grouplist an array of group code names.
	 * @param memberGroups a component set of groups ( set to $this->groups() by default )
	 */
	public function memberNotInGroups($groupList,$memberGroups = null){
		if(!$memberGroups) $memberGroups = $this->Groups();
		
		foreach($memberGroups as $group){
			if(in_array($group->Code,$groupList)){
				$index = array_search($group->Code,$groupList);
				unset($groupList[$index]);
			}
		}
		return $groupList;
	}

	/**
	 * Return a FieldSet of fields that would appropriate for editing this member.
	 */
	public function getCMSFields() {
		$fields = new FieldSet(
				//new TextField("Salutation", "Title"),
				new HeaderField( "Personal Details" ),
				new TextField("FirstName", "First Name"),
				new TextField("Surname", "Surname"),
				new HeaderField( "User Details" ),
				new TextField("Email", "Email"),
				/*new TextField("Password", "Password")*/
				new PasswordField("Password", "Password")
				//new TextareaField("Address","Address"),
				//new TextField("JobTitle", "Job Title"),
				//new TextField( "Organisation", "Organisation" ),
				//new OptionsetField("HTMLEmail","Mail Format", array( 1 => 'HTML', 0 => 'Text only' ) )
			);
			
		$this->extend('updateCMSFields', $fields);
		// if($this->hasMethod('updateCMSFields')) $this->updateCMSFields($fields);
		
		return $fields;
	}
		
	function unsubscribeFromNewsletter( $newsletterType ) {
		// record today's date in unsubscriptions
   		 // this is a little bit redundant
	    $unsubscribeRecord = new Member_UnsubscribeRecord();
	    $unsubscribeRecord->unsubscribe( $this, $newsletterType );
		$this->Groups()->remove( $newsletterType->GroupID );
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DB::query("SELECT * FROM Member")->value() && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
			Security::findAnAdministrator($_REQUEST['username'], $_REQUEST['password']);
			Database::alteration_message("Added admin account","created");
		}
	}
}

/**
 * Special kind of ComponentSet that has special methods for manipulating a user's membership
 */
class Member_GroupSet extends ComponentSet {
	/**
	 * Control group membership with a number of checkboxes.  
	 *  - If the checkbox fields are present in $data, then the member will be added to the group with the same codename.  
	 *  - If the checkbox fields are *NOT* present in $data, then the member willb e removed from the group with the same codename.
	 * @param checkboxes an array list of the checkbox fieldnames (Only values are used.) eg array(0,1,2);
	 * @param data The form data. usually in the format array(0 => 2) (just pass the checkbox data from your form);
	 */
	function setByCheckboxes($checkboxes, $data) {
		foreach($checkboxes as $checkbox) {
			if($data[$checkbox]){
				$add[] = $checkbox;	
			}else{
				$remove[] = $checkbox;
			} 
		}
		if($add)$this->addManyByCodename($add);
		if($remove)	$this->removeManyByCodename($remove);
	}
	
	/**
	 * Allows you to set groups based on a checkboxsetfield. 
	 * (pass the form element from your post data directly to this method, and it 
	 * will update the groups and add and remove the member as appropriate)
	 * @param checkboxsetField - the CheckboxSetField (with data) from your form.
	 * 
	 * On the form setup
	 * 
	 	$fields->push(
			new CheckboxSetField(
				"NewsletterSubscriptions",
				"Receive email notification of events in ",
				$sourceitems = DataObject::get("NewsletterType")->toDropDownMap("GroupID","Title"),
				$selectedgroups = $member->Groups()->Map("ID","ID")
			)
		);
	 * 
	 * 
	 * 
	 * On the form handler: 
	 	$groups = $member->Groups();
        $checkboxfield = $form->Fields()->fieldByName("NewsletterSubscriptions");
	 	$groups->setByCheckboxSetField($checkboxfield);
	 * 
	 */
	function setByCheckboxSetField($checkboxsetfield){
	
		// Get the values from the formfield. 
		$values = $checkboxsetfield->Value();
		$sourceItems = $checkboxsetfield->getSource();
			
		if($sourceItems){
			// If (some) values are present, add and remove as necessary.
			if($values){
				// update the groups based on the selections
				foreach($sourceItems as $k => $item){
					if(in_array($k,$values)){
						$add[] = $k;
					}else{
						$remove[] = $k;
					}			
				}
			
			// else we should be removing all from the necessary groups.
			}else{
				$remove = $sourceItems;
			}

			if($add)$this->addManyByGroupID($add);
			if($remove)	$this->RemoveManyByGroupID($remove);
			
		}else{
			USER_ERROR("Member::setByCheckboxSetField() - No source items could be found for checkboxsetfield ". $checkboxsetfield->Name(),E_USER_WARNING);
		}
	}
	
	/**
	 * Adds this member to the groups based on the 
	 * groupID.
	 */
	function addManyByGroupID($groupIds){
		$groups = $this->getGroupsFromIDs($groupIds);
		if($groups){
			foreach($groups as $group){
				$this->add($group);
			}
		}
		
	}
	
	/**
	 * Removes the member from many groups based on 
	 * the group ID.
	 */
	function removeManyByGroupID($groupIds){
	 	$groups = $this->getGroupsFromIDs($groupIds);
	 	if($groups){
			foreach($groups as $group){
				$this->remove($group);
			}
		}
	 	
	}
	 
	/**
	 * Returns the groups from an array of GroupIDs
	 */
	function getGroupsFromIDs($ids){
		if($ids && count($ids) > 1){
			return	DataObject::get("Group","ID IN (". implode(",",$ids)   .")");
		}else{
			return DataObject::get_by_id("Group",$ids[0]);
		}
	}
	
	
	/**
	 * Adds this member to the groups passed.
	 */
	function addManyByCodename($codenames) {
		$groups = $this->codenamesToGroups($codenames);
		if($groups){
			foreach($groups as $group){
				$this->add($group);
			}
		}
	}
	
	/**
	 * Removes this member from the groups passed.
	 */
	function removeManyByCodename($codenames) {
		$groups = $this->codenamesToGroups($codenames);
		if($groups){
			foreach($groups as $group){
				$this->remove($group);
			}	
		}
	}
	
	/**
	 * Helper function to return the appropriate group via a codename.
	 */
	protected function codenamesToGroups($codenames) {
		$list = "'" . implode("', '", $codenames) . "'";
		$output = DataObject::get("Group", "Code IN ($list)");
				
		// Some are missing - throw warnings
		if(!$output || $output->Count() != sizeof($list)) {
			foreach($codenames as $codename) $missing[$codename] = $codename;
			if($output) foreach($output as $record) unset($missing[$record->Code]);
			if($missing) user_error("The following group-codes aren't matched to any groups: " . implode(", ", $missing) . ".  You probably need to link up the correct group codes in phpMyAdmin", E_USER_WARNING);
		}
		
		return $output;
	}
}



class Member_SignupEmail extends Email_Template {
	protected 
		$from = 'ask@perweek.co.nz',
		$to = '$Email',
		$subject = "Thanks for signing up",
		$body = '
			<h1>Welcome, $FirstName.</h1>
			<p>Thanks for signing up to become a new member, your details are listed below for future reference.</p>

			<p>You can login to the website using the credentials listed below:	
				<ul>
					<li><strong>Email:</strong>$Email</li>
					<li><strong>Password:</strong>$Password</li>
				</ul>
			</p>
			
			<h3>Contact Information</h3>
			<ul>
				<li><strong>Name:</strong> $FirstName $Surname</li>
				<% if Phone %>
					<li><strong>Phone:</strong> $Phone</li>
				<% end_if %>
				
				<% if Mobile %>
					<li><strong>Mobile:</strong> $Mobile</li>
				<% end_if %>
				
				<% if RuralAddressCheck %>
					<li><strong>Rural Address:</strong>
						$RapidResponse $Road<br/>
						$RDNumber<br/>
						$City $Postcode
					</li>
				<% else %>
					<li><strong>Address:</strong>
					<br/>
					$Number $Street $StreetType<br/>
					$Suburb<br/>
					$City $Postcode
					</li>
				<% end_if %>
							
				<% if DriversLicense5A %>
					<li><strong>Drivers License:</strong> $DriversLicense5A<% if DriversLicense5B %> - $DriversLicense5B <% end_if %></li>
				<% end_if %>
				
			</ul>';
			
	function MemberData() {
		return $this->template_data->listOfFields(
		"FirstName","Surname","Email",
		"Phone","Mobile","Street",
		"Suburb","City","Postcode","DriversLicense5A","DriversLicense5B"
		);
	}
}

/**
* Send an email saying that the password has been reset.
*/

class Member_ChangePasswordEmail extends Email_Template {
    protected $from = '';   // setting a blank from address uses the site's default administrator email
    protected $subject = "Your password has been changed";
    protected $ss_template = 'ChangePasswordEmail';
    protected $to = '$Email';    
}

class Member_ForgotPasswordEmail extends Email_Template {
    protected $from = '';
    protected $subject = "Your password";
    protected $ss_template = 'ForgotPasswordEmail';
    protected $to = '$Email';   
}

/**
 * Record to keep track of which records a member has unsubscribed from and when
 */
class Member_UnsubscribeRecord extends DataObject {
    
    static $has_one = array(
        'NewsletterType' => 'NewsletterType',
        'Member' => 'Member'
    );
    
    function unsubscribe( $member, $newsletterType ) {
        // $this->UnsubscribeDate()->setVal( 'now' );
        $this->MemberID = ( is_numeric( $member ) ) ? $member : $member->ID;
        $this->NewsletterTypeID = ( is_numeric( $newletterType ) ) ? $newsletterType : $newsletterType->ID;
        $this->write();   
    }
	protected 
		$from = 'ask@perweek.co.nz',
		$to = '$Email',
		$subject = "Your password has been changed",
		$body = '
			<h1>Here\'s your new password</h1>
			<p>
				<strong>Email:</strong> $Email<br />
				<strong>Password:</strong> $Password
			</p>
			<p>Your password has been changed. Please keep this email, for future reference.</p>';
}

class Member_Validator extends RequiredFields {
	protected $customRequired = array('FirstName', 'Email', 'Password');
	
	public function __construct() {
		$required = func_get_args();
		if(isset($required[0]) && is_array($required[0])) {
			$required = $required[0];
		}
		$required = array_merge($required, $this->customRequired);
		
		 parent::__construct($required);
	}
	

	function php($data) {
		$valid = parent::php($data);
		
		// Check if a member with that email doesn't already exist, or if it does that it is this member.
		$member = DataObject::get_one('Member', "Email = '". Convert::raw2sql($data['Email']) ."'");
		// if we are in a complex table field popup, use ctf[childID], else use ID
		$id = (isset($_REQUEST['ctf']['childID'])) ? $_REQUEST['ctf']['childID'] : $_REQUEST['ID'];
		if(is_object($member) && $member->ID != $id) {
			$emailField = $this->form->dataFieldByName('Email');
			$this->validationError($emailField->id(), "There already exists a member with this email", "required");
			$valid = false;
		}
		
		return $valid;
	}
}
?>