---
title: Common Variables
summary: Some of the common variables and methods your templates can use, including Menu, SiteConfig, and more.
---
# Common Variables

The page below describes a few of common variables and methods you'll see in a SilverStripe template. This is not an 
exhaustive list. From your template you can call any method, database field, or relation on the object which is 
currently in scope as well as its' subclasses or extensions. 

Knowing what methods you can call can be tricky, but the first step is to understand the scope you're in. Scope is 
explained in more detail on the [syntax](syntax#scope) page. Many of the methods listed below can be called from any 
scope, and you can specify additional static methods to be available globally in templates by implementing the 
[api:TemplateGlobalProvider] interface.

[notice]
Want a quick way of knowing what scope you're in? Try putting `$ClassName` in your template. You should see a string 
such as `Page` of the object that's in scope. The methods you can call on that object then are any functions, database 
properties or relations on the `Page` class, `Page_Controller` class as well as anything from their subclasses **or** 
extensions.
[/notice]

Outputting these variables is only the start, if you want to format or manipulate them before adding them to the template
have a read of the [Formating, Modifying and Casting Variables](casting) documentation.

[alert]
Some of the following only apply when you have the `CMS` module installed. If you're using the `Framework` alone, this
functionality may not be included.
[/alert]


## Base Tag

```ss
	<head>
		<% base_tag %>

		..
	</head>

```
src="someimage.jpg" />) will become relative to the URI specified in the base tag. This ensures the browser knows where
to locate your site’s images and css files.

It renders in the template as `<base href="http://www.yoursite.com" /><!--[if lte IE 6]></base><![endif]-->`

[alert]
A `<% base_tag %>` is nearly always required or assumed by SilverStripe to exist.
[/alert]

## CurrentMember

Returns the currently logged in [api:Member] instance, if there is one logged in. 

```ss
	<% if $CurrentMember %>
	  Welcome Back, $CurrentMember.FirstName
	<% end_if %>

```
## Title and Menu Title

```ss
	$Title
	$MenuTitle

```
`getTitle()` method).

The CMS module in particular provides two fields to label a page: `Title` and `MenuTitle`. `Title` is the title 
displayed on the web page, while `MenuTitle` can be a shorter version suitable for size-constrained menus.

[notice]
If `MenuTitle` is left blank by the CMS author, it'll just default to the value in `Title`.
[/notice]

## Page Content

```ss
	$Content

```
but it is also the standard for any object that has a body of content to output.

[info]
Please note that this database content can be `versioned`, meaning that draft content edited in the CMS can be different 
from published content shown to your website visitors. In templates, you don't need to worry about this distinction.

The `$Content` variable contains the published content by default,and only preview draft content if explicitly 
requested (e.g. by the "preview" feature in the CMS) (see the [versioning documentation](/../model/versioning) for 
more details).
[/info]

### SiteConfig: Global settings

[notice]
`SiteConfig` is a module that is bundled with the `CMS`. If you wish to include `SiteConfig` in your framework only 
web pages. You'll need to install it via `composer`.
[/notice]

```ss
	$SiteConfig.Title

```
than PHP code. By default, this includes a Website title and a Tagline.

`SiteConfig` can be extended to hold other data, for example a logo image which can be uploaded through the CMS or 
global content such as your footer content.


## Meta Tags

The `$MetaTags` placeholder in a template returns a segment of HTML appropriate for putting into the `<head>` tag. It
will set up title, keywords and description meta-tags, based on the CMS content and is editable in the 'Meta-data' tab
on a per-page basis. 

[notice]
If you don’t want to include the title tag use `$MetaTags(false)`.
[/notice]

By default `$MetaTags` renders:

```ss
	<title>Title of the Page</title>
	<meta name="generator" http-equiv="generator" content="SilverStripe 3.0" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

```
	
```ss
	<meta name="generator" http-equiv="generator" content="SilverStripe 3.0" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

```

```ss
	$MetaTags(false)
	<title>$Title - Bob's Fantasy Football</title>

```

```ss
	<a href="$Link">..</a>

```
returns the relative URL for the object and `AbsoluteLink` outputs your full website address along with the relative 
link.

```ss
	$Link
	<!-- returns /about-us/offices/ -->

	$AbsoluteLink
	<!-- returns http://yoursite.com/about-us/offices/ -->

```

```ss
	$isSection
	$isCurrent

```
will return true or false based on page being looped over relative to the currently viewed page. 

For instance, to only show the menu item linked if it's the current one:

```ss
	<% if $isCurrent %>
		$Title
	<% else %>
		<a href="$Link">$Title</a>
	<% end_if %>
	
```
An example for checking for `current` or `section` is as follows:

