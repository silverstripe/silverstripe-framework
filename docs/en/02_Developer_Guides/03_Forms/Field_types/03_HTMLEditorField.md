title: Rich-text editing (WYSIWYG)
summary: SilverStripe's use and configuration of TinyMCE html editor.

# Rich-text editing (WYSIWYG)

Editing and formatting content is the bread and butter of every content management system, which is why SilverStripe 
has a tight integration with our preferred editor library, [TinyMCE](http://tinymce.com).

On top of the base functionality, we use our own insertion dialogs to ensure you can effectively select and upload 
files. In addition to the markup managed by TinyMCE, we use [shortcodes](/developer_guides/extending/shortcodes) to store 
information about inserted images or media elements.

The framework comes with a [HTMLEditorField](api:SilverStripe\Forms\HTMLEditor\HTMLEditorField) form field class which encapsulates most of the required
functionality. It is usually added through the [DataObject::getCMSFields()](api:SilverStripe\ORM\DataObject::getCMSFields()) method:

**app/code/MyObject.php**


```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject 
{
    
    private static $db = [
        'Content' => 'HTMLText'
    ];
    
    public function getCMSFields() 
    {
        return new FieldList(
            new HTMLEditorField('Content')
        );
    }
}
```

### Specify which configuration to use

By default, a config named 'cms' is used in any new [HTMLEditorField](api:SilverStripe\Forms\HTMLEditor\HTMLEditorField).

If you have created your own [HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig) and would like to use it,
you can call `HtmlEditorConfig::set_active('myConfig')` and all subsequently created [HTMLEditorField](api:SilverStripe\Forms\HTMLEditor\HTMLEditorField)
will use the configuration with the name 'myConfig'.

You can also specify which [HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig) to use on a per field basis via the construct argument.
This is particularly useful if you need different configurations for multiple [HTMLEditorField](api:SilverStripe\Forms\HTMLEditor\HTMLEditorField) on the same page or form.


```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject 
{
    private static $db = [
        'Content' => 'HTMLText',
        'OtherContent' => 'HTMLText'
    ];
    
    public function getCMSFields() 
    {
        return new FieldList([
            new HTMLEditorField('Content'),
            new HTMLEditorField('OtherContent', 'Other content', $this->OtherContent, 'myConfig')
        ]);
    }
}

```

In the above example, the 'Content' field will use the default 'cms' config while 'OtherContent' will be using 'myConfig'.

## Configuration

To keep the JavaScript editor configuration manageable and extensible, we've wrapped it in a PHP class called 
[HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig). The class comes with its own defaults, which are extended through the [Configuration API](../../configuration)
in the framework (and the `cms` module in case you've got that installed).

There can be multiple configs, which should always be created / accessed using [HtmlEditorConfig::get()](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig::get()). You can 
then set the currently active config using `set_active()`.

<div class="info" markdown="1">
</div>

<div class="notice" markdown='1'>
Currently the order in which the `_config.php` files are executed depends on the module directory names. Execution 
order is alphabetical, so if you set a TinyMCE option in the `aardvark/_config.php`, this will be overridden in 
`vendor/silverstripe/framework/admin/_config.php` and your modification will disappear.
</div>

## Adding and removing capabilities

In its simplest form, the configuration of the editor includes adding and removing buttons and plugins.

You can add plugins to the editor using the Framework's [HtmlEditorConfig::enablePlugins()](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig::enablePlugins()) method. This will
transparently generate the relevant underlying TinyMCE code.

**app/_config.php**

```php
use SilverStripe\Forms\HTMLEditor\HtmlEditorConfig;

HtmlEditorConfig::get('cms')->enablePlugins('media');
```

<div class="notice" markdown="1">
This utilities the TinyMCE's `PluginManager::load` function under the hood (check the 
[TinyMCE documentation on plugin loading](http://www.tinymce.com/wiki.php/API3:method.tinymce.AddOnManager.load) for 
details).
</div>

Plugins and advanced themes can provide additional buttons that can be added (or removed) through the
configuration. Here is an example of adding a `ssmacron` button after the `charmap` button:

**app/_config.php**

```php
HtmlEditorConfig::get('cms')->insertButtonsAfter('charmap', 'ssmacron');
```

Buttons can also be removed:

**app/_config.php**

```php
HtmlEditorConfig::get('cms')->removeButtons('tablecontrols', 'blockquote', 'hr');
```

<div class="notice" markdown="1">
Internally [HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig) uses the TinyMCE's `theme_advanced_buttons` option to configure these. See the 
[TinyMCE documentation of this option](http://www.tinymce.com/wiki.php/Configuration:theme_advanced_buttons_1_n)
for more details.
</div>

### Setting options

TinyMCE behaviour can be affected through its [configuration options](http://www.tinymce.com/wiki.php/Configuration).
These options will be passed straight to the editor.

One example of the usage of this capability is to redefine the TinyMCE's [whitelist of HTML
tags](http://www.tinymce.com/wiki.php/Configuration:extended_valid_elements) - the tags that will not be stripped
from the HTML source by the editor.

**app/_config.php**

```php
// Add start and type attributes for <ol>, add <object> and <embed> with all attributes.
HtmlEditorConfig::get('cms')->setOption(
    'extended_valid_elements',
    'img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|usemap],' .
    'iframe[src|name|width|height|title|align|allowfullscreen|frameborder|marginwidth|marginheight|scrolling],' .
    'object[classid|codebase|width|height|data|type],' .
    'embed[src|type|pluginspage|width|height|autoplay],' .
    'param[name|value],' .
    'map[class|name|id],' .
    'area[shape|coords|href|target|alt],' .
    'ol[start|type]'
);
```

<div class="notice" markdown="1">
The default setting for the CMS's `extended_valid_elements` we are overriding here can be found in 
`vendor/silverstripe/framework/admin/_config.php`.
</div>

## Writing custom plugins

It is also possible to add custom plugins to TinyMCE, for example toolbar buttons.
You can enable them through [HtmlEditorConfig::enablePlugins()](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig::enablePlugins()):

**app/_config.php**

```php
HtmlEditorConfig::get('cms')->enablePlugins(['myplugin' => '../../../app/javascript/myplugin/editor_plugin.js']);
```

You can learn how to [create a plugin](http://www.tinymce.com/wiki.php/Creating_a_plugin) from the TinyMCE documentation.

## Image and media insertion

The [HtmlEditorField](api:SilverStripe\Forms\HTMLEditor\HtmlEditorField) API also handles inserting images and media files into the managed HTML content. It can be 
used both for referencing files on the webserver filesystem (through the [File](api:SilverStripe\Assets\File) and [Image](api:SilverStripe\Assets\Image) APIs), as well 
as hotlinking files from the web. 

We use [shortcodes](/developer_guides/extending/shortcodes) to store information about inserted images or media elements. The 
[ShortcodeParser](api:SilverStripe\View\Parsers\ShortcodeParser) API post-processes the HTML content on rendering, and replaces the shortcodes accordingly. It also 
takes care of care of placing the shortcode replacements relative to its surrounding markup (e.g. left/right alignment).

## oEmbed: Embedding media through external services

The ["oEmbed" standard](http://www.oembed.com/) is implemented by many media services around the web, allowing easy 
representation of files just by referencing a website URL. For example, a content author can insert a playable youtube 
video just by knowing its URL, as opposed to dealing with manual HTML code.

oEmbed powers the "Insert from web" feature available through 
[HtmlEditorField](api:SilverStripe\Forms\HTMLEditor\HtmlEditorField). Internally this service is provided
by the [embed](https://github.com/oscarotero/Embed) library.

To disable oembed you will need to follow the below to remove the plugin from tinymce, as well
as disabling the internal service via yml:

```yaml
---
Name: oembed-disable
---
SilverStripe\AssetAdmin\Forms\RemoteFileFormFactory:
  enabled: false
```

```php
HtmlEditorConfig::get('cms')->disablePlugins('ssembed');
```

## Limiting oembed URLs

HtmlEditorField can have whitelists set on both the scheme (default http & https) and domains allowed when
inserting files for use with oembed.

This is performed through the config variables on the 
[RemoteFileFormFactory](api:SilverStripe\AssetAdmin\Forms\RemoteFileFormFactory) class:

```yaml
---
Name: oembed-restrictions
---
SilverStripe\AssetAdmin\Forms\RemoteFileFormFactory:
  fileurl_scheme_whitelist:
    - https
    - http
  fileurl_scheme_blacklist:
    - ftp
  fileurl_domain_whitelist:
    - google.com
  fileurl_domain_blacklist:
    - localhost
  fileurl_port_whitelist:
    - 80
    - 443
  fileurl_port_blacklist:
    - 23
```

This allows a white or blacklist to be applied to schema, domain, or port (if provided). Note that
both blacklist and whitelist need to match, and are only ignored if the rules are empty for any
of the above values.

By default live sites (SS_ENVIRONMENT_TYPE="live") will not attempt to resolve oembed urls that
point to localhost to protect your site from cross site request forgery.

### Doctypes

Since TinyMCE generates markup, it needs to know which doctype your documents will be rendered in. You can set this 
through the [element_format](http://www.tinymce.com/wiki.php/Configuration:element_format) configuration variable. It 
defaults to the stricter 'xhtml' setting, for example rendering self closing tags like `<br/>` instead of `<br>`.

In case you want to adhere to HTML4 instead, use the following configuration:


```php
HtmlEditorConfig::get('cms')->setOption('element_format', 'html');
```

By default, TinyMCE and SilverStripe will generate valid HTML5 markup, but it will strip out HTML5 tags like 
`<article>` or `<figure>`. If you plan to use those, add them to the 
[valid_elements](http://www.tinymce.com/wiki.php/Configuration:valid_elements) configuration setting.

Also, the [HTMLValue](api:SilverStripe\View\Parsers\HTMLValue) API underpinning the HTML processing parses the markup into a temporary object tree 
which can be traversed and modified before saving. The built-in parser only supports HTML4 and XHTML syntax. In order 
to successfully process HTML5 tags, please use the 
['silverstripe/html5' module](https://github.com/silverstripe/silverstripe-html5).

## Recipes

### Customising the "Insert" panels

In the standard installation, you can insert links (internal/external/anchor/email),
images as well as flash media files. The forms used for preparing the new content element
are rendered by SilverStripe, but there's some JavaScript involved to transfer
back and forth between a content representation the editor can understand, present and save.

Example: Remove field for "image captions"


```php
use SilverStripe\Core\Extension;

// File: app/code/MyToolbarExtension.php
class MyToolbarExtension extends Extension 
{
    public function updateFieldsForImage(&$fields, $url, $file) 
    {
        $fields->removeByName('CaptionText');
    }
}
```

```php
// File: app/_config.php
use SilverStripe\Admin\ModalController;

ModalController::add_extension('MyToolbarExtension');
```

Adding functionality is a bit more advanced, you'll most likely
need to add some fields to the PHP forms, as well as write some
JavaScript to ensure the values from those fields make it into the content
elements (and back out in case an existing element gets edited).
There's lots of extension points in the [ModalController](api:SilverStripe\Admin\ModalController) class
to get you started.

### Security groups with their own editor configuration

Different groups of authors can be assigned their own config,
e.g. a more restricted rule set for content reviewers (see the "Security" )
The config is available on each user record through [Member::getHtmlEditorConfigForCMS()](api:SilverStripe\Security\Member::getHtmlEditorConfigForCMS()).
The group assignment is done through the "Security" interface for each [Group](api:SilverStripe\Security\Group) record.
Note: The dropdown is only available if more than one config exists.

### Using the editor outside of the CMS

Each interface can have multiple fields of this type, each with their own toolbar to set formatting
and insert HTML elements. They do share one common set of dialogs for inserting links and other media though,
encapsulated in the [ModalController](api:SilverStripe\Admin\ModalController) class.
In the CMS, those dialogs are automatically instantiate, but in your own interfaces outside
of the CMS you have to take care of instantiate yourself:


```php
use SilverStripe\Admin\ModalController;
use SilverStripe\Control\Controller;

// File: app/code/MyController.php
class MyObjectController extends Controller 
{
    public function Modals() 
    {
        return ModalController::create($this, "Modals");
    }
}
```

Note: The dialogs rely on CMS-access, e.g. for uploading and browsing files,
so this is considered advanced usage of the field.

```php
// File: app/_config.php
HtmlEditorConfig::get('cms')->disablePlugins('ssbuttons');
HtmlEditorConfig::get('cms')->removeButtons('sslink', 'ssmedia');
HtmlEditorConfig::get('cms')->addButtonsToLine(2, 'link', 'media');
```

### Developing a wrapper to use a different WYSIWYG editors with HTMLEditorField

WYSIWYG editors are complex beasts, so replacing it completely is a difficult task.
The framework provides a wrapper implementation for the basic required functionality,
mainly around selecting and inserting content into the editor view.
Have a look in `HtmlEditorField.js` and the `ss.editorWrapper` object to get you started
on your own editor wrapper. Note that the [HtmlEditorConfig](api:SilverStripe\Forms\HTMLEditor\HtmlEditorConfig) is currently hardwired to support TinyMCE,
so its up to you to either convert existing configuration as applicable,
or start your own configuration.
