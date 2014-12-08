title: Rich-text editing (WYSIWYG)
summary: SilverStripe's use and configuration of TinyMCE html editor.

# Rich-text editing (WYSIWYG)

Editing and formatting content is the bread and butter of every content management system, which is why SilverStripe 
has a tight integration with our preferred editor library, [TinyMCE](http://tinymce.com).

On top of the base functionality, we use our own insertion dialogs to ensure you can effectively select and upload 
files. In addition to the markup managed by TinyMCE, we use [shortcodes](../../extending/shortcodes) to store 
information about inserted images or media elements.

The framework comes with a `[api:HTMLEditorField]` form field class which encapsulates most of the required
functionality. It is usually added through the `[api:DataObject->getCMSFields()]` method:

**mysite/code/MyObject.php**

	:::php
	<?php

	class MyObject extends DataObject {
		
		private static $db = array(
			'Content' => 'HTMLText'
		);
		
		public function getCMSFields() {
			return new FieldList(
				new HTMLEditorField('Content')
			);
		}
	}

## Configuration

To keep the JavaScript editor configuration manageable and extensible, we've wrapped it in a PHP class called 
`[api:HtmlEditorConfig]`. The class comes with its own defaults, which are extended through the [Configuration API](../../configuration)
in the framework (and the `cms` module in case you've got that installed).

There can be multiple configs, which should always be created / accessed using `[api:HtmlEditorConfig::get]`. You can 
then set the currently active config using `set_active()`.

<div class="info" markdown="1">
By default, a config named 'cms' is used in any field created throughout the CMS interface.
</div>

<div class="notice" markdown='1'>
Currently the order in which the `_config.php` files are executed depends on the module directory names. Execution 
order is alphabetical, so if you set a TinyMCE option in the `aardvark/_config.php`, this will be overridden in 
`framework/admin/_config.php` and your modification will disappear.
</div>

## Adding and removing capabilities

In its simplest form, the configuration of the editor includes adding and removing buttons and plugins.

You can add plugins to the editor using the Framework's `[api:HtmlEditorConfig::enablePlugins]` method. This will
transparently generate the relevant underlying TinyMCE code.

**mysite/_config.php**
	:::php
	HtmlEditorConfig::get('cms')->enablePlugins('media');

<div class="notice" markdown="1">
This utilities the TinyMCE's `PluginManager::load` function under the hood (check the 
[TinyMCE documentation on plugin loading](http://www.tinymce.com/wiki.php/API3:method.tinymce.AddOnManager.load) for 
details).
</div>

Plugins and advanced themes can provide additional buttons that can be added (or removed) through the
configuration. Here is an example of adding a `ssmacron` button after the `charmap` button:

**mysite/_config.php**
	:::php
	HtmlEditorConfig::get('cms')->insertButtonsAfter('charmap', 'ssmacron');

Buttons can also be removed:

**mysite/_config.php**
	:::php
	HtmlEditorConfig::get('cms')->removeButtons('tablecontrols', 'blockquote', 'hr');

<div class="notice" markdown="1">
Internally `[api:HtmlEditorConfig]` uses the TinyMCE's `theme_advanced_buttons` option to configure these. See the 
[TinyMCE documentation of this option](http://www.tinymce.com/wiki.php/Configuration:theme_advanced_buttons_1_n)
for more details.
</div>

### Setting options

TinyMCE behaviour can be affected through its [configuration options](http://www.tinymce.com/wiki.php/Configuration).
These options will be passed straight to the editor.

One example of the usage of this capability is to redefine the TinyMCE's [whitelist of HTML
tags](http://www.tinymce.com/wiki.php/Configuration:extended_valid_elements) - the tags that will not be stripped
from the HTML source by the editor.

**mysite/_config.php**
	:::php
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

<div class="notice" markdown="1">
The default setting for the CMS's `extended_valid_elements` we are overriding here can be found in 
`framework/admin/_config.php`.
</div>

## Writing custom plugins

It is also possible to add custom buttons to TinyMCE. A simple example of this is SilverStripe's `ssmacron` plugin. The 
source can be found in the Framework's `thirdparty/tinymce_ssmacron` directory.

Here is how we can create a project-specific plugin. Create a `mysite/javascript/myplugin` directory, add the plugin 
button icon - here `myplugin.png` - and the source code - here `editor_plugin.js`. Here is a very simple example of a 
plugin that adds a button to the editor:

	:::js
	(function() {
		tinymce.create('tinymce.plugins.myplugin', {
			
			init : function(ed, url) {
				var self = this;
				
				ed.addButton ('myplugin', {
					'title' : 'My plugin',
					'image' : url+'/myplugin.png',
					'onclick' : function () {
						alert('Congratulations! Your plugin works!');
					}
				});
				
			},
			
			getInfo : function() {
				return {
					longname  : 'myplugin',
					author	  : 'Me',
					authorurl : 'http://me.org.nz/',
					infourl   : 'http://me.org.nz/myplugin/',
					version   : "1.0"
				};
			}
		});
		
		tinymce.PluginManager.add('myplugin', tinymce.plugins.myplugin);
	})();

You can then enable this plugin through the [api:HtmlEditorConfig::enablePlugins]:

**mysite/_config.php**
	:::php
	HtmlEditorConfig::get('cms')->enablePlugins(array('myplugin' => '../../../mysite/javascript/myplugin/editor_plugin.js'));

For more complex examples see the [Creating a Plugin](http://www.tinymce.com/wiki.php/Creating_a_plugin) in TinyMCE
documentation, or browse through plugins that come with the Framework at `thirdparty/tinymce/plugins`.

## Image and media insertion

The `[api:HtmlEditorField]` API also handles inserting images and media files into the managed HTML content. It can be 
used both for referencing files on the webserver filesystem (through the `[api:File]` and `[api:Image]` APIs), as well 
as hotlinking files from the web. 

We use [shortcodes](../../configuration/shortcodes) to store information about inserted images or media elements. The 
[api:ShortcodeParser] API post-processes the HTML content on rendering, and replaces the shortcodes accordingly. It also 
takes care of care of placing the shortcode replacements relative to its surrounding markup (e.g. left/right alignment).

## oEmbed: Embedding media through external services

The ["oEmbed" standard](http://www.oembed.com/) is implemented by many media services around the web, allowing easy 
representation of files just by referencing a website URL. For example, a content author can insert a playable youtube 
video just by knowing its URL, as opposed to dealing with manual HTML code.

oEmbed powers the "Insert from web" feature available through `[api:HtmlEditorField]`. Internally, it makes HTTP 
queries to a list of external services if it finds a matching URL. These services are described in the 
`Oembed.providers` configuration. Since these requests are performed on page rendering, they typically have a long 
cache time (multiple days). 

<div class="info" markdown="1">
To refresh a oEmbed cache, append `?flush=1` to a URL.
</div>

To disable oEmbed usage, set the `Oembed.enabled` configuration property to "false".

### Doctypes

Since TinyMCE generates markup, it needs to know which doctype your documents will be rendered in. You can set this 
through the [element_format](http://www.tinymce.com/wiki.php/Configuration:element_format) configuration variable. It 
defaults to the stricter 'xhtml' setting, for example rendering self closing tags like `<br/>` instead of `<br>`.

In case you want to adhere to HTML4 instead, use the following configuration:

	:::php
	HtmlEditorConfig::get('cms')->setOption('element_format', 'html');

By default, TinyMCE and SilverStripe will generate valid HTML5 markup, but it will strip out HTML5 tags like 
`<article>` or `<figure>`. If you plan to use those, add them to the 
[valid_elements](http://www.tinymce.com/wiki.php/Configuration:valid_elements) configuration setting.

Also, the `[api:SS_HTMLValue]` API underpinning the HTML processing parses the markup into a temporary object tree 
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

	:::php
	// File: mysite/code/MyToolbarExtension.php
	class MyToolbarExtension extends Extension {
		public function updateFieldsForImage(&$fields, $url, $file) {
			$fields->removeByName('Caption');
		}
	}

	:::php
	// File: mysite/_config.php
	HtmlEditorField_Toolbar::add_extension('MyToolbarExtension');

Adding functionality is a bit more advanced, you'll most likely
need to add some fields to the PHP forms, as well as write some
JavaScript to ensure the values from those fields make it into the content
elements (and back out in case an existing element gets edited).
There's lots of extension points in the `[api:HtmlEditorField_Toolbar]` class
to get you started.

### Security groups with their own editor configuration

Different groups of authors can be assigned their own config,
e.g. a more restricted rule set for content reviewers (see the "Security" )
The config is available on each user record through `[api:Member->getHtmlEditorConfigForCMS()]`.
The group assignment is done through the "Security" interface for each `[api:Group]` record.
Note: The dropdown is only available if more than one config exists.

### Using the editor outside of the CMS

Each interface can have multiple fields of this type, each with their own toolbar to set formatting
and insert HTML elements. They do share one common set of dialogs for inserting links and other media though,
encapsulated in the `[api:HtmlEditorField_Toolbar]` class.
In the CMS, those dialogs are automatically instantiate, but in your own interfaces outside
of the CMS you have to take care of instantiate yourself:

	:::php
	// File: mysite/code/MyController.php
	class MyObjectController extends Controller {
		public function EditorToolbar() {
			return HtmlEditorField_Toolbar::create($this, "EditorToolbar");
		}
	}

	:::ss
	// File: mysite/templates/MyController.ss
	$Form
	<% with $EditorToolbar %>
		$MediaForm
		$LinkForm
	<% end_with %>

Note: The dialogs rely on CMS-access, e.g. for uploading and browsing files,
so this is considered advanced usage of the field.

	:::php
	// File: mysite/_config.php
	HtmlEditorConfig::get('cms')->disablePlugins('ssbuttons');
	HtmlEditorConfig::get('cms')->removeButtons('sslink', 'ssmedia');
	HtmlEditorConfig::get('cms')->addButtonsToLine(2, 'link', 'media');

### Developing a wrapper to use a different WYSIWYG editors with HTMLEditorField

WYSIWYG editors are complex beasts, so replacing it completely is a difficult task.
The framework provides a wrapper implementation for the basic required functionality,
mainly around selecting and inserting content into the editor view.
Have a look in `HtmlEditorField.js` and the `ss.editorWrapper` object to get you started
on your own editor wrapper. Note that the `[api:HtmlEditorConfig]` is currently hardwired to support TinyMCE,
so its up to you to either convert existing configuration as applicable,
or start your own configuration.

### Integrating a Spellchecker for TinyMCE

The TinyMCE editor uses spellchecking integrated into the browser if possible
([docs](http://www.tinymce.com/wiki.php/Plugin3x:spellchecker)).
Most modern browsers support it, although Internet Explorer only has limited
support in IE10. Alternatively, you can use the PSpell PHP module for server side checks.
Assuming you have the module installed, here's how you enable its use in `mysite/_config.php`:

	:::php
	HtmlEditorConfig::get('cms')->enablePlugins('spellchecker', 'contextmenu');
	HtmlEditorConfig::get('cms')->addButtonsToLine(2, 'spellchecker');
	HtmlEditorConfig::get('cms')->setOption(
		'spellchecker_rpc_url', 
		THIRDPARTY_DIR . '/tinymce-spellchecker/rpc.php'
	);
	HtmlEditorConfig::get('cms')->setOption('browser_spellcheck', false);

Now change the default spellchecker in `framework/thirdparty/tinymce-spellchecker/config.php`:

	:::php
	// ...
	$config['general.engine'] = 'PSpell';
