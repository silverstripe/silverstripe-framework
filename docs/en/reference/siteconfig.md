# SiteConfig

## Introduction

The `[api:SiteConfig]` panel was introduced in 2.4 for providing a generic interface for managing site wide settings or
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
	
	class CustomSiteConfig extends DataObjectDecorator {
		
		function extraStatics() {
			return array(
				'db' => array(
					'FooterContent' => 'HTMLText'
				)
			);
		}
	
		public function updateCMSFields(FieldSet $fields) {
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

## API Documentation
`[api:SiteConfig]`