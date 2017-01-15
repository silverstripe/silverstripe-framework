title: Members

# Member

## Introduction

The [api:Member] class is used to represent user accounts on a SilverStripe site (including newsletter recipients).
 
## Testing For Logged In Users

The [api:Member] class comes with 2 static methods for getting information about the current logged in user.

**Member::currentUserID()**

Retrieves the ID (int) of the current logged in member.  Returns *0* if user is not logged in.  Much lighter than the
next method for testing if you just need to test.

	:::php
	// Is a member logged in?
	if( Member::currentUserID() ) {
		// Yes!
	} else {
		// No!
	}


**Member::currentUser()**

Returns the full *Member* Object for the current user, returns *null* if user is not logged in.

	:::php
	if( $member = Member::currentUser() ) {
		// Work with $member
	} else {
		// Do non-member stuff
	}


## Subclassing

<div class="warning" markdown="1">
This is the least desirable way of extending the [api:Member] class. It's better to use [api:DataExtension]
(see below).
</div>

You can define subclasses of [api:Member] to add extra fields or functionality to the built-in membership system.

	:::php
	class MyMember extends Member {
		private static $db = array(
			"Age" => "Int",
			"Address" => "Text",
		);
	}


To ensure that all new members are created using this class, put a call to [api:Object::useCustomClass()] in
(project)/_config.php:

	:::php
	Object::useCustomClass("Member", "MyMember");

Note that if you want to look this class-name up, you can call Object::getCustomClass("Member")

## Overloading getCMSFields()

If you overload the built-in public function getCMSFields(), then you can change the form that is used to view & edit member
details in the newsletter system.  This function returns a [api:FieldList] object.  You should generally start by calling
parent::getCMSFields() and manipulate the [api:FieldList] from there.

	:::php
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->insertBefore("HTMLEmail", new TextField("Age"));
		$fields->removeByName("JobTitle");
		$fields->removeByName("Organisation");
		return $fields;
	}


## Extending Member or DataObject?

Basic rule: Class [api:Member] should just be extended for entities who have some kind of login.
If you have different types of [api:Member]s in the system, you have to make sure that those with login-capabilities have
unique email-addresses (as this is used for login-credentials). 
For persons without login-capabilities (e.g. for an address-database), you shouldn't extend [api:Member] to avoid conflicts
with the Member-database. This enables us to have a different subclass of [api:Member] for an email-address with login-data,
and another subclass for the same email-address in the address-database.

## Member Role Extension

Using inheritance to add extra behaviour or data fields to a member is limiting, because you can only inherit from 1
class. A better way is to use role extensions to add this behaviour. Add the following to your
`[config.yml](/developer_guides/configuration/configuration/#configuration-yaml-syntax-and-rules)`.

	:::yml
	Member:
	  extensions:
	    - MyMemberExtension

A role extension is simply a subclass of [api:DataExtension] that is designed to be used to add behaviour to [api:Member]. 
The roles affect the entire class - all members will get the additional behaviour.  However, if you want to restrict
things, you should add appropriate [api:Permission::checkMember()] calls to the role's methods.

	:::php
	class MyMemberExtension extends DataExtension {
	  /**
	
	   * Modify the field set to be displayed in the CMS detail pop-up
	   */
	  public function updateCMSFields(FieldList $currentFields) {
	    // Only show the additional fields on an appropriate kind of use 
	    if(Permission::checkMember($this->owner->ID, "VIEW_FORUM")) {
	      // Edit the FieldList passed, adding or removing fields as necessary
	    }
	  }
	
		// define additional properties
		private static $db = array(); 
		private static $has_one = array(); 
		private static $has_many = array(); 
		private static $many_many = array(); 
		private static $belongs_many_many = array(); 
	
	  public function somethingElse() {
	    // You can add any other methods you like, which you can call directly on the member object.
	  }
	}

## Saved User Logins ##

Logins can be "remembered" across multiple devices when user checks the "Remember Me" box. By default, a new login token
will be created and associated with the device used during authentication. When user logs out, all previously saved tokens
for all devices will be revoked, unless `[api:RememberLoginHash::$logout_across_devices] is set to false. For extra security,
single tokens can be enforced by setting `[api:RememberLoginHash::$force_single_token] to true.

## Acting as another user ##

Occasionally, it may be necessary not only to check permissions of a particular member, but also to
temporarily assume the identity of another user for certain tasks. E.g. when running a CLI task,
it may be necessary to log in as an administrator to perform write operations.

You can use `Member::actAs()` method, which takes a member or member id to act as, and a callback
within which the current user will be assigned the given member. After this method returns
the current state will be restored to whichever current user (if any) was logged in.

If you pass in null as a first argument, you can also mock being logged out, without modifying
the current user.

Note: Take care not to invoke this method to perform any operation the current user should not
reasonably be expected to be allowed to do.

E.g.


    :::php
    class CleanRecordsTask extends BuildTask
    {
        public function run($request)
        {
            if (!Director::is_cli()) {
                throw new BadMethodCallException('This task only runs on CLI');
            }
            $admin = Security::findAnAdministrator();
            Member::actAs($admin, function() {
                DataRecord::get()->filter('Dirty', true)->removeAll();
            });
        }


## API Documentation

[api:Member]
