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

To extend the options available in the panel you can define your own fields via the Extension.
If you need custom logic, e.g. checking if the server is in dev mode before applying the statics on the extension, you can use `add_to_class()`

The function is defined as static and calls `update()` with an array and it also needs to call `parent::add_to_class()`:

	<?php
	//...
	static function add_to_class($class, $extensionClass, $args = null) {
		if(Director::isDev(true)) {
			Config::inst()->update($class, 'db', array(
				'Debug' => 'Varchar'
			));
		}
		parent::add_to_class($class, $extensionClass, $args);
	}

Alternatively, you can define statics on the extension directly, like this:

	<?php
	//...
	static $db = array(
		'Debug' => 'Varchar'
	);

## API Documentation
`[api:SiteConfig]`
