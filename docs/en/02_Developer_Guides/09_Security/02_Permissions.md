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

## PermissionProvider

`[api:PermissionProvider]` is an interface which lets you define a method *providePermissions()*.
This method should return a map of permission code names with a human readable explanation of its purpose.

	:::php
	class Page_Controller implements PermissionProvider {
	  public function init() {
	    parent::init();
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

## Special cases

### ADMIN permissions

By default the config option `admin_implies_all` is true - this means that any user granted the `ADMIN` permission has
all other permissions granted to them. This is a type of cascading of permissions that is hard coded into the permission
system.

### CMS access permissions

Access to the CMS has a couple of special cases where permission codes can imply other permissions.

#### 1. Granting access to all CMS permissions

The `CMS_ACCESS_LeftAndMain` grants access to every single area of the CMS, without exception. Internally, this works by
adding the `CMS_ACCESS_LeftAndMain` code to the set of accepted codes when a `CMS_ACCESS_*` permission is required.
This works much like ADMIN permissions (see above)


#### 2. Checking for any access to the CMS

You can check if a user has access to the CMS by simply performing a check against `CMS_ACCESS`.

	:::php
	if (Permission::checkMember($member, 'CMS_ACCESS')) {
		//user can access the CMS
	}

Internally, this checks that the user has any of the defined `CMS_ACCESS_*` permissions.


## API Documentation
`[api:Permission]`
