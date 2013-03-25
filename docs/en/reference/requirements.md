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

## API Documentation
`[api:Requirements]`