```ss
    <a class="<% if $isCurrent %>current<% else_if $isSection %>section<% end_if %>" href="$Link">$MenuTitle</a>

```

**Additional Utility Method**

 * `$InSection(page-url)`: This if block will pass if we're currently on the page-url page or one of its children.

```ss
	<% if $InSection(about-us) %>
		<p>You are viewing the about us section</p>
	<% end_if %>

```
### URLSegment

This returns the part of the URL of the page you're currently on. For example on the `/about-us/offices/` web page the 
`URLSegment` will be `offices`. `URLSegment` cannot be used to generate a link since it does not output the full path.
It can be used within templates to generate anchors or other CSS classes.

```ss
	<div id="section-$URLSegment">

	</div>

	<!-- returns <div id="section-offices"> -->

```

Returns the class of the current object in [scope](syntax#scope) such as `Page` or `HomePage`. The `$ClassName` can be 
handy for a number of uses. A common use case is to add to your `<body>` tag to influence CSS styles and JavaScript 
behavior based on the page type used:

```ss
	<body class="$ClassName">

	<!-- returns <body class="HomePage">, <body class="BlogPage"> -->

```

```ss
	<% loop $Children %>

	<% end_loop %>

```
the `CMS` or a custom list of data. This originates in the `Versioned` extension's `getChildren` method.

[alert]
For doing your website navigation most likely you'll want to use `$Menu` since its independent of the page 
context.
[/alert]

### ChildrenOf

```ss
	<% loop $ChildrenOf(<my-page-url>) %>

	<% end_loop %>

```
because it's not dependent on the context of the current page. For example, it would allow you to list all staff member 
pages underneath a "staff" holder on any page, regardless if its on the top level or elsewhere.


### AllChildren

Content authors have the ability to hide pages from menus by un-selecting the `ShowInMenus` checkbox within the CMS. 
This option will be honored by `<% loop $Children %>` and `<% loop $Menu %>` however if you want to ignore the user
preference, `AllChildren` does not filter by `ShowInMenus`.

```ss
	<% loop $AllChildren %>
		...
	<% end_loop %>

```
### Menu Loops

```ss
	<% loop $Menu(1) %>
		...
	<% end_loop %>

```

[notice]
Pages with the `ShowInMenus` property set to `false` will be filtered out.
[/notice]

## Access to a specific Page

```ss
	<% with $Page(my-page) %>
		$Title
	<% end_with %>

```

## Access to Parent and Level Pages

### Level

```ss
	<% with $Level(1) %>
		$Title
	<% end_with %>

```
looking back through its parent pages. `Level(1)` being the top most level.

For example, imagine you're on the "bob marley" page, which is three levels in: "about us > staff > bob marley".

*  `$Level(1).Title` would return "about us"
*  `$Level(2).Title` would return "staff"
*  `$Level(3).Title` would return "bob marley"

### Parent

```ss
	<!-- given we're on 'Bob Marley' in "about us > staff > bob marley" -->
	
	$Parent.Title
	<!-- returns 'staff' -->

	$Parent.Parent.Title
	<!-- returns 'about us' -->

```
## Navigating Scope

See [scope](syntax#scope).

## Breadcrumbs

Breadcrumbs are the path of pages which need to be taken to reach the current page, and can be a great navigation aid 
for website users.

While you can achieve breadcrumbs through the `$Level(<level>)` control manually, there's a nicer shortcut: The 
`$Breadcrumbs` variable.

```ss
	$Breadcrumbs

```

```ss
	<% if $Pages %>
		<% loop $Pages %>
			<% if $Last %>$Title.XML<% else %><a href="$Link">$MenuTitle.XML</a> &raquo;<% end_if %>
		<% end_loop %>
	<% end_if %>

```
To customise the markup that the `$Breadcrumbs` generates, copy `cms/templates/BreadcrumbsTemplate.ss` to 
`mysite/templates/BreadcrumbsTemplate.ss`, modify the newly copied template and flush your SilverStripe cache.
[/info]

## Forms

```ss
	$Form

```
SilverStripe log-in form. If you are on such a page, the `$Form` variable will contain the HTML content of the form. 
Placing it just below `$Content` is a good default.

You can add your own forms by implementing new form instances (see the [Forms tutorial](/tutorials/forms)).


## Related

 * [Casting and Formating Variables](casting)
 * [Template Inheritance](template_inheritance)

## API Documentation

 * [api:ContentController]: The main controller responsible for handling pages.
 * [api:Controller]: Generic controller (not specific to pages.)
 * [api:DataObject]: Underlying model class for page objects.
 * [api:ViewableData]: Underlying object class for pretty much anything displayable.
