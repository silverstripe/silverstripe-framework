# Shortcodes

The Shortcode API (new in 2.4) is a simple regex based parser that allows you to replace simple bbcode-like tags within
a HTMLText or HTMLVarchar field when rendered into a template. It is inspired by and very similar to the [Wordpress
implementation](http://codex.wordpress.org/Shortcode_API) of shortcodes.

Here are all variants of the acceptable shortcode tags:

	[shortcode]
	[shortcode/]
	[shortcode,parameter="value"]
	[shortcode,parameter="value"]Enclosed Content[/shortcode]

Note the usage of `,` to delimit the parameters.

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

The parser will recognise this as:

	[shortcode]
	[shortcode]
	[/shortcode]
