# Templates

## Introduction

SilverStripe templates consist of HTML code augmented with special control codes, described below.  Because of this, you
can have as much control of your site's HTML code as you like.

Because the SilverStripe templating language is a string processing language it can therefore be used to make other
text-based data formats, such as XML or RTF.

Here is a very simple template:

	:::ss
	<html>
		<head>
			<% base_tag %>
			<title>$Title</title>
			$MetaTags
		</head>
		<body>
		<div id="Container">
			<div id="Header">
				<h1>Bob's Chicken Shack</h1>
			</div>
			<div id="Navigation">
				<% if Menu(1) %>
				<ul>
					<% control Menu(1) %>	  
					<li><a href="$Link" title="Go to the $Title page" class="$LinkingMode">$MenuTitle</a></li>
					<% end_control %>
				</ul>
				<% end_if %>
			</div>
			<div class="typography">
				$Layout
			</div>
			<div id="Footer">
				<p>Copyright $Now.Year</p>
			</div>
		</div>
		</body>
	</html>
	<%-- comment --%>

## Default Template Syntax

These tags appear on the default template and should be included in every theme:

### Base Tag

The `<% base_tag %>` placeholder is replaced with the HTML base element. Relative links within a document (such as `<img
src="someimage.jpg" />`) will become relative to the URI specified in the base tag. This ensures the browser knows where
to locate your site’s images and css files. So it is a must for templates!

It renders in the template as `<base href="http://www.mydomain.com" />`

### Meta Tags

The `$MetaTags` placeholder in a template returns a segment of HTML appropriate for putting into the `<head>` tag. It
will set up title, keywords and description meta-tags, based on the CMS content and is editable in the 'Meta-data' tab
on a per-page basis. If you don’t want to include the title-tag `<title>` (for custom templating), use
`$MetaTags(false)`.

By default `$MetaTags` renders:

	:::ss
	<title>Title of the Page</title>
	<meta name="generator" http-equiv="generator" content="SilverStripe 2.0" >
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" >

TODO Explain SiteTree properties and SiteTree->MetaTags() overloading

### Including CSS and JavaScript files

See [CSS](/topics/css) and [Javascript](/topics/javascript) topics for individual including of files and
[requirements](reference/requirements) for good examples of including both Javascript and CSS files.

### Layout Tag

In every SilverStripe theme there is a default `Page.ss` file in the `/templates` folder. `$Layout` appears in this file
and is a core variable which includes a Layout template inside the `/templates/Layout` folder once the page is rendered. 
By default the `/templates/Layout/Page.ss` file is included in the html template.

### .typography style

By default, SilverStripe includes the `theme/css/typography.css` file into the Content area. So you should always include the
typography style around the main body of the site so both styles appear in the CMS and on the template. Where the main body of
the site is can vary, but usually it is included in the /Layout files. These files are included into the main Page.ss template
by using the `$Layout` variable so it makes sense to add the .typography style around $Layout.

	:::ss
	<div class="typography">
		$Layout
	</div>

## Designing reusable templates

Although SilverStripe is ultimately flexible in how you create your templates, there's a couple of best practices. These
will help you to design templates for modules, and make it easier for other site developers to integrate them into their
own base templates.

* Most of your templates should be Layout templates
* Build your templates as a [Theme](/topics/themes) so you can easily re-use and exchange them
* Your layout template should include a standard markup structure (`<div id="Layout">$Layout</div>`)
* Layout templates only include content that could be completely replaced by another module (e.g. a forum thread). It
might be infeasible to do this 100%, but remember that every piece of navigation that needs to appear inside `$Layout`
will mean that you have to customise templates when integrating the module.
*  Any CSS applied to layout templates should be flexible width. This means the surrounding root template can set its
width independently.
*  Don't include any navigation elements in your Layout templates, they should be contained in the root template.
*  Break down your templates into groups of includes.  Site integrators would then have the power to override individual
includes, rather than entire templates.

For more information about templates go to the [Advanced Templates](/reference/advanced-templates) page.


## Related
* [Advanced Templates](/reference/advanced-templates)
* [Typography](/reference/typography)
* [themes](/topics/themes)
* [widgets](/topics/widgets)
* [images](/reference/image)
* [Tutorial 1: Building a basic site](/tutorials/1-building-a-basic-site)
* [Tutorial 2: Extending a basic site](/tutorials/2-extending-a-basic-site)