---
title: File usage
summary: See file usage and customising the file "Used on" table
icon: compress-arrows-alt
---

# File Usage

CMS users can view where a file is used by accessing the Used On tab in the Files section. This feature allows them to identify what DataObjects depend on the file.

In the Files section of the CMS, click on a file to see file details.  Within the file details panel there is a
Used on tab that shows a table of Pages and other DataObjects where the file is used throughout the website.

## Customising the File "Used on" table in the Files section (asset-admin)

Your project specific DataObject will automatically be displayed on the Used on tab. This may not always be desirable, especially when working with background DataObjects the user can not interact with directly. Extensions can be applied
to the `UsedOnTable` class to update specific entries.

Extension hooks can be used to do the following:
- Exclude DataObjects of a particular type of class from being fetched from the database
- Exclude individual DataObjects that were fetched for showing on the used on table
- Link ancestors of a DataObject so they show on the same row of the used on table

### Example PHP file:

```php
<?php
namespace My\App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

class UsedOnTableExtension extends Extension
{

    // This extension hook will prevent type(s) of DataObjects from showing on the Used on tab in the Files section
    // This will prevent a MyDataObjectToExclude::get() call from being executed
    public function updateUsageExcludedClasses(array &$excludedClasses)
    {
        $excludedClasses[] = MyDataObjectToExclude::class;
    }

    // This extension hook will alter a DataObject after it was fetched via MyDataObject::get()
    // This allows a greater level of flexibility to exclude or modify individual DataObjects
    // It is less efficient to use this extension hook that `updateUsageExcludedClasses()` above
    public function updateUsageDataObject(DataObject $dataObject)
    {
        if (!($dataObject instanceof MyDataObject)) {
            return;
        }
        // Exclude DataObject from showing
        if ($dataObject->Title == 'lorem ipsum') {
            $dataObject = null;
        }
        // Show the DataObject's Parent() instead
        $dataObject = $dataObject->Parent();
    }

    // This extension hook is used to to show ancestor DataObjects in the used on table alongside the
    // DataObject the File is used on.  This is useful for two reasons:
    // - It gives context more context, for instance if File is used on a Content block, it can be used to show the
    //   Page that Content Block is used on
    // - The DataObject may not have a `CMSEditLink()` implementation, though the ancestor DataObject does.
    //   The CMS frontend will fallback to using the Ancestor `CMSEditLink()` for when a user clicks on a row on
    //   the used on table
    public function updateUsageAncestorDataObjects(array &$ancestorDataObjects, DataObject $dataObject)
    {
        if (!($dataObject instanceof MyDataObjectThatIWantToLink)) {
            return;
        }
        $parentObjectIWantToIgnore = $dataObject->MyParentComponent();
        $grandParentObjectIWantToLink = $parentObjectIWantToIgnore->MyParentComponent();
        // Add $grandParentObjectIWantToLink to ancestors, but not $parentObjectIWantToIgnore
        $ancestorDataObjects[] = $grandParentIWantToLink;
    }
}

### Example YML file:

```yml
SilverStripe\Admin\Forms\UsedOnTable:
  extensions:
    - My\App\Extensions\UsedOnTableExtension
```