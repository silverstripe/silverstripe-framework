title: Requirements
summary: How to include and require other assets in your templates such as javascript and CSS files.

# Requirements

The requirements class takes care of including CSS and JavaScript into your applications. This is preferred to hard 
coding any references in the `<head>` tag of your template, as it enables a more flexible handling through the 
[api:Requirements] class.

## Template Requirements API

**mysite/templates/Page.ss**

	:::ss
	<% require css("cms/css/TreeSelector.css") %>
	<% require themedCSS("TreeSelector") %>
	<% require javascript("cms/javascript/LeftAndMain.js") %>

<div class="alert" markdown="1">
Requiring assets from the template is restricted compared to the PHP API.
</div>

## PHP Requirements API

It is common practice to include most Requirements either in the *init()*-method of your [controller](../controller), or
as close to rendering as possible (e.g. in `[api:FormField]`.

	:::php
	<?php

	class MyCustomController extends Controller {

		public function init() {
			parent::init();
		
			Requirements::javascript("cms/javascript/LeftAndMain.js");
			Requirements::css("cms/css/TreeSelector.css");
		}
	}

### CSS Files

	:::php
	Requirements::css($path, $media);

If you're using the CSS method a second argument can be used. This argument defines the 'media' attribute of the 
`<link>` element, so you can define 'screen' or 'print' for example.

	:::php
	Requirements::css("cms/css/TreeSelector.css", "screen,projection");

### Javascript Files

	:::php
	Requirements::javascript($path);

A variant on the inclusion of custom javascript is the inclusion of *templated* javascript.  Here, you keep your
JavaScript in a separate file and instead load, via search and replace, several PHP-generated variables into that code.

	:::php
	$vars = array(
	    "EditorCSS" => "cms/css/editor.css",
	);

	Requirements::javascriptTemplate("cms/javascript/editor.template.js", $vars);

In this example, `editor.template.js` is expected to contain a replaceable variable expressed as `$EditorCSS`.

### Custom Inline CSS or Javascript

You can also quote custom script directly. This may seem a bit ugly, but is useful when you need to transfer some kind
of 'configuration' from the database in a raw format.  You'll need to use the `heredoc` syntax to quote JS and CSS, 
this is generally speaking the best way to do these things - it clearly marks the copy as belonging to a different
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

## Combining Files

You can concatenate several CSS or javascript files into a single dynamically generated file. This increases performance
by reducing HTTP requests.

	:::php
	Requirements::combine_files(
		'foobar.js',
		array(
			'mysite/javascript/foo.js',
			'mysite/javascript/bar.js',
		)
	);

<div class="alert" markdown='1'>
To make debugging easier in your local environment, combined files is disabled when running your application in `dev`
mode.
</div>

By default it stores the generated file in the assets/ folder, but you can configure this by pointing the 
`Requirements.combined_files_folder` configuration setting to a specific folder.

**mysite/_config/app.yml**
	
	:::yml
	Requirements:
	  combined_files_folder: '_combined'

<div class="info" markdown='1'>
If SilverStripe doesn't have permissions on your server to write these files it will default back to including them
individually. SilverStripe **will not** rewrite your paths within the file.
</div>

You can also combine CSS files into a media-specific stylesheets as you would with the `Requirements::css` call - use
the third paramter of the `combine_files` function:

	:::php
	$printStylesheets = array(
		"$themeDir/css/print_HomePage.css",
		"$themeDir/css/print_Page.css",
	);

	Requirements::combine_files('print.css', $printStylesheets, 'print');

## Clearing assets

	:::php
	Requirements::clear();

Clears all defined requirements. You can also clear specific requirements.

	:::php
	Requirements::clear(THIRDPARTY_DIR.'/prototype.js');

<div class="alert" markdown="1">
Depending on where you call this command, a Requirement might be *re-included* afterwards.
</div>

## Blocking

Requirements can also be explicitly blocked from inclusion, which is useful to avoid conflicting JavaScript logic or 
CSS rules. These blocking rules are independent of where the `block()` call is made. It applies both for already 
included requirements, and ones included after the `block()` call.

One common example is to block the core `jquery.js` added by various form fields and core controllers, and use a newer 
version in a custom location. This assumes you have tested your application with the newer version.

	:::php
	Requirements::block(THIRDPARTY_DIR . '/jquery/jquery.js');

<div class="alert" markdown="1">
The CMS also uses the `Requirements` system, and its operation can be affected by `block()` calls. Avoid this by 
limiting the scope of your blocking operations, e.g. in `init()` of your controller.
</div>

## Inclusion Order

Requirements acts like a stack, where everything is rendered sequentially in the order it was included. There is no way
to change inclusion-order, other than using *Requirements::clear* and rebuilding the whole set of requirements. 

<div class="alert" markdown="1">
Inclusion order is both relevant for CSS and Javascript files in terms of dependencies, inheritance and overlays - be 
careful when messing with the order of requirements.
</div>

## Javascript placement

By default, SilverStripe includes all Javascript files at the bottom of the page body, unless there's another script 
already loaded, then, it's inserted before the first `<script>` tag. If this causes problems, it can be configured.

**mysite/_config/app.yml**

	:::yml
	Requirements:
	  write_js_to_body: true
	  force_js_to_bottom: true

`Requirements.force_js_to_bottom`, will force SilverStripe to write the Javascript to the bottom of the page body, even 
if there is an earlier script tag.


## API Documentation

* [api:Requirements]