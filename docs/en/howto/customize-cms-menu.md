# How to customize the CMS Menu #

## Defining a Custom Icon ##

Every time you add a new extension of the `api:LeftAndMain` class to the CMS, SilverStripe will automatically create a new menu-item for it, with a default title and icon.
We can easily change that behaviour by using the static `$menu_title` and `$menu_icon` statics to
provide a custom title and icon.

The most popular extension of LeftAndMain is the `api:ModelAdmin` class, so we'll use that for an example. 
We'll take the `ProductAdmin` class used in the [ModelAdmin reference](../reference/modeladmin#setup).

First we'll need a custom icon. For this purpose SilverStripe uses 16x16 black-and-transparent PNG graphics.
In this case we'll place the icon in `mysite/images`, but you are free to use any location.

	:::php
	class ProductAdmin extends ModelAdmin {
		// ...
		static $menu_icon = 'mysite/images/product-icon.png'; 
	}

## Defining a Custom Title ##

The title of menu entries is configured through the `$menu_title` static.
If its not defined, the CMS falls back to using the class name of the controller,
removing the "Admin" bit at the end.

	:::php
	class ProductAdmin extends ModelAdmin {
		// ...
		static $menu_title = 'My Custom Admin'; 
	}
 
In order to localize the menu title in different languages, use the `<classname>.MENUTITLE`
entity name, which is automatically created when running the i18n text collection.
For more information on language and translations, please refer to the [i18n](../reference/ii8n) docs.
	
## Related

 * [How to extend the CMS interface](extend-cms-interface)