# Rich-Text Editing (WYSIWYG)

## Introduction

Editing and formatting content is the bread and butter of every content management system,
which is why SilverStripe has a tight integration with our preferred editor library, [TinyMCE](http://tinymce.com).
On top of the base functionality, we use our own insertion dialogs to ensure
you can effectively select and upload files.

## Usage

The framework comes with a `[api:HTMLEditorField]` form field class which encapsulates most of the required functionality.
It is usually added through the `[api:DataObject->getCMSFields()]` method:

	:::php
	class MyObject extends DataObject {
		static $db = array('Content' => 'HTMLText');

		public function getCMSFields() {
			return new FieldList(new HTMLEditorField('Content'));
		}
	}

## Configuration

To keep the JavaScript editor configuration manageable and extensible,
we've wrapped it in a PHP class called `[api:HtmlEditorConfig]`.
The class comes with its own defaults, which are extended through the `_config.php`
files in the framework (and the `cms` module in case you've got that installed).
There can be multiple configs, which should always be created / accessed using `[api:HtmlEditorConfig::get]. 
You can then set  the currently active config using `set_active()`. 
By default, a config named 'cms' is used in any field created throughout the CMS interface.

Example: Enable the "media" plugin:

	:::php
	// File: mysite/_config.php
	HtmlEditorConfig::get('cms')->enablePlugins('media');

Example: Remove some buttons for more advanced formatting

	:::php
	// File: mysite/_config.php
	HtmlEditorConfig::get('cms')->removeButtons('tablecontrols', 'blockquote', 'hr');

## Image and Media Insertion

The `[api:HtmlEditorField]` API also handles inserting images and media 
files into the managed HTML content. It can be used both for referencing
files on the webserver filesystem (through the `[api:File]` and `[api:Image]` APIs),
as well as hotlinking files from the web.

## oEmbed: Embedding media through external services

The ["oEmbed" standard](http://www.oembed.com/) is implemented by many media services
around the web, allowing easy representation of files just by referencing a website URL.
For example, a content author can insert a playable youtube video just by knowing
its URL, as opposed to dealing with manual HTML code.

oEmbed powers the "Insert from web" feature available through `[api:HtmlEditorField]`.
Internally, it makes HTTP queries to a list of external services
if it finds a matching URL. These services are described in the `Oembed.providers` configuration.
Since these requests are performed on page rendering, they typically have a long cache time (multiple days). To refresh a cache, append `?flush=1` to a URL.

To disable oEmbed usage, set the `Oembed.enabled` configuration property to "false".

## Recipes

### Customizing the "Insert" panels

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
	Object::add_extension('HtmlEditorField', 'MyToolbarExtension');

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
In the CMS, those dialogs are automatically instanciated, but in your own interfaces outside
of the CMS you have to take care of instanciation yourself:

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
	<% with EditorToolbar %>
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

## Related

 * [Howto: Extend the CMS Interface](../howto/extend-cms-interface)
