# Howto customize the CMS Menu #

## Introduction ##

Every time you add a new extension of the `api:LeftAndMain` class to the CMS, SilverStripe will automatically
create a new menu-item for it, title and the default 'cogs' icon included.
But we can easily change that behaviour by using the static `$menu-title` and `$menu-icon` variables to
provide a custom title and icon.

The most popular extension of LeftAndMain is by far the `api:ModelAdmin` class, so we'll use that for an example. 
We'll take the ProductAdmin class used in the [ModelAdmin reference](../reference/modeladmin#setup).

## First: the icon ##

First we'll need a custom icon. For this purpose SilverStripe uses 16x16 black-and-transparent png icons.
In this case we'll place the icon in mysite/images, but you are free to use any location, as long as you 
provide the right path.

## ProductAdmin ##

	:::php
	class ProductAdmin extends ModelAdmin {
		public static $managed_models = array('Product','Category'); // Can manage multiple models
 		static $url_segment = 'products'; // Linked as /admin/products/	
 		
		static $menu_title = 'My Product Admin';
		static $menu-icon = 'mysite/images/product-icon.png'; 
	}

## Language file: MENUTITLE ##
 
By default, when displaying the title for the menu item, SilverStripe will look for the `MENUTITLE` entity in 
your module's current language file and use that. This is true even for the default language (lang/en.yml):  

	:::yml
	...
	ProductAdmin:
		MENUTITLE: 'My Product Admin'

Only when no language file is found for the current language, or the MENUITEM entity is not present in them, will 
$menu_title be used directly. 

The correct value for MENUTITLE will be created automatically if you use 
[i18nTextCollector](../reference/ii8n#collecting-text) to create your languagefile. 
If you're changing the menu title for a module with existing languagefiles, please check the values for 
MENUTITLE and adapt.

For more information on language and translations,please refer to [i18n](../reference/ii8n).
	
## Other changes ##

Other changes to the appearance of the menu buttons, or any other parts of the CMS for that matter, should 
preferrably be done using stylesheets. Have a look at [How to extend the CMS interface](extend-cms-interface) for 
an extensive howto on extending the CMS.