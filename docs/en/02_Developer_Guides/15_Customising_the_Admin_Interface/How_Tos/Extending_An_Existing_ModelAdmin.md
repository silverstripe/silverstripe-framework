## Extending existing ModelAdmin

Sometimes you'll work with ModelAdmins from other modules. To customize these interfaces, you can always subclass. But there's
also another tool at your disposal: The `[api:Extension]` API.

	:::php
	class MyAdminExtension extends Extension {
		// ...
		public function updateEditForm(&$form) {
			$form->Fields()->push(/* ... */)
		}
	}

Now enable this extension through your `[config.yml](/topics/configuration)` file.

	:::yml
	MyAdmin:
	  extensions:
	    - MyAdminExtension

The following extension points are available: `updateEditForm()`, `updateSearchContext()`,
`updateSearchForm()`, `updateList()`, `updateImportForm`.