# Building templates for page types

Much of your work building a SilverStripe site will involve the creation of templates for your [page types](/topics/page-types).  SilverStripe has its own template language, which is described in full [here](/reference/templates).

SilverStripe templates consist of HTML code augmented with special control codes, described below.  Because of this, you can have as much control of your site's HTML code as you like.

Take a look at mysite/templates/Page.ss. It contains standard HTML markup, with some extra tags.  You can see that this file only generates some of the content – it sets up the  `<html>` tags, deals with the `<head>` section, creates the first-level navigation, and then closes it all off again. See `$Layout`? That’s what is doing most of the work when you visit a page.

Now take a look at `mysite/templates/Layout/Page.ss`.  This as you can see has a lot more markup in it – it’s what is included into `$Layout` when the ‘Page’ page type is rendered.  Similarly, `mysite/templates/Layout/HomePage.ss` would be rendered into `$Layout` when the ‘HomePage’ page type is selected for the current page you’re viewing.

Here is a very simple pair of templates.  We shall explain their contents item by item.

`templates/Page.ss`

	:::ss
	<html>
		<%-- This is my first template --%>
		<head>
			<% base_tag %>
			<title>$Title</title>
			$MetaTags
		</head>
		<body>
		<div id="Container">
			<div id="Header">
				<h1>Bob's Chicken Shack</h1>
				<% with $CurrentMember %>
				<p>You are logged in as $FirstName $Surname.</p>
				<% end_with %>
			</div>
			<div id="Navigation">
				<% if $Menu(1) %>
				<ul>
					<% loop $Menu(1) %>	  
					<li><a href="$Link" title="Go to the $Title page" class="$LinkingMode">$MenuTitle</a></li>
					<% end_loop %>
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


`templates/Layout/Page.ss`

	<h1>$Title</h1>
	$Content
	$Form

## <%-- This is my first template --%>

This is a comment.  Like HTML comments, these tags let you include explanatory information in your comments.  Unlike HTML comments, these tags won't be included in the HTML file downloaded by your visitors.

## <% base_tag %>

This tag must always appear in the `<head>` of your templates.  SilverStripe uses a combination of a site-wide base tag and relative links to ensure that a site can function when loaded into a subdirectory on your webserver, as this is handy when developing a site.  For more information see the [templates reference](/reference/templates#base-tag)

### $MetaTags

The `$MetaTags` placeholder in a template returns a segment of HTML appropriate for putting into the `<head>` tag. It
will set up title, keywords and description meta-tags, based on the CMS content and is editable in the 'Meta-data' tab
on a per-page basis. If you don’t want to include the title-tag `<title>` (for custom templating), use
`$MetaTags(false)`.

By default `$MetaTags` renders:

	:::ss
	<title>Title of the Page</title>
	<meta name="generator" http-equiv="generator" content="SilverStripe 2.0" >
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" >

### <% with $CurrentMember %>...<% end_with %>

This is a "with" block.  A with block will change the "scope" of the template, so that all template variables inside that block will contain values from the $CurrentMember object, rather than from the page being rendered.

`$CurrentMember` is an object with information about the currently logged in member.  If no-one is logged in, then it's blank.  In that case, the entire `<% with $CurrentMember %>` block will be omitted.

### $FirstName, $Surname

These two variables come from the `$CurrentMember` object, because they are inside the `<% with $CurrentMember %>` block.  In particular, they will contain the first name and surname of the currently logged in member.

### <% if $Menu(1) %>...<% end_if %>

This template code defines a conditional block.  Its contents will only be shown if `$Menu(1)` contains anything.

`$Menu(1)` is a built-in page control that defines the top-level menu.  You can also create a sub-menu using `$Menu(2)`, and a third-level menu  using using `$Menu(3)`, etc.

### <% loop $Menu(1) %>...<% end_loop %> %>

This template code defines a repeating element.  `$Menu(1)`.  Like `<% with %>`, the loop block will change the "scope" of your template, which means that all of the template variables you use inside it will refer to a menu item.  The template code will be repeated once per menu item, with the scope set to that menu item's page.

### $Link, $Title, $MenuTitle

Because these 3 variables are within the repeating element, then their values will come from that repeating element.  In this case, they will be the values of each menu item.

 * `$Link`: A link to that menu item.
 * `$Title`: The page title of that menu item.
 * `$MenuTitle`: The navigation label of that menu item.

### $LinkingMode

Once again, this is a variable that will be source from the menu item.  This variable differs for each menu item, and will be set to one of these 3 values:

 * `link`: You are neither on this page nor in this section.
 * `current`: You are currently on this page.
 * `section`: The current page is a child of this menu item; so this is menu item identifies the section that you're currently in.

By setting the HTML class to this value, you can distinguish the styling of the currently selected menu item.

### $Layout

This variable will be replaced with the the rendered version of `templates/Layout/Page.ss`.  If you create a page type that is a subclass of Page, then it is possible to only define `templates/Layout/MySubclassPage.ss`.  In that case, then the rendered version of `templates/Layout/MySubclassPage.ss` wil be inserted into the `$Layout` variable in `templates/Page.ss`.  This is a good way of defining a single main template and page specific sub-templates.

### $Now.Year

This will return the current year.  `$Now` returns an `SS_Datetime` object, which has a number of methods, such as `Year`.  See [the API documentation](api:SS_Datetime) for a list of all the methods.

### $Title

This is the same template code as used in the title attribute of your navgation.  However, because we are using it outside of the `<% loop Menu(1) >` block, it will return the title of the current page, rather than the title of the menu item.  We use this to make our main page title.

### $Content

This variable contains the content of the current page.

### $Form

Very often, a page will contain some content and a form of some kind.  For example, the log-in page has a log-in form.  If you are on such a page, this variable will contain the HTML content of the form.  Putting it just below $Content is a good default.