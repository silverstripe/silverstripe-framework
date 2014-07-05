title: Extending DataObjects
summary: Modify the data model without using subclasses.

# Extending DataObjects

You can add properties and methods to existing [api:DataObjects]s like [api:Member] without hacking core code or sub 
classing by using [api:DataExtension]. See the [Extending SilverStripe](../extending) guide for more information on
[api:DataExtension].

The following documentation outlines some common hooks that the [api:Extension] API provides specifically for managing
data records.

## onBeforeWrite

You can customize saving-behavior for each DataObject, e.g. for adding workflow or data customization. The function is 
triggered when calling *write()* to save the object to the database. This includes saving a page in the CMS or altering 
a `ModelAdmin` record.

Example: Disallow creation of new players if the currently logged-in player is not a team-manager.

	:::php
	<?php

	class Player extends DataObject {

	  private static $has_many = array(
	    "Teams"=>"Team"
	  );
	
	  public function onBeforeWrite() {
	    // check on first write action, aka "database row creation" (ID-property is not set)
	    if(!$this->isInDb()) {
	      $currentPlayer = Member::currentUser();

	      if(!$currentPlayer->IsTeamManager()) {
	        user_error('Player-creation not allowed', E_USER_ERROR);
	        exit();
	      }
	    }
	
	    // check on every write action
	    if(!$this->record['TeamID']) {
	        user_error('Cannot save player without a valid team', E_USER_ERROR);
	        exit();
	    }
	
	    // CAUTION: You are required to call the parent-function, otherwise
	    // SilverStripe will not execute the request.
	    parent::onBeforeWrite();
	  }
	}

## onBeforeDelete

Triggered before executing *delete()* on an existing object.

Example: Checking for a specific [permission](/reference/permission) to delete this type of object. It checks if a 
member is logged in who belongs to a group containing the permission "PLAYER_DELETE".

	:::php
	<?php

	class Player extends DataObject {

	  private static $has_many = array(
	    "Teams" => "Team"
	  );
	
	  public function onBeforeDelete() {
	    if(!Permission::check('PLAYER_DELETE')) {
	      Security::permissionFailure($this);
	      exit();
	    }
	
	    parent::onBeforeDelete();
	  }
	}



<div class="notice" markdown='1'>
Note: There are no separate methods for *onBeforeCreate* and *onBeforeUpdate*. Please check `$this->isInDb()` to toggle 
these two modes, as shown in the example above.
</div>
