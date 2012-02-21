# User Permissions

## Introduction

This class implements SilverStripe's permission system.

## Usage

Permissions are defined on a group-by-group basis.  To give a permission to a member, go to a group that contains them,
and then select the permissions tab, and add that permission to the list.

The simple usage, Permission::check("PERM_CODE") will detect if the currently logged in member has the given permission.
 See the API docs for more options.

**Group ACLs**

*  Call **Permission::check("MY_PERMISSION_CODE")** to see if the current user has MY_PERMISSION_CODE.
*  MY_PERMISSION_CODE can be loaded into the Security admin on the appropriate group, using the "Permissions" tab. 

You can use whatever codes you like, but for the sanity of developers and users, it would be worth listing the codes in
[permissions:codes](/reference/permission)

## PermissionProvider

`[api:PermissionProvider]` is an interface which lets you define a method *providePermissions()*. This method should return a
map of permission code names with a human readable explanation of its purpose (see
[permissions:codes](/reference/permission)).

	:::php
	class Page_Controller implements PermissionProvider {
	  public function init() {
	    if(!Permission::check("VIEW_SITE")) Security::permissionFailure();
	  }
	
	  public function providePermissions() {
	    return array(
	      "VIEW_SITE" => "Access the site",
	    );
	  }
	}


This can then be used to add a dropdown for permission codes to the security panel.  Permission::get_all_codes() will be
a helper method that will call providePermissions() on every applicable class, and collate the resuls into a single
dropdown.

## Default use

By default, permissions are used in the following way:

*  The 'View' permission is checked when opening a page
*  The 'View' permissions is used on **all** default datafeeds:
    * If not logged in, the 'View' permissions must be 'anyone logged in' for a page to be displayed in a menu
    * If logged in, you must be allowed to view a page for it to be displayed in a menu

**NOTE:** Should the canView() method on SiteTree  be updated to call Permission::check("SITETREE_VIEW", $this->ID)? 
Making this work well is a subtle business and should be discussed with a few developers.

## Setting up permissions

*  By default, permissions are linked to groups.  You define a many-many relationship called Can(permname), eg,
"CanView".  Please note that group permissions are more efficient, as SQL joins are used to filter data.
*  Alternatively, you can create a custom permission by defining a function called can(permname)

## Using permissions

*  On an individual data record, $page->can("View", $member = null) and be called.  If a member isn't passed, the
currently logged in member is assumed.
*  On a request, $request->hasPermission("View", $member = null) can be called.  See [datamodel](/topics/datamodel) for
information on request objects.


## API Documentation
`[api:Permission]`
