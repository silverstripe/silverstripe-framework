# DataExtension

## Introduction

Extensions allow for adding additional functionality to a `[api:DataObject]` or
modifying existing functionality without the hassle of creating a subclass.

## Usage

Extensions are defined as subclasses of either `[api:DataExtension]` for 
extending a `[api:DataObject]` subclass or the `[api:Extension]` class for non 
DataObject subclasses (such as Controllers)

	:::php
	<?php
	// mysite/code/MyMemberExtension.php
	
	class MyMemberExtension extends DataExtension {

	}

This defines your own extension where we can add our own functions, database 
fields or other properties. After this class has been created, it 
does not yet apply it to your object. Next you need to tell SilverStripe what
class you want to extend.

### Adding a extension to a built-in class

For example, you may might want to add a `MyMemberExtension` class to the 
`[api:Member]` object to provide a custom method.

In order to active this extension, you need to add the following to your 
[config.yml](/topics/configuration).

	:::yml
	Member:
	  extensions:
	    - MyMemberExtension

Alternatively, you can add extensions through PHP code (in your `_config.php` 
file).

	:::php
	Member::add_extension('MyMemberExtension');


### Extending code to allow for extensions

If you're providing a module or working on code that may need to be extended by 
other code, it can provide a *hook* which allows an Extension to modify the 
results. This is done through the `[api:Object->extend()]` method.

	:::php
	public function myFunc() {
		$foo = // ..

		$this->extend('alterFoo', $foo);

		return $foo;
	}

In this example, the myFunc() method adds a hook to allow `DataExtension`
subclasses added to the instance to define an `alterFoo($foo)` method to modify 
the result of the method.

The `$foo` parameter is passed by reference, as it is an object. 

### Accessing the original Object from an Extension

In your extension class you can refer to the source object through the `owner`
property on the class.

	:::php
	<?php

	class MyMemberExtension extends DataExtension {

		public function alterFoo($foo) {
			// outputs the original class
			var_dump($this->owner);
		}
	}

### Checking to see if an Object has an Extension

To see what extensions are currently enabled on an object, you can use 
`[api:Object->getExtensionInstances()]` and `[api:Object->hasExtension($extension)]`.

##  Implementation

###  Adding extra database fields

Extra database fields can be added with a extension in the same manner as if 
they were placed on the `DataObject` class they're applied to.  These will be 
added to the table of the base object - the extension will actually edit the
$db, $has_one, etc static variables on load.

The function should return a map where the keys are the names of the static 
variables to update:

	:::php
	<?php

	class MyMemberExtension extends DataExtension {

		private static $db = array(
			'Position' => 'Varchar',
		);

		private static $has_one = array(
			'Image' => 'Image',
		);
	}

### Modifying CMS Fields

The member class demonstrates an extension that allows you to update the default 
CMS fields for an object in an extension:


	:::php
	<?php

	class MyMemberExtension extends DataExtension {

		private static $db = array(
			'Position' => 'Varchar',
		);

		private static $has_one = array(
			'Image' => 'Image',
		);

		public function updateCMSFields(FieldList $fields) {
	  		$fields->push(new TextField('Position'));
	  		$fields->push(new UploadField('Image', 'Profile Image'));
		}
	}

### Adding/modifying fields prior to extensions

User code can intervene in the process of extending cms fields by using 
`beforeUpdateCMSFields` in its implementation of `getCMSFields`. This can be 
useful in cases where user code will add fields to a dataobject that should be 
present in the `$fields` parameter when passed to `updateCMSFields` in
 extensions.

This method is preferred to disabling, enabling, and calling cms field 
extensions manually.

	:::php
	function getCMSFields() {
		$this->beforeUpdateCMSFields(function($fields) {
			// Include field which must be present when updateCMSFields is called on extensions
			$fields->addFieldToTab("Root.Main", new TextField('Detail', 'Details', null, 255));
		});

		$fields = parent::getCMSFields();
		// ... additional fields here
		return $fields;
	}

