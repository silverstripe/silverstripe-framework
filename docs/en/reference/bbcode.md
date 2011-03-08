# BBcode support

A bbcode tags help box shows when the "BBCode help" link is clicked. Javascript is required for this to work. 
It has been encorporated as a modified version of PEAR's [HTML_BBCodeParser](http://pear.php.net/package/HTML_BBCodeParser)
BBCode is used by default in the [blog](http://silverstripe.org/blog-module) and 
[forum](http://silverstripe.org/forum-module) modules.

## Usage

To add bbcode parsing to a template, instead of $Content use:

	:::ss
	$Content.Parse(BBCodeParser)


BBCode can be enabled in comments by adding the following to _config.php

	:::php
	PageComment::enableBBCode();


## Supported Tags

- [b]Bold[/b]
- [i]Italics[/i]
- [u]Underlined[/u]
- [s]Struck-out[/s]
- [color=blue]blue text[/color]
- [align=right]right aligned[/align]
- [code]Code block[/code]
- [email]you@yoursite.com[/email]
- [email=you@yoursite.com]Email[/email]
- [ulist][*]unordered item 1[/ulist]
- [img]http://www.website.com/image.jpg[/img]
- [url]http://www.website.com/[/url]
- [url=http://www.website.com/]Website[/url]