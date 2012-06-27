# SiteConfig

## Introduction

The `[api:SiteConfig]` panel provides a generic interface for managing site wide settings or
functionality which is used throughout the site. Out of the box it provides 2 fields 'Site Name' and 'Site Tagline'.

## Accessing `[api:SiteConfig]` Options

You can access `[api:SiteConfig]` options from any SS template by using the function $SiteConfig.FieldName

	:::ss
	$SiteConfig.Title 
	$SiteConfig.Tagline
	
	// or 
	
	<% control SiteConfig %>
	$Title $AnotherField
	<% end_control %>


Or if you want to access variables in the PHP you can do

	:::php
	$config = SiteConfig::current_site_config(); 
	
	$config->Title


## Extending `[api:SiteConfig]`

To extend the options available in the panel you can define your own fields via an Extension.

Create a mysite/code/CustomSiteConfig.php file.

	:::php
	<?php
	
	class CustomSiteConfig extends DataExtension {
		
		public function extraStatics() {
			return array(
				'db' => array(
					'FooterContent' => 'HTMLText'
				)
			);
		}
	
		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab("Root.Main", new HTMLEditorField("FooterContent", "Footer Content"));
		}
	}


Then add a link to your extension in the _config.php file like below.

	Object::add_extension('SiteConfig', 'CustomSiteConfig');


This tells SilverStripe to add the CustomSiteConfig extension to the `[api:SiteConfig]` class. 

After adding those two pieces of code, rebuild your database by visiting http://yoursite.com/dev/build and then reload
the admin interface. You may need to reload it with a ?flush=1 on the end.

You can define as many extensions for `[api:SiteConfig]` as you need. For example if you are developing a module you can define
your own global settings for the dashboard.
N.B Using extraStatistics on extensions has been deprecated as of SilverStripe 3.0 please see the details below

### SilverStripe 3.0 DataExtension and deprecated extraStatics on extension classes {#extensions}

`DataObjectDecorator` has been renamed to `DataExtension`. Any classes that extend `DataObjectDecorator`
should now extend `DataExtension` instead.

`extraStatics()` on extensions is now deprecated.

Instead of using `extraStatics()`, you can simply define static variables on your extension directly.

If you need custom logic, e.g. checking for a class before applying the statics on the extension,
you can use `add_to_class()` as a replacement to `extraStatics()`.

Given the original `extraStatics` function:

	<?php
	//...
	function extraStatics($class, $extensionClass) {
		if($class == 'MyClass') {
			return array(
				'db' => array(
					'Title' => 'Varchar'
				);
			);
		}
	}

This would now become a static function `add_to_class`, and calls `update()` with an array
instead of returning it. It also needs to call `parent::add_to_class()`:

	<?php
	//...
	static function add_to_class($class, $extensionClass, $args = null) {
		if($class == 'MyClass') {
			Config::inst()->update($class, 'db', array(
				'Title' => 'Varchar'
			));
		}
		parent::add_to_class($class, $extensionClass, $args);
	}

Alternatively, you can define statics on the extension directly, like this:

	<?php
	//...
	static $db = array(
		'Title' => 'Varchar'
	);


## API Documentation
`[api:SiteConfig]`
