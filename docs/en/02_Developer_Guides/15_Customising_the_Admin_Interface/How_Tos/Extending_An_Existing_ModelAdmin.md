---
title: Extending an existing ModelAdmin
summary: ModelAdmin interfaces that come with the core can be customised easily
---

## Extending existing ModelAdmin

Sometimes you'll work with ModelAdmins from other modules. To customise these interfaces, you can always subclass. But there's
also another tool at your disposal: The [api:Extension] API.

```php
	class MyAdminExtension extends Extension {
		// ...
		public function updateEditForm(&$form) {
			$form->Fields()->push(/* ... */)
		}
	}

```

```yml
	MyAdmin:
	  extensions:
	    - MyAdminExtension

```
`updateSearchForm()`, `updateList()`, `updateImportForm`.
