# How to customize the CMS Menu

## Adding an administration panel

Every time you add a new extension of the `[api:LeftAndMain]` class to the CMS,
SilverStripe will automatically create a new `[api:CMSMenuItem]` for it

The most popular extension of LeftAndMain is a `[api:ModelAdmin]` class, so
for a more detailed introduction to creating new `ModelAdmin` interfaces, read
the [ModelAdmin reference](../modeladmin).

In this document we'll take the `ProductAdmin` class used in the
[ModelAdmin reference](../modeladmin#setup) and so how we can change
the menu behaviour by using the `$menu_title` and `$menu_icon` statics to
provide a custom title and icon.

### Defining a Custom Icon

First we'll need a custom icon. For this purpose SilverStripe uses 16x16
black-and-transparent PNG graphics. In this case we'll place the icon in
`mysite/images`, but you are free to use any location.

	:::php
	class ProductAdmin extends ModelAdmin {
		// ...
		private static $menu_icon = 'mysite/images/product-icon.png';
	}

### Defining a Custom Title

The title of menu entries is configured through the `$menu_title` static.
If its not defined, the CMS falls back to using the class name of the
controller, removing the "Admin" bit at the end.

	:::php
	class ProductAdmin extends ModelAdmin {
		// ...
		private static $menu_title = 'My Custom Admin';
	}

In order to localize the menu title in different languages, use the
`<classname>.MENUTITLE` entity name, which is automatically created when running
the i18n text collection.

For more information on language and translations, please refer to the
[i18n](../../ii8n) docs.

## Adding an external link to the menu

On top of your administration windows, the menu can also have external links
(e.g. to external reference). In this example, we're going to add a link to
Google to the menu.

First, we need to define a `[api:LeftAndMainExtension]` which will contain our
button configuration.

	:::php
	<?php

	class CustomLeftAndMain extends LeftAndMainExtension {

		public function init() {
			// unique identifier for this item. Will have an ID of Menu-$ID
			$id = 'LinkToGoogle';

			// your 'nice' title
			$title = 'Google';

			// the link you want to item to go to
			$link = 'http://google.com';

			// priority controls the ordering of the link in the stack. The
			// lower the number, the lower in the list
			$priority = -2;

			// Add your own attributes onto the link. In our case, we want to
			// open the link in a new window (not the original)
			$attributes = array(
				'target' => '_blank'
			);

			CMSMenu::add_link($id, $title, $link, $priority, $attributes);
		}
	}

To have the link appear, make sure you add the extension to the `LeftAndMain`
class. For more information about configuring extensions see the
[extensions reference](../extending/extensions).

	:::php
	LeftAndMain::add_extension('CustomLeftAndMain')


## Related

 * [How to extend the CMS interface](extend_cms_interface)
