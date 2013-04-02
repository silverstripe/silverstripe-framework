# Shortcodes

The Shortcode API is a way to replace simple bbcode-like tags within HTML. It is inspired by and very similar to 
the [Wordpress implementation](http://codex.wordpress.org/Shortcode_API) of shortcodes.

A guide to syntax

	Unclosed - [shortcode]
	Explicitly closed - [shortcode/]
	With parameters, mixed quoting - [shortcode parameter=value parameter2='value2' parameter3="value3"]
	Old style parameter separation - [shortcode,parameter=value,parameter2='value2',parameter3="value3"]
	With contained content & closing tag - [shortcode]Enclosed Content[/shortcode]
	Escaped (will output [just] [text] in response) - [[just] [[text]]

Shortcode parsing is already hooked into HTMLText and HTMLVarchar fields when rendered into a template

## Attribute and element scope

HTML with unprocessed shortcodes in it is still valid HTML. As a result, shortcodes can be in two places in HTML:

 - In an attribute value, like so:

	<a title="[title]">link</a>
	
 - In an element's text, like so:

	<p>
		Some text [shortcode] more text
	</p>

The first is called "element scope" use, the second "attribute scope"

You may not use shortcodes in any other location. Specifically, you can not use shortcodes to generate attributes or 
change the name of a tag. These usages are forbidden:

	<[paragraph]>Some test</[paragraph]>

	<a [titleattribute]>link</a>

Also note:

  - you may need to escape text inside attributes `>` becomes `&gt;` etc
  
  - you can include HTML tags inside a shortcode tag, but you need to be careful of nesting to ensure you don't
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

## Location

Element scoped shortcodes have a special ability to move the location they are inserted at to comply with
HTML lexical rules. Take for example this basic paragraph tag:

	<p><a href="#">Head [figure src="assets/a.jpg" caption="caption"] Tail</a></p>
	
When converted naively would become

	<p><a href="#">Head <figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure> Tail</a></p>

However this is not valid HTML - P elements can not contain other block level elements.

To fix this you can specify a "location" attribute on a shortcode. When the location attribute is "left" or "right"
the inserted content will be moved to immediately before the block tag. The result is this:

	<figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#">Head  Tail</a></p>

When the location attribute is "leftAlone" or "center" then the DOM is split around the element. The result is this:

	<p><a href="#">Head </a></p><figure><img src="assets/a.jpg" /><figcaption>caption</figcaption></figure><p><a href="#"> Tail</a></p>

## Defining Custom Shortcodes

All you need to do to define a shortcode is to register a callback with the parser that will be called whenever a
shortcode is encountered. This callback will return a string to replace the shortcode with.

	:::php
	public static function my_shortcode_handler($attributes, $enclosedContent, $parser, $tagName) {
		// This simple callback simply converts the shortcode to a span.
		return "<span class=\"$tagName\">$enclosedContent</span>";
	}

The parameters passed to the callback are, in order:

* Any parameters attached to the shortcode as an associative array (keys are lower-case).
* Any content enclosed within the shortcode (if it is an enclosing shortcode). Note that any content within this will
not have been parsed, and can optionally be fed back into the parser.
* The ShortcodeParser instance used to parse the content.
* The shortcode tag name that was matched within the parsed content.

For the shortcode to work, you need to register it with the `ShortcodeParser`. Assuming you've placed the
callback function in the `Page` class, you would need to make the following call from `_config.php`:

	:::php
	ShortcodeParser::get('default')->register(
		'shortcode_tag_name',
		array('Page', 'my_shortcode_handler')
	);

An example result of installing such a shortcode would be that the string `[shortcode_tag_name]Testing
testing[/shortcode_tag_name]` in the page *Content* would be replaced with the `<span class="shortcode_tag_name">Testing
testing</span>`.

### Parameter values

Here is a summary of the callback parameter values based on some example shortcodes.

#### Short

	[my_shortcodes]

	$attributes      => array()
	$enclosedContent => null
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

#### Short with attributes

	[my_shortcode,attribute="foo",other="bar"]

	$attributes      => array ('attribute'  => 'foo', 'other'      => 'bar')
	$enclosedContent => null
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

#### Long with attributes

	[my_shortcode,attribute="foo"]content[/my_shortcode]

	$attributes      => array('attribute' => 'foo')
	$enclosedContent => 'content'
	$parser          => ShortcodeParser instance
	$tagName         => 'my_shortcode'

## Inbuilt Shortcodes

All internal links inserted via the CMS into a content field are in the form `<a href="[sitetree_link,id=n]">`. At
runtime this is replaced by a plain link to the page with the ID in question.

## Limitations

Since the shortcode parser is based on a simple regular expression it cannot properly handle nested shortcodes. For
example the below code will not work as expected:

	[shortcode]
	[shortcode][/shortcode]
	[/shortcode]

The parser will raise an error if it can not find a matching opening tag for any particular closing tag
