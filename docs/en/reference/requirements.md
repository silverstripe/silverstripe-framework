# Requirements

## Introduction

The requirements class takes care of including CSS and JavaScript into your applications. This is preferred to
hardcoding any references in the `<head>`-tag of your template, as it enables a more flexible handling.


## Including inside PHP Code
It is common practice to include most Requirements either in the *init()*-method of your [controller](/topics/controller), or
as close to rendering as possible (e.g. in `[api:FormField]`

	:::php
	Requirements::javascript("cms/javascript/LeftAndMain.js");
	Requirements::css("cms/css/TreeSelector.css");


If you're using the CSS method a second argument can be used. This argument defines the 'media' attribute of the `<link>`
element, so you can define 'screen' or 'print' for example.

	Requirements::css("cms/css/TreeSelector.css", "screen,projection");

## Including inside Template files

If you do not want to touch the PHP (for example you are constructing a generic theme) then you can include a file via
the templates

	<% require css(cms/css/TreeSelector.css) %>
	<% require themedCSS(TreeSelector) %>
	<% require javascript(cms/javascript/LeftAndMain.js) %>

## Combining Files

You can concatenate several CSS or javascript files into a single dynamically generated file. This increases performance
reducing HTTP requests. Note that for debugging purposes combined files is disabled in devmode.

	:::php
	// supports CSS + JS
	Requirements::combine_files(
		'foobar.js',
		array(
			'mysite/javascript/foo.js',
			'mysite/javascript/bar.js',
		)
	);


By default it stores the generated file in the assets/ folder but you can configure this by setting
 

	:::php
	// relative from the base folder
	Requirements::set_combined_files_folder('folder');


If SilverStripe doesn't have permissions on your server to write these files it will default back to including them
individually .

## Custom Inline Scripts

You can also quote custom script directly.  This may seem a bit ugly, but is useful when you need to transfer some kind
of 'configuration' from the database to the javascript/css.  You'll need to use the "heredoc" syntax to quote JS and
CSS, this is generally speaking the best way to do these things - it clearly marks the copy as belonging to a different
language.

	:::php
	Requirements::customScript(<<<JS
	  alert("hi there"); 
	JS
	);
	Requirements::customCSS(<<<CSS
	  .tree li.$className {
	    background-image: url($icon);
	  }
	CSS
	);


## Templated javascript

A variant on the inclusion of custom javascript is the inclusion of *templated* javascript.  Here, you keep your
JavaScript in a separate file and instead load, via search and replace, several PHP-generated variables into that code.

	:::php
	$vars = array(
	    "EditorCSS" => "mot/css/editor.css",
	)
	Requirements::javascriptTemplate("cms/javascript/editor.template.js", $vars);


## Clearing

You may want to clear all of the requirements mentioned thus far.  I've used this when you've put an iframe generator as
an action on the controller that uses it.  The iframe has a completely different set of scripting and styling
requirements, and it's easiest to flush all the default stuff and start again.

	:::php
	Requirements::clear();


You can also clear specific Requirements:

	:::php
	Requirements::clear('jsparty/prototype.js');

Caution: Depending on where you call this command, a Requirement might be *re-included* afterwards.



## Inclusion Order

Requirements acts like a stack, where everything is rendered sequentially in the order it was included. There is no way
to change inclusion-order, other than using *Requirements::clear* and rebuilding (=guessing) the whole set of
requirements. Caution: Inclusion order is both relevant for CSS and Javascript files in terms of dependencies,
inheritance and overlays - please be careful when messing with the order of Requirements.

NOTE:
By default, SilverStripe includes all Javascript files at the bottom of the page body. If this causes problems for you,
for example if you're using animation that ends up showing everything until the bottom of the page loads, or shows
buttons before pushing them will actually work, you can change this behaviour:

In your controller's init() function, add:

	:::php
	Requirements::set_write_js_to_body(false);


## CMS Requirements

The SilverStripe core includes a lot of Requirements by itself. Most of these are collated in `[api:LeftAndMain]` first.

## Motivation

Every page requested is made up of a number of parts, and many of those parts require their own CSS or JavaScript.  
Rather than force the developer to put all of those requests into the template, or the header function, you can
reference required files anywhere in your application.

This lets you create very modular units of PHP+JavaScript+CSS, which a powerful concept but must be managed carefully.  

## Managing Generic CSS styling

One of the aims of this is to create units of functionality that can be reasonably easily deployed as-is, while still
giving developers the option to customise them.  The logical solution to this is to create 'generic' CSS to be applied
to these things.  However, we must take great care to keep the CSS selectors very nonspecific.  This precludes us from
adding any CSS that would "override customisations" in the form - for example, resetting the width of a field where 100%
width isn't appropriate.

Another solution would be to include some "generic CSS" for form elements at the very high level, so that fixed widths
on forms were applied to the generic form, and could therefore be overridden by a field's generic stylesheet.  Similar
to this is mandating the use of "form div.field input" to style form input tags, whether it's a generic form or a custom
one.

Perhaps we could make use of a Requirements::disallowCSS() function, with which we could prevent the standard CSS from
being included in situations where it caused problems.  But the complexity could potentially balloon, and really, it's a
bit of an admission of defeat - we shouldn't need to have to do this if our generic CSS was well-designed.


## Ideas/Problems

### Ajax

The whole "include it when you need it" thing shows some weaknesses in areas such as the CMS, where Ajax is used to load
in large pieces of the application, which potentially require more CSS and JavaScript to be included.  At this stage,
the only workaround is to ensure that everything you might need is included on the first page-load.

One idea is to mention the CSS and JavaScript which should be included in the header of the Ajax response, so that the
client can load up those scripts and stylesheets upon completion of the Ajax request.  This could be coded quite
cleanly, but for best results we'd want to extend prototype.js with our own changes to their Ajax system, so that every
script had consistent support for this.

### Lots of files

Because everything's quite modular, it's easy to end up with a large number of small CSS and JavaScript files.  This has
problems with download time, and potentially maintainability.

We don't have any easy answers here, but here are some ideas:

*  Merging the required files into a single download on the server.  The flip side of this is that if every page has a
slightly different JS/CSS requirements, the whole lot will be refetched.
*  Better: "Tagging" each required file for different use-cases, and creating a small set of common functionalities
(e.g. everything tagged "base" such as prototype.js would always be included)
*  Do lazy fetching of scripts within an ajax-call. This seems to be possible, but very tricky due to the asynchronous
nature of an ajax-request. Needs some more research

## API Documentation
`[api:Requirements]`