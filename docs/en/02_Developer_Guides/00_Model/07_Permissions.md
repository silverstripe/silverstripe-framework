title: Model-Level Permissions
summary: Reduce risk by securing models.

# Model-Level Permissions

Models can be modified in a variety of controllers and user interfaces, all of which can implement their own security 
checks. Often it makes sense to centralize those checks on the model, regardless of the used controller.

The API provides four methods for this purpose: `canEdit()`, `canCreate()`, `canView()` and `canDelete()`.

Since they're PHP methods, they can contain arbitrary logic matching your own requirements. They can optionally receive 
a `$member` argument, and default to the currently logged in member (through `Member::currentUser()`).

<div class="notice" markdown="1">
By default, all `DataObject` subclasses can only be edited, created and viewed by users with the 'ADMIN' permission 
code.
</div>

	:::php
	<?php

	class MyDataObject extends DataObject {
	
		public function canView($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}

		public function canEdit($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}

		public function canDelete($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}

		public function canCreate($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}
	}

<div class="alert" markdown="1">
These checks are not enforced on low-level ORM operations such as `write()` or `delete()`, but rather rely on being 
checked in the invoking code. The CMS default sections as well as custom interfaces like [api:ModelAdmin] or 
[api:GridField] already enforce these permissions.
</div>

## API Documentation

* [api:DataObject]
* [api:Permission]