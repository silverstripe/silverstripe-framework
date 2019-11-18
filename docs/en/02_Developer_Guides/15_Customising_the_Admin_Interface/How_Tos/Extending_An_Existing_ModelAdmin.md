---
title: Extending an existing ModelAdmin
summary: ModelAdmin interfaces that come with the core can be customised easily
---
## Extending existing ModelAdmin

Sometimes you'll work with ModelAdmins from other modules. To customise these interfaces, you can always subclass. But there's
also another tool at your disposal: The [Extension](api:SilverStripe\Core\Extension) API.


```php
use SilverStripe\Core\Extension;

class MyAdminExtension extends Extension 
{
    // ...
    public function updateEditForm(&$form) 
    {
        $form->Fields()->push(/* ... */)
    }
}
```

Now enable this extension through your `[config.yml](/topics/configuration)` file.


```yml
MyAdmin:
  extensions:
    - MyAdminExtension
```

The following extension points are available: `updateEditForm()`, `updateSearchContext()`,
`updateSearchForm()`, `updateList()`, `updateImportForm`.
