# How to extend the CMS interface #

## Introduction ##

The CMS interface works just like any other part of your website: It consists of PHP controllers,
templates, CSS stylesheets and JavaScript. Because it uses the same base elements,
it is relatively easy to extend.

The CMS is built on principles of "[unobtrusive JavaScript](http://en.wikipedia.org/wiki/Unobtrusive_JavaScript)".

## Add a panel to the CMS interface ##

The CMS uses a JavaScript layout manager called [jLayout](http://www.bramstein.com/projects/jlayout/),
which allows us to build complex layouts with minimal JavaScript configuration.
As an example, we're going to add a permanent "quicklinks" bar to popular pages at the bottom
of the "Pages" area (but leave it out of other areas like "Users").

CMS templates are inherited based on their controllers, similiar to subclasses of
the common `Page` object (a new PHP class `MyPage` will look for a `MyPage.ss` template).
We can use this fact to target one specific area only: the `CMSPageEditController` class.

1. Create a new template in `mysite/templates/CMSPageEditController.ss`
2. Copy the template markup of the base implementation at `sapphire/admin/templates/LeftAndMain.ss` into the file.
It will automatically be picked up by the CMS logic.
3. Add a new section after the `$Content` tag:
	
		:::ss
		...
		<div class="cms-container" data-layout="{type: 'border'}">
			$Menu
			$Content
			<div class="cms-bottom-bar south">
				<ul>
					<li><a href="admin/page/edit/show/1">Edit "My popular page"</a></li>
					<li><a href="admin/page/edit/show/99">Edit "My other page"</a></li>
				</ul>
			</div>
		</div>
		...

4. Create a new `mysite/css/CMSPageEditController.css` file and paste the following content:

		:::css
		.cms-bottom-bar {height: 20px;}

5. Load the new CSS file into the CMS, by adding the following line to `mysite/_config.php`:

		:::php
		LeftAndMain::require_css('mysite/css/CMSPageEditController.css');

Done! You might have noticed that we didn't write any JavaScript to add our layout manager.
The important piece of information is the `south` class in our new `<div>` structure,
plus the height value in our CSS. It instructs the existing parent layout how to render the element.

The page list itself is hardcoded for now, we'll leave it to the reader to make
this a dynamic and prettier list. Hint: Take a look at the [LeftAndMain](../reference/leftandmain) documentation to find
out how to subclass and replace an admin interface with your own PHP code.