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
 	
	[my_shortcode]
	[my_shortcode /]
	[my_shortcode,myparameter="value"]
	[my_shortcode,myparameter="value"]Enclosed Content[/my_shortcode]

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
		private static $db = array('Content' => 'HTMLText');
		private static $casting = array('ContentHighlighted' => 'HTMLText');
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

	ShortcodeParser::get('default')->register('my_shortcode', <callback>);
 
These parameters are passed to the callback:

 - Any parameters attached to the shortcode as an associative array (keys are lower-case).
 - Any content enclosed within the shortcode (if it is an enclosing shortcode). Note that any content within this
   will not have been parsed, and can optionally be fed back into the parser.
 - The ShortcodeParser instance used to parse the content.
 - The shortcode tag name that was matched within the parsed content.
 - An associative array of extra information about the shortcode being parsed. For example, if the shortcode is
   is inside an attribute, the `element` key contains a reference to the parent `DOMElement`, and the `node`
   key the attribute's `DOMNode`.

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
for more advanced editing interfaces (with visual placeholders). See the built-in `embed` shortcode as an example
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

 
## Syntax

 * Unclosed - `[shortcode]`
 * Explicitly closed - `[shortcode/]`
 * With parameters, mixed quoting - `[shortcode parameter=value parameter2='value2' parameter3="value3"]`
 * Old style parameter separation - `[shortcode,parameter=value,parameter2='value2',parameter3="value3"]`
 * With contained content & closing tag - `[shortcode]Enclosed Content[/shortcode]`
 * Escaped (will output `[just] [text]` in response) - `[[just] [[text]]`

### Attribute and element scope

HTML with unprocessed shortcodes in it is still valid HTML. As a result, shortcodes can be in two places in HTML:

 - In an attribute value, like so: `<a title="[title]">link</a>`
 - In an element's text, like so: `<p>Some text [shortcode] more text</p>`

The first is called "element scope" use, the second "attribute scope"

You may not use shortcodes in any other location. Specifically, you can not use shortcodes to generate attributes or 
change the name of a tag. These usages are forbidden:

	<[paragraph]>Some test</[paragraph]>

	<a [titleattribute]>link</a>

You may need to escape text inside attributes `>` becomes `&gt;`,
You can include HTML tags inside a shortcode tag, but you need to be careful of nesting to ensure you don't
break the output
    
Good:

	<div>
		[shortcode]
			<p>Caption</p>
		[/shortcode]
	</div>

Bad:

	<div>
		[shortcode]
	</div>
	<p>
		[/shortcode]
	</p>

### Location

Element scoped shortcodes have a special ability to move the location they are inserted at to comply with
HTML lexical rules. Take for example this basic paragraph tag:

	<p><a href="#">Head [figure,src="assets/a.jpg",caption="caption"] Tail</a></p>
	
When converted naively would become

	<p><a href="#">Head <figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure> Tail</a></p>

However this is not valid HTML - P elements can not contain other block level elements.

To fix this you can specify a "location" attribute on a shortcode. When the location attribute is "left" or "right"
the inserted content will be moved to immediately before the block tag. The result is this:

	<figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#">Head  Tail</a></p>

When the location attribute is "leftAlone" or "center" then the DOM is split around the element. The result is this:

	<p><a href="#">Head </a></p><figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#"> Tail</a></p>

### Parameter values

Here is a summary of the callback parameter values based on some example shortcodes.

Short

	[my_shortcodes]

	$attributes      => array()
	$enclosedContent => null
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

Short with attributes

	[my_shortcode,attribute="foo",other="bar"]

	$attributes      => array ('attribute'  => 'foo', 'other'      => 'bar')
	$enclosedContent => null
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

Long with attributes

	[my_shortcode,attribute="foo"]content[/my_shortcode]

	$attributes      => array('attribute' => 'foo')
	$enclosedContent => 'content'
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

## Limitations

Since the shortcode parser is based on a simple regular expression it cannot properly handle nested shortcodes. For
example the below code will not work as expected:

	[shortcode]
	[shortcode][/shortcode]
	[/shortcode]

The parser will raise an error if it can not find a matching opening tag for any particular closing tag

## Related

  * [Wordpress implementation](http://codex.wordpress.org/Shortcode_API)