### Object extension injection points

`Object` now has two additional methods, `beforeExtending` and `afterExtending`, 
each of which takes a method name and a callback to be executed immediately 
before and after `Object::extend()` is called on extensions.

This is useful in many cases where working with modules such as `Translatable` 
which operate on `DataObject` fields that must exist in the `FieldList` at the 
time that `$this->extend('UpdateCMSFields')` is called.

<div class="notice" markdown='1'>
Please note that each callback is only ever called once, and then cleared, so 
multiple extensions to the same function require that a callback is registered 
each time, if necessary.
</div>

Example: A class that wants to control default values during object 
initialization. The code needs to assign a value if not specified in 
`self::$defaults`, but before extensions have been called:

	:::php
	function __construct() {
		$self = $this;

		$this->beforeExtending('populateDefaults', function() use ($self) {
			if(empty($self->MyField)) {
				$self->MyField = 'Value we want as a default if not specified in $defaults, but set before extensions';
			}
		});

		parent::__construct();
	}


### Custom database generation

Some extensions are designed to transparently add more sophisticated 
data-collection capabilities to your `DataObject`. For example, `[api:Versioned]` 
adds version tracking and staging to any `DataObject` that it is applied to. 

To do this, define an **augmentDatabase()** method on your extension.  This will 
be called when the database is rebuilt.

*  You can query `$this->owner` for information about the data object, such as 
the fields it has
*  You can use **DB::requireTable($tableName, $fieldList, $indexList)** to set 
up your new tables.  This function takes care of creating, modifying, or leaving 
tables as required, based on your desired schema.

### Custom write queries

If you have customised the generated database, then you probably want to change 
the way that writes happen.  This isused by `[api:Versioned]` to get an entry 
written in ClassName_versions whenever an insert/update happens.

To do this, define the **augmentWrite(&$manipulation)** method.  This method is 
passed a manipulation array representing the write about to happen, and is able 
to amend this as desired, since it is passed by reference. 

### Custom relation queries

The other queries that you will want to customise are the selection queries, 
called by get & get_one.  For example, the Versioned object has code to redirect 
every request to ClassName_live, if you are browsing the live site.

To do this, define the **augmentSQL(SQLQuery &$query)** method. Again, the 
`$query` object is passed by reference and can be modified as needed by your 
method. Instead of a manipulation array, we have a `[api:SQLQuery]` object.

### Additional methods

The other thing you may want to do with a extension is provide a method that can 
be called on the `[api:DataObject]` that is being extended. For instance, you 
may add a publish() method to every `[api:DataObject]` that is extended with 
`[api:Versioned]`.

This is as simple as defining a method called publish() on your extension.  Bear 
in mind, however, that instead of $this, you should be referring to 
`$this->owner`.

*  $this = The `[api:DataExtension]` object.
*  $this->owner = The related `[api:DataObject]` object.

If you want to add your own internal properties, you can add this to the 
`[api:DataExtension]`, and these will be referred to as `$this->propertyName`.  
Every `[api:DataObject]` has an associated `[api:DataExtension]` instance for 
each class that it is extended by.

	:::php
	<?php

	class Customer extends DataObject {
	
		private static $has_one = array(
			'Account' => 'Account'
		);
	
		private static $extensions = array(
			'CustomerWorkflow'
		);
	
	}
	
	class Account extends DataObject {
	
		private static $db = array(
			'IsMarkedForDeletion'=>'Boolean'
		);
	
		private static $has_many = array(
			'Customers' => 'Customer'
		);
	}
	
	class CustomerWorkflow extends DataExtension {
	
		public function IsMarkedForDeletion() {
			return (bool) $this->owner->Account()->IsMarkedForDeletion;
		}
	}


## API Documentation

* `[api:Extension]`
* `[api:DataExtension]`

## See Also

* [Injector](injector/)
* `[api:Object::useCustomClass]`
