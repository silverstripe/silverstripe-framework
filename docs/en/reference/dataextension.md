# DataExtension

## Introduction

Extensions allow for adding additional functionality to a `[api:DataObject]`.

In some cases, it can be easier to completely replace the used class throughout the core with your custom
implementation. Have a look at `[api:Object->useCustomClass()]`.

## Usage

Your extension will nee to be a subclass of `[api:DataExtension]` or the `[api:Extension]` class.

	:::php
	<?php
	
	// mysite/code/CustomMember.php
	
	class CustomMember extends DataExtension {
	
	}

This defines your own extension where you can add your own functions, database fields or other properties you want.
After you create this extension however it does not yet apply it to your object. Next you need to tell SilverStripe what
class you want to extend.

### Adding a extension to a built-in class

Sometimes you will want to add extension to classes that you didn't make.  For example, you might want to add the
`[api:ForumRole]` extension to the `[api:Member]` object.


	:::php
	Object::add_extension('Class You Want To Override', 'Your Class Name');


For example above we want to override Member with a Custom Member so we would write the following

	:::php
	// add to mysite/_config.php	
	Object::add_extension('Member', 'CustomMember');</code>

##  Implementation


###  Adding extra database fields

Extra database fields can be added with a extension in the same manner as if they
were placed on the `DataObject` class they're applied to.  These will be added to the table of the base object - the extension will actually edit the $db, $has_one, etc static variables on load.

The function should return a map where the keys are the names of the static variables to update:

	:::php
	class CustomMember extends DataExtension {
		static $db = array(
			'AvatarURL' => 'Varchar',
		);
		static $has_one = array(
			'RelatedMember' => 'Member',
		);
	}

### Modifying CMS Fields

The member class demonstrates an extension that allows you to update the default CMS fields for an 
object in an extension:

	:::php
	public function getCMSFields() {
	  // ... 
	  $this->extend('updateCMSFields', $fields);
	  return $fields;
	}


The `$`fields parameter is passed by reference, as it is an object.

	:::php
	public function updateCMSFields(FieldList $fields) {
	  $fields->push(new TextField('Position', 'Position Title'));
	  $fields->push(new UploadField('Image', 'Profile Image'));
	}

### Custom database generation

Some extensions are designed to transparently add more sophisticated data-collection capabilities to your data object.
For example, `[api:Versioned]` adds version tracking and staging to any data object that it is applied to.  To do this,
you need to be able to create additional database tables and fields to keep your state stored in.

To do this, define an **augmentDatabase()** method on your extension.  This will be called when db/build is visited.

*  You can query ``$this->owner`` for information about the data object, such as the fields it has
*  You can use **DB::requireTable($tableName, $fieldList, $indexList)** to set up your new tables.  This function takes
care of creating, modifying, or leaving tables as required, based on your desired schema.

### Custom write queries

If you have customised the generated database, then you probably want to change the way that writes happen.  This is
used by `[api:Versioned]` to get an entry written in ClassName_versions whenever an insert/update happens.

To do this, define the **augmentWrite(&$manipulation)** method.  This method is passed a manipulation array representing
the write about to happen, and is able to amend this as desired, since it is passed by reference. 

### Custom relation queries

The other queries that you will want to customise are the selection queries, called by get & get_one.  For example, the
Versioned object has code to redirect every request to ClassName_live, if you are browsing the live site.

To do this, define the **augmentSQL(SQLQuery &$query)** method.  Again, the $query object is passed by reference and can
be modified as needed by your method.  Instead of a manipulation array, we have a `[api:SQLQuery]` object.

### Additional methods

The other thing you may want to do with a extension is provide a method that can be called on the `[api:DataObject]` that is
being extended.  For instance, you may add a publish() method to every `[api:DataObject]` that is extended with `[api:Versioned]`.

This is as simple as defining a method called publish() on your extension.  Bear in mind, however, that instead of
$this, you should be referring to $this->owner.

*  $this = The `[api:DataExtension]` object.
*  $this->owner = The related `[api:DataObject]` object.

If you want to add your own internal properties, you can add this to the `[api:DataExtension]`, and these will be referred
to as `$this->propertyName`.  Every `[api:DataObject]` has an associated `[api:DataExtension]` instance for each class that it is
extended by.

	:::php
	class Customer extends DataObject {
	
	 static $has_one = array('Account'=>'Account');
	
	 static $extensions = array(
	    'CustomerWorkflow'
	 );
	
	}
	
	class Account extends DataObject {
	
	 static $db = array(
	     'IsMarkedForDeletion'=>'Boolean'
	 );
	
	 static $has_many = array('Customers'=>'Customer');
	
	}
	
	class CustomerWorkflow extends DataExtension {
	
	 public function IsMarkedForDeletion() {
	     return ($this->owner->Account()->IsMarkedForDeletion == 1) ? true : false;
	 }
	
	}


## API Documentation
`[api:DataExtension]`