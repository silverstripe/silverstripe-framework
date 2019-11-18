---
title: SiteConfig
summary: Content author configuration through the SiteConfig module.
icon: laptop-code
---

# SiteConfig

The `SiteConfig` module provides a generic interface for managing site-wide settings or functionality which is used 
throughout the site. Out of the box, this includes setting the site name and site-wide access.

## Accessing variables

`SiteConfig` options can be accessed from any template by using the $SiteConfig variable.


```ss
$SiteConfig.Title 
$SiteConfig.Tagline

<% with $SiteConfig %>
    $Title $AnotherField
<% end_with %>
```

To access variables in the PHP:


```php
use Silverstripe\SiteConfig\SiteConfig;

$config = SiteConfig::current_site_config(); 

echo $config->Title;

// returns "Website Name"
```

## Extending SiteConfig

To extend the options available in the panel, define your own fields via a [DataExtension](api:SilverStripe\ORM\DataExtension).

**app/code/extensions/CustomSiteConfig.php**


```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataExtension;

class CustomSiteConfig extends DataExtension 
{
    
    private static $db = [
        'FooterContent' => 'HTMLText'
    ];

    public function updateCMSFields(FieldList $fields) 
    {
        $fields->addFieldToTab("Root.Main", 
            new HTMLEditorField("FooterContent", "Footer Content")
        );
    }
}
```

Then activate the extension.

**app/_config/app.yml**


```yml
Silverstripe\SiteConfig\SiteConfig:
  extensions:
    - CustomSiteConfig
```

[notice]
After adding the class and the YAML change, make sure to rebuild your database by visiting http://example.com/dev/build.
You may also need to reload the screen with a `?flush=1` i.e http://example.com/admin/settings?flush=1.
[/notice]

You can define as many extensions for `SiteConfig` as you need. For example, if you're developing a module and want to
provide the users a place to configure settings then the `SiteConfig` panel is the place to go it.

## API Documentation

* [SiteConfig](api:SilverStripe\SiteConfig\SiteConfig)


## Related Lessons
* [DataExtensions and SiteConfig](https://www.silverstripe.org/learn/lessons/v4/data-extensions-and-siteconfig-1)
