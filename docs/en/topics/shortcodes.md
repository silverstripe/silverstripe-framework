# Shortcodes: Flexible Content Embedding

## Overview

The `[api:ShortcodeParser]` API is simple parser that allows you to map specifically
formatted content to a callback to transform them into something else.
You might know this concept from forum software which don't allow you to insert
direct HTML, instead resorting to a custom syntax. 

In the CMS, authors often want to insert content elements which go beyond
standard formatting, at an arbitrary position in their WYSIWYG editor.
Shortcodes are a semi-technical solution for this. A good example would
be embedding a 3D file viewer or a Google Map at a certain location.

Here's some syntax variations:
 	
	[myshortcode]
	[myshortcode /]
	[myshortcode,myparameter="value"]
	[myshortcode,myparameter="value"]Enclosed Content[/myshortcode]

## Usage

In its most basic form, you can invoke the `[api:ShortcodeParser]` directly:

	:::php
	ShortcodeParser::get_active()->parse($myvalue);

In addition, shortcodes are automatically parsed on any database field which is declared
as `[api:HTMLValue]` or `[api:HTMLText]`, when rendered into a template.
This means you can use shortcodes on common fields like `SiteTree.Content`,
and any other `[api:DataObject::$db]` definitions of these types.

In order to allow shortcodes in your own template placeholders,
ensure they're casted correctly:

	:::php
	class MyObject extends DataObject {
		static $db = array('Content' => 'HTMLText');
		static $casting = array('ContentHighlighted' => 'HTMLText');
		public function ContentHighlighted($term) {
			return str_replace($term, "<em>$term</em>", $this->Content);
		}
	}

There is currently no way to allow shortcodes directly in template markup
(as opposed to return values of template placeholders).

## Defining Custom Shortcodes
 
All you need to do to define a shortcode is to register a callback with the parser that will be called whenever a
shortcode is encountered. This callback will return a string to replace the shortcode with.
If the shortcode is used for template placeholders of type `HTMLText` or `HTMLVarchar`, 
the returned value should be valid HTML
 
To register a shortcode you call:

	ShortcodeParser::get('default')->register('myshortcode', <callback>);
 
These parameters are passed to the callback:

 - Any parameters attached to the shortcode as an associative array (keys are lower-case).
 - Any content enclosed within the shortcode (if it is an enclosing shortcode). Note that any content within this
   will not have been parsed, and can optionally be fed back into the parser.
 - The ShortcodeParser instance used to parse the content.
 - The shortcode tag name that was matched within the parsed content.
 
## Example: Google Maps Iframe by Address

To demonstrate how easy it is to build custom shortcodes, we'll build one to display
a Google Map based on a provided address. Format:

	[googlemap,width=500,height=300]97-99 Courtenay Place, Wellington, New Zealand[/googlemap]

So we've got the address as "content" of our new `googlemap` shortcode tags,
plus some `width` and `height` arguments. We'll add defaults to those in our shortcode parser so they're optional.

	:::php
	ShortcodeParser::get('default')->register('googlemap', function($arguments, $address, $parser, $shortcode) {
		$iframeUrl = sprintf(
			'http://maps.google.com/maps?q=%s&amp;hnear=%s&amp;ie=UTF8&hq=&amp;t=m&amp;z=14&amp;output=embed',
			urlencode($address),
			urlencode($address)
		);
		$width = (isset($args['width']) && $args['width']) ? $args['width'] : 400;
		$height = (isset($args['height']) && $args['height']) ? $args['height'] : 300;
		return sprintf(
			'<iframe width="%d" height="%d" src="%s" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe>',
			$width,
			$height,
			$iframeUrl
		);
	});

The hard bits are taken care of (parsing out the shortcodes), everything we need to do is a bit of string replacement.
CMS users still need to remember the specific syntax, but these shortcodes can form the basis
for more advanced editing interfaces (with visual placeholders). See the built-in `[embed]` shortcode as an example
for coupling shortcodes with a form to create and edit placeholders.

## Built-in Shortcodes

SilverStripe comes with several shortcode parsers already.

### Links

Internal page links keep references to their database IDs rather than
the URL, in order to make these links resilient against moving the target page to a different
location in the page tree. This is done through the `[sitetree_link]` shortcode, which
takes an `id` parameter. Example: `<a href="[sitetree_link,id=99]">`

Links to internal `File` database records work exactly the same, but with the `[file_link]` shortcode.

### Media (Photo, Video and Rich Content)

Many media formats can be embedded into websites through the `<object>`
tag, but some require plugins like Flash or special markup and attributes.
OEmbed is a standard to discover these formats based on a simple URL,
for example a Youtube link pasted into the "Insert Media" form of the CMS.

Since TinyMCE can't represent all these varations, we're showing a placeholder
instead, and storing the URL with a custom `[embed]` shortcode.

Example: `.[embed width=480 height=270 class=left thumbnail=http://i1.ytimg.com/vi/lmWeD-vZAMY/hqdefault.jpg?r=8767]http://www.youtube.com/watch?v=lmWeD-vZAMY[/embed]`

 
## Limitations
 
Since the shortcode parser is based on a simple regular expression it cannot properly handle nested shortcodes. For
example the below code will not work as expected:

	[shortcode]
  [shortcode][/shortcode]
  [/shortcode]

The parser will recognise this as:
 
	[shortcode]
  [shortcode]
  [/shortcode]

## Related

  * [Wordpress implementation](http://codex.wordpress.org/Shortcode_API)