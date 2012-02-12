# SilverStripe Markdown Syntax

As Markdown by default is quite limited and not well suited for technical documentation,
the SilverStripe project relies on certain syntax additions. As a base syntax, we use
the [Markdown Extra](http://michelf.com/projects/php-markdown/extra/) format, which provides us
with support for tables, definition lists, code blocks and inline HTML. 

**Please read the [Markdown](http://daringfireball.net/projects/markdown/syntax) and 
[Markdown Extra](http://michelf.com/projects/php-markdown/extra/) documentation for a syntax overview**

On top of that, we have added syntax that is only resolved by our custom parser.
The goal is to keep the customization to a necessary minimum, 
and HTML output should still be readable with our custom markup unparsed.

## Rendering

While most of the Markdown syntax is parseable by all common implementations,
the special syntax is relying on a custom SilverStripe project that powers `http://doc.silverstripe.org`.

The website a standard SilverStripe installation with the [sapphiredocs](https://github.com/silverstripe/silverstripe-sapphiredocs/)
module installed (see module [README](https://github.com/silverstripe/silverstripe-sapphiredocs/blob/master/README.md) and
[documentation](https://github.com/silverstripe/silverstripe-sapphiredocs/tree/master/docs/en)).

## Syntax

### Relative Links

Relative links can point to other markdown pages in the same module.
They are always referred to **without** the `.md` file extension.
"Absolute" links relate to the root of a certain module,
not the webroot of the renderer project or the filesystem root.

* link to folder on same level: `[title](sibling/)`
* link to page on same level: `[title](sibling)`
* link to parent folder: `[title](../parent/)`
* link to page in parent folder: `[title](../parent/page)`
* link to root folder: `[title](/)`
* link to root page: `[title](/rootpage)`

Don't forget the trailing slash for directory links,
it is important to distinguish files from directories.

<div class="notice" markdown='1'>
It is recommended to use absolute links over relative links
to make files easier to move around without changing all links.
</div>

### API Links

You can link to API documentation from within the markup by pseudo-links.
These are automatically resolved to the right URL on `http://api.silverstripe.org`.
API links are automatically wrapped in `<code>` blocks by the formatter.

 * Link to class: `[api:DataObject]`
 * Link to static method: `[api:DataObject::has_one()]`
 * Link to instance method: `[api:DataObject->write()]`
 * Link to static property: `[api:DataObject::$searchable_fields]`
 * Link to instance property: `[api:DataObject->changedFields]`
 * Custom titles: `[my title](api:DataObject)`

There's some gotchas:

 * This notation can't be used in code blocks.
 * If you want to use API links to other modules or versions of the same module, you'll have to use the full `http://` URL.
 * You can't mark API links in backticks to trigger `<pre>` formatting, as it will stop the link parsing.
	 The backticks are automatically added by the parser.

### Code Blocks with Highlighting

Code blocks can optionally contain language hints that a syntax highlighter can
pick up. Use the first line in the block to add a language identifier, prefixed by three colons (`:::`), for example `:::php`.
We're currently using the [syntaxhighlighter](http://code.google.com/p/syntaxhighlighter/) JavaScript implementation.
See a [list of supported languages](http://code.google.com/p/syntaxhighlighter/wiki/Languages).

Example for PHP: 

	:::php
	class Page extends SiteTree {
		public function myFunction() {
			// ...
		}
	}

For SilverStripe templates, please use `:::ss` as a brush.

### Images

As a convention, referenced images in a Markdown formatted page should always be stored
in an `_images/` folder on the same level as the page itself. Try to keep the image size
small, as we typically package the documentation with the source code download, and
need to keep the file size small. 

You can link to absolute image URLs as well, of course.

## FAQs

### How do I preview my own SS Markdown?

Thats only possible with the `sapphiredocs` module - we don't have a standalone parser.

### Can I run my own documentation server?

Yes, the `sapphiredocs` module just requires a default SilverStripe installation (2.4+).

### Can I generate SS Markdown other formats?

Currently this is not supported, as all HTML is generated on the fly.

### Can I contribute to the parser and rendering project?

Of course, the `sapphiredocs` code is BSD licensed - we're looking forward to your contributions!

## Related ##

 * [contributing](contributing#writing-documentation): The doc.silverstripe.org website has certain styling and writing conventions