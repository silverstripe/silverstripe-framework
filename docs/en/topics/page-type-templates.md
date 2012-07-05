# Building templates for page types

Much of your work building a SilverStripe site will involve the creation of 
templates for your own page types. SilverStripe has its own template language. 
Its basic features like variables, blocks and loops are described in our ["templates" reference guide](/reference/templates).
In this guide, we'll show you specific uses for creating page layouts. 
This assumes you are familiar with the concept of ["page types"](/topics/page-types).

To get a feel for what those templates look like, let's have a look at an abbreviated example. In your webroot, these templates are usually located in `themes/<your-theme>/templates`.
Replace the `<your-theme>` placeholder accordingly, most likely you're using a theme called "simple")
Most of the magic happens in `Page.ss` and `Layout/Page.ss`.

`themes/<your-theme>/templates/Page.ss`

	:::ss
	<html>
		<head>
			<% base_tag %>
			<title>$SiteConfig.Title | $Title</title>
			$MetaTags(false)
		</head>
		<body>
		<div id="Container">
			<header>
				<h1>Bob's Chicken Shack</h1>
			</header>
			
			<navigation>
				<% if $Menu(1) %>
				<ul>
					<% loop $Menu(1) %>	  
					<li><a href="$Link" class="$LinkingMode">$MenuTitle</a></li>
					<% end_loop %>
				</ul>
				<% end_if %>
			</navigation>
			
			<div class="typography">
				$Layout
			</div>
			
		</div>
		</body>
	</html>


`themes/<your-theme>/templates/Layout/Page.ss`

	<h2>$Title</h2>
	$Content
	$Form

### Template inheritance through $Layout

Our example shows two templates, both called `Page.ss`.
One is located in the `templates/` "root" folder, the other one in a `templates/Layout/` subfolder.
This "inner template" is used by the `$Layout` placeholder in the "root template",
and is inherited based on the underlying PHP classes (read more about template inheritance
on the ["page types" topic](/topics/page-types)).

"Layout" is a fixed naming convention,
you can't use the same pattern for other folder names.

### Page Content

	:::ss
	$Content

This variable in the `Layout` template contains the main content of the current page,
edited through the WYSIWIG editor in the CMS.
It returns the database content of the `SiteTree.Content` property.

Please note that this database content can be "staged",
meaning that draft content edited in the CMS can be different from published content
shown to your website visitors. In templates, you don't need to worry about this distinction.
The `$Content` variable contain the published content by default,
and only preview draft content if explicitly requested (e.g. by the "preview" feature in the CMS)
(see the ["versioning" topic](topics/versioning) for more details).

### Menu Loops

	:::ss
	<% loop $Menu(1) %>...<% end_loop %>

`$Menu(1)` is a built-in page control that defines the top-level menu.
You can also create a sub-menu using `$Menu(2)`, and so forth.

The `<% loop $Menu(1) %>...<% end_loop %>` block defines a repeating element.  
It will change the "scope" of your template, which means that all of the template variables you use inside it will refer to a menu item.  The template code will be repeated once per menu item, with the scope set to that menu item's page. In this case, a menu item refers to an instance
of the `Page` class, so you can access all properties defined on there, for example `$Title`.

Note that pages with the `ShowInMenus` property set to FALSE will be filtered out
(its a checkbox in the "Settings" panel of the CMS).

### Children Loops

	:::ss
	<% loop Children %>...<% end_loop %>

Will loop over all children of the current page context.
Helpful to create page-specific subnavigations.
Most likely, you'll want to use `<% loop Menu %>` for your main menus,
since its independent of the page context.

	:::ss
	<% loop ChildrenOf(<my-page-url>) %>...<% end_loop %>

Will create a list of the children of the given page,
as identified by its `URLSegment` value. This can come in handy because its not dependent
on the context of the current page. For example, it would allow you to list all staff member pages
underneath a "staff" holder on any page, regardless if its on the top level or elsewhere.

	:::ss
	<% loop allChildren %>...<% end_loop %>

This will show all children of a page even if the `ShowInMenus` property is set to FALSE.

### Access to Parent and Level Pages
	
	:::ss
	<% with $Level(1) %>
		$Title
	<% end_with %>

Will return a page in the current path, at the level specified by the numbers.  
It is based on the current page context, looking back through its parent pages.

For example, imagine you're on the "bob marley" page,
which is three levels in: "about us > staff > bob marley".

*  `$Level(1).Title` would return "about us"
*  `$Level(2).Title` would return "staff"
*  `$Level(3).Title` would return "bob marley"

To simply retrieve the parent page of the current context (if existing), use the `$Parent` variable.

