title: Shortcodes
summary: Flexible content embedding

# Shortcodes

The [api:ShortcodeParser] API is simple parser that allows you to map specifically formatted content to a callback to 
transform them into something else. You might know this concept from forum software which don't allow you to insert
direct HTML, instead resorting to a custom syntax. 

In the CMS, authors often want to insert content elements which go beyond standard formatting, at an arbitrary position 
in their WYSIWYG editor. Shortcodes are a semi-technical solution for this. A good example would be embedding a 3D file 
viewer or a Google Map at a certain location. 


	:::php
	$text = "<h1>My Map</h1>[map]"
	
	// Will output
	// <h1>My Map</h1><iframe ..></iframe>


Here's some syntax variations:


	:::php
	[my_shortcode]
	#
	[my_shortcode /]
	#
	[my_shortcode,myparameter="value"]
	#
	[my_shortcode,myparameter="value"]Enclosed Content[/my_shortcode]

Shortcodes are automatically parsed on any database field which is declared as [api:HTMLValue] or [api:HTMLText], 
when rendered into a template. This means you can use shortcodes on common fields like `SiteTree.Content`, and any 
other `[api:DataObject::$db]` definitions of these types.

Other fields can be manually parsed with shortcodes through the `parse` method.

	:::php
	$text = "My awesome [my_shortcode] is here.";
	ShortcodeParser::get_active()->parse($text);

## Defining Custom Shortcodes
 
First we need to define a callback for the shortcode.

**mysite/code/Page.php**

	:::php
	<?php

	class Page extends SiteTree {
		
		private static $casting = array(
			'MyShortCodeMethod' => 'HTMLText'
		);

		public static function MyShortCodeMethod($arguments, $content = null, $parser = null, $tagName) {
			return "<em>" . $tagName . "</em> " . $content . "; " . count($arguments) . " arguments.";
		}
	}

These parameters are passed to the `MyShortCodeMethod` callback:

 - Any parameters attached to the shortcode as an associative array (keys are lower-case).
 - Any content enclosed within the shortcode (if it is an enclosing shortcode). Note that any content within this
   will not have been parsed, and can optionally be fed back into the parser.
 - The ShortcodeParser instance used to parse the content.
 - The shortcode tag name that was matched within the parsed content.
 - An associative array of extra information about the shortcode being parsed. For example, if the shortcode is
   is inside an attribute, the `element` key contains a reference to the parent `DOMElement`, and the `node`
   key the attribute's `DOMNode`.


To register a shortcode you call the following.

**mysite/_config.php**

	:::php
	// ShortcodeParser::get('default')->register($shortcode, $callback);

	ShortcodeParser::get('default')->register('my_shortcode', array('Page', 'MyShortCodeMethod'));


## Built-in Shortcodes

SilverStripe comes with several shortcode parsers already.

### Links

Internal page links keep references to their database IDs rather than the URL, in order to make these links resilient 
against moving the target page to a different location in the page tree. This is done through the `[sitetree_link]` 
shortcode, which takes an `id` parameter. 

	:::php
	<a href="[sitetree_link,id=99]">

Links to internal `File` database records work exactly the same, but with the `[file_link]` shortcode.

	:::php
	<a href="[file_link,id=99]">

### Media (Photo, Video and Rich Content)

Many media formats can be embedded into websites through the `<object>` tag, but some require plugins like Flash or 
special markup and attributes. OEmbed is a standard to discover these formats based on a simple URL, for example a 
Youtube link pasted into the "Insert Media" form of the CMS.

Since TinyMCE can't represent all these variations, we're showing a placeholder instead, and storing the URL with a 
custom `[embed]` shortcode.


[embed width=480 height=270 class=left thumbnail=http://i1.ytimg.com/vi/lmWeD-vZAMY/hqdefault.jpg?r=8767]
	http://www.youtube.com/watch?v=lmWeD-vZAMY
[/embed]


### Attribute and element scope

HTML with unprocessed shortcodes in it is still valid HTML. As a result, shortcodes can be in two places in HTML:

 - In an attribute value, like so: `<a title="[title]">link</a>`
 - In an element's text, like so: `<p>Some text [shortcode] more text</p>`

The first is called "element scope" use, the second "attribute scope"

You may not use shortcodes in any other location. Specifically, you can not use shortcodes to generate attributes or 
change the name of a tag. These usages are forbidden:

	<[paragraph]>Some test</[paragraph]>

	<a [titleattribute]>link</a>

You may need to escape text inside attributes `>` becomes `&gt;`, You can include HTML tags inside a shortcode tag, but 
you need to be careful of nesting to ensure you don't break the output.
    
	:::ss
	<!-- Good -->
	<div>
		[shortcode]
			<p>Caption</p>
		[/shortcode]
	</div>

	<!-- Bad: -->

	<div>
		[shortcode]
	</div>
	<p>
		[/shortcode]
	</p>

### Location

Element scoped shortcodes have a special ability to move the location they are inserted at to comply with HTML lexical 
rules. Take for example this basic paragraph tag:

	<p><a href="#">Head [figure,src="assets/a.jpg",caption="caption"] Tail</a></p>
	
When converted naively would become:

	<p><a href="#">Head <figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure> Tail</a></p>

However this is not valid HTML - P elements can not contain other block level elements.

To fix this you can specify a "location" attribute on a shortcode. When the location attribute is "left" or "right"
the inserted content will be moved to immediately before the block tag. The result is this:

	<figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#">Head  Tail</a></p>

When the location attribute is "leftAlone" or "center" then the DOM is split around the element. The result is this:

	<p><a href="#">Head </a></p><figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#"> Tail</a></p>

### Parameter values

Here is a summary of the callback parameter values based on some example shortcodes.
	
	:::php
	public function MyCustomShortCode($arguments, $content = null, $parser = null, $tagName) {
		// ..
	}

	[my_shortcode]
	$attributes 	=> array();
	$content 		=> null;
	$parser         => ShortcodeParser instance,
	$tagName 		=> 'myshortcode')

	[my_shortcode,attribute="foo",other="bar"]

	$attributes      => array ('attribute'  => 'foo', 'other'      => 'bar')
	$enclosedContent => null
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

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

## Related Documentation

 * [Wordpress Implementation](http://codex.wordpress.org/Shortcode_API)
 * [How to Create a Google Maps Shortcode](how_tos/create_a_google_maps_shortcode)

## API Documentation

 * [api:ShortcodeParser]
