---
title: Using ElementalArea with DataObject
summary: Learn how to add ElementalArea to your models and manage it in ModalAdmin
---

# Using ElementalArea with DataObject

## Creaating Model and adding ElementalArea

When you create DataObject you should define connection between your DataObject and ElementalArea by adding $has_one relationship.
The following code creates CMS Admin section where CMS user can manage the BlogPosts.

Let's look at a simple example:

**app/src/Admins/BlogPostsAdmin.php**

```php
namespace App\Admins;

use App\Models\BlogPost;
use SilverStripe\Admin\ModelAdmin;

class BlogPostsAdmin extends ModelAdmin
{

    private static string $url_segment = 'blog-posts-admin';

    private static string $menu_title = 'Blog Posts';

    private static string $menu_icon_class = 'font-icon-block-banner';

    private static array $managed_models = [
        BlogPost::class,
    ];
}
```

**app/src/Models/BlogPost.php**

```php

namespace App\Models;

use App\Admins\BlogPostsAdmin;

class BlogPost extends DataObject
{

    private static string $table_name = 'BlogPost';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
    ]

    private static $has_one = [
        'ElementalArea' => ElementalArea::class,
    ];

    ...
}
 ```

If you are planning to use ElementalArea together with DataObject, it is important to define a few following methods in your class.
The Elemetnal envokes these methods to collaborate with elements in the DataObject.
First of all, CMSEditLink() method should be defined to provide ability to open partucular BlogPost editing section.

```php
...

class BlogPost extends DataObject
{
    ...

    public function CMSEditLink()
    {
        // In this example we use BlogPostsAdmin class as Controller
        $admin = BlogPostsAdmin::singleton();

        // Makes link more readable. Instead App\Models\ BlogPost, we get App-Models-BlogPost
        $sanitisedClassname = str_replace('\\', '-', $this->ClassName);

        // Returns link to editing section with elements
        return Controller::join_links(
            $admin->Link($sanitisedClassname),
            'EditForm/field/',
            $sanitisedClassname,
            'item',
            $this->ID,
        );
    }

    ...
    
}

```

Method Link() doesn't exist in DataObject class, so you should define this method in your class. The Elemental use this method to open Preview tab.
You should add canArchive() method in your class as well to set rules for canDalete() method.
Use following code as an example to how simply create link.

```php
...

class BlogPost extends DataObject
{
    ...

    public function Link($action = null)
    {
        $admin = BlogPostsAdmin::singleton();

        return $this->ID
            ? Controller::join_links(
                $admin->Link(str_replace('\\', '-', $this->ClassName)),
                'cmsPreview',
                $this->ID
                )
            : null;
    }

    public function canArchive($member = null)
    {
        return $this->canDelete($member);
    }
}

```

## Creating Previewable DataObject in CMS admin section

Class BlogPost can implement CMSPreviewable interface to show a preview.
By making a following changes, you can append Preview window in the BlogPost Admin section

**app/src/Admins/BlogPostsAdmin.php**

```php
...

class BlogPostsAdmin extends ModelAdmin
{
    ...

    private static $allowed_actions = [
        'cmsPreview',
    ];

    private static $url_handlers = [
        '$ModelClass/cmsPreview/$ID' => 'cmsPreview',
    ];

    public function cmsPreview()
    {
        $id = $this->urlParams['ID'];
        $obj = $this->modelClass::get_by_id($id);
        if (!$obj || !$obj->exists()) {
            return $this->httpError(404);
        }

        // Include use of a front-end theme temporarily.
        $oldThemes = SSViewer::get_themes();
        SSViewer::set_themes(SSViewer::config()->get('themes'));
        $preview = $obj->forTemplate();

        // Make sure to set back to backend themes.
        SSViewer::set_themes($oldThemes);

        return $preview;
    }

}

```

**app/src/Models/BlogPost.php**

```php
...

class BlogPost extends DataObject implements CMSPreviewable
{
    ...

    public function PreviewLink($action = null)
    {
        $admin = BlogPostsAdmin::singleton();
        return $this->ID
            ? Controller::join_links(
                $admin->Link(str_replace('\\', '-', $this->ClassName)),
                'cmsPreview',
                $this->ID
                )
            : null;
    }

    // The following methods are required to show data in template

    public function getMimeType()
    {
        return 'text/html';
    }

    public function forTemplate($holder = true)
    {
        return $this->renderWith(['type' => 'Blogs', self::class]);
    }
}

```

## Related Documentation

* [Preview](/developer_guides/customising_the_admin_interface/preview/)