title: SiteConfig
summary: Content author configuration through the SiteConfig module.

# SiteConfig

The `SiteConfig` module provides a generic interface for managing site wide settings or functionality which is used 
throughout the site. Out of the box this includes selecting the current site theme, site name and site wide access.

## Accessing variables

`SiteConfig` options can be accessed from any template by using the $SiteConfig variable.

	:::ss
	$SiteConfig.Title 
	$SiteConfig.Tagline
	
	<% with $SiteConfig %>
		$Title $AnotherField
	<% end_loop %>

To access variables in the PHP:

	:::php
	$config = SiteConfig::current_site_config(); 
	
	echo $config->Title;

	// returns "Website Name"


## Extending SiteConfig

To extend the options available in the panel, define your own fields via a [api:DataExtension].

**mysite/code/extensions/CustomSiteConfig.php**

	:::php
	<?php
	
	class CustomSiteConfig extends DataExtension {
		
		private static $db = array(
			'FooterContent' => 'HTMLText'
		);
	
		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab("Root.Main", 
				new HTMLEditorField("FooterContent", "Footer Content")
			);
		}
	}

Then activate the extension.

**mysite/_config/app.yml**

	:::yml
	SiteConfig:
	  extensions:
	    - CustomSiteConfig

<div class="notice" markdown="1">
After adding the class and the YAML change, make sure to rebuild your database by visiting http://yoursite.com/dev/build.
You may also need to reload the screen with a `flush=1` i.e http://yoursite.com/admin/settings?flush=1.
</div>

You can define as many extensions for `SiteConfig` as you need. For example, if you're developing a module and what to
provide the users a place to configure settings then the `SiteConfig` panel is the place to go it.

## API Documentation

* `[api:SiteConfig]`