### Access to a specific Page

	:::ss
	<% loop Page(my-page) %>...<% end_loop %>`

"Page" will return a single page from the site tree, looking it up by URL.  You can use it in the `<% loop %>` format.
Can't be called using `$Page(my-page).Title`.

### Title and Menu Title

The CMS provides two fields to label a page: "Title" and "Menu Title".
"Title" is the title in its full length, while "Menu Title" can be
a shorter version suitable for size-constrained menus.
If "Menu Title" is left blank by the CMS author, it'll just default to "Title".

### Links and Linking Modes

	:::ss
	$LinkingMode

Each menu item we loop over knows its location on the website, so can generate a link to it.
This happens through the `[api:SiteTree->Link()]` method behind the scenes.
We're not using the direct database property `SiteTree.URLSegment` here
because pages can be nested, so the link needs to be generated on the fly.
In the template syntax, there's no distinction between a method and a property though.
The link is relative by default (see `<% base_tag %>`),
you can get an absolute one including the domain through [$AbsoluteLink](api:SiteTree->AbsoluteLink())`.

In addition, each menu item gets some context information relative
to the page you're currently viewing, contained in the `$LinkingMode` placeholder.
By setting a HTML class to this value, you can distinguish the styling of 
the currently selected menu item. It can have the following values:

 * `link`: You are neither on this page nor in this section.
 * `current`: You are currently on this page.
 * `section`: The current page is a child of this menu item, so the current "section"

More common uses:

 * `$LinkOrCurrent`: Determines if the item is the current page. Returns "link" or "current" strings.
 * `$LinkOrSection`: Determines if the item is in the current section, so in the path towards the current page. Useful for menus which you only want to show a second level menu when you are on that page or a child of it. Returns "link" or "section" strings.
 * `InSection(page-url)`: This if block will pass if we're currently on the page-url page or one of its children.
 
Example: Only show the menu item linked if its the current one:

	:::ss
	<% if LinkOrCurrent = current %>
		$Title
	<% else %>
		<a href="$Link">$Title</a>
	<% end_if %>

### Breadcrumbs

Breadcrumbs are the path of parent pages which needs to be taken
to reach the current page, and can be a great navigation aid for website users.

While you can achieve breadcrumbs through the `<% Level(<level>) %>` control already,
there's a nicer shortcut: The `$Breadcrumbs` control.

It uses its own template defined in `BreadcrumbsTemplate.ss`.
Simply place a file with the same name in your `themes/<your-theme>/templates`
folder to customize its output. Here's the default template:

	:::ss
	<% if Pages %>
		<% loop Pages %>
			<% if Last %>$Title.XML<% else %><a href="$Link">$MenuTitle.XML</a> &raquo;<% end_if %>
		<% end_loop %>
	<% end_if %>

For more customization options like limiting the amount of breadcrumbs,
take a look at `[api:SiteTree->Breadcrumbs()]`.

### SiteConfig: Global settings

	:::ss
	$SiteConfig.Title

The ["SiteConfig"](/reference/siteconfig) object allows content authors
to modify global data in the CMS, rather than PHP code.
By default, this includes a website title and tagline
(as opposed to the title of a specific page).
It can be extended to hold other data, for example a logo image
which can be uploaded through the CMS.
The object is available to all page templates through the `$SiteConfig` placeholder.

### Meta Tags

The `$MetaTags` placeholder in a template returns a segment of HTML appropriate for putting into the `<head>` tag. It
will set up title, keywords and description meta-tags, based on the CMS content and is editable in the 'Meta-data' tab
on a per-page basis. If you don’t want to include the title-tag `<title>` (for custom templating), use
`$MetaTags(false)`.

By default `$MetaTags` renders:

	:::ss
	<title>Title of the Page</title>
	<meta name="generator" http-equiv="generator" content="SilverStripe 3.0" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />

#### URLSegment

This returns the part of the URL of the page you're currently on. 
Shouldn't be used for linking to a page, since the link
is a composite value based on all parent pages as well (through the `$Link` variable).

####  ClassName

Returns the class of the underlying `Page` record. 
This can be handy to add to your `<body>` tag to influence
CSS styles and JavaScript behaviour based on the page type used:

	:::ss
	<body class="$ClassName">

In case you want to include parent PHP classes in this list as well,
use the `$CSSClasses` placeholder instead.

#### BaseHref

Returns the base URL for the current site. 
This is used to populate the `<base>` tag by default.
Can be handy to prefix custom links (not generated through `SiteTree->Link()`),
to ensure they work correctly when the webroot is hosted in a subfolder
rather than its own domain (a common development setup).

### Forms

	:::ss
	$Form

Very often, a page will contain some content and a form of some kind.  For example, the log-in page has a log-in form.  If you are on such a page, the `$Form` variable will contain the HTML content of the form.  Placing it just below `$Content` is a good default. Behind the scenes,
it maps to the `Page_Controller->Form()` method. You can add more forms by implementing
new methods there (see ["forms" topic](/topics/forms) for details).

### More Advanced Controls

Template variables and controls are just PHP properties and methods
on the underlying controllers and model classes.
We've just shown you the most common once, in practice
you can use any public API on those classes, and [extend](/reference/dataextension) them
with your own. To get an overview on what's available to you,
we recommend that you dive into the API docs for the following classes:

 * `[api:ContentController]`: The main controller responsible for handling pages
 * `[api:Controller]`: Generic controller (not specific to pages)
 * `[api:DataObject]`: Underlying model class for page objects
 * `[api:ViewableData]`: Underlying object class for pretty much anything displayable