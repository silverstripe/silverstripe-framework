# How to extend the CMS interface #

## Introduction ##

The CMS interface works just like any other part of your website: It consists of PHP controllers,
templates, CSS stylesheets and JavaScript. Because it uses the same base elements,
it is relatively easy to extend. 
As an example, we're going to add a permanent "bookmarks" bar to popular pages at the bottom of the CMS.
A page can be bookmarked by a CMS author through a simple checkbox.

For a deeper introduction to the inner workings of the CMS, please refer to our
guide on [CMS Architecture](../reference/cms-architecture).

## Overload a CMS template ##

First of all, create a new folder structure in your SilverStripe webroot, which will
form our module for this example. 
	
	cms/
	framework/
	zzz_admin/
		_config.php
		code/
		css/
		admin/
			templates/
				
Note: The `zzz_` prefix and `admin/` subfolder are only a temporary measure necessary to ensure our templates
are included *after* the original CMS templates. At the moment, you can't use the `mysite/` folder
to achieve the same result.

CMS templates are inherited based on their controllers, similar to subclasses of
the common `Page` object (a new PHP class `MyPage` will look for a `MyPage.ss` template).
We can use this to create a different base template with `LeftAndMain.ss`
(which corresponds to the `LeftAndMain` PHP controller class).

Copy the template markup of the base implementation at `framework/admin/templates/LeftAndMain.ss` into `zzz_admin/admin/templates/LeftAndMain.ss`. It will automatically be picked up by the CMS logic. Add a new section after the `$Content` tag:
	
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
	
Refresh the CMS interface with `admin/?flush=all`, and you should see the new bottom bar with some hardcoded links.
We'll make these dynamic further down. 

You might have noticed that we didn't write any JavaScript to add our layout manager. 
The important piece of information is the `south` class in our new `<div>` structure,
plus the height value in our CSS. It instructs the existing parent layout how to render the element.
This layout manager ([jLayout](http://www.bramstein.com/projects/jlayout/)) 
allows us to build complex layouts with minimal JavaScript configuration.
	
## Include custom CSS in the CMS

In order to show the links in one line, we'll add some CSS, and get it to load with the CMS interface.
Paste the following content into a new file called `zzz_mysite/css/BookmarkedPages.css`:

	:::css
	.cms-bottom-bar {height: 20px; padding: 5px; background: #C6D7DF;}
	.cms-bottom-bar ul {list-style: none; margin: 0; padding: 0;}
	.cms-bottom-bar ul li {float: left; margin-left: 1em;}
	.cms-bottom-bar a {color: #444444;}

Load the new CSS file into the CMS, by adding the following line to `zzz_admin/_config.php`:

	:::php
	<?php
	LeftAndMain::require_css('zzz_admin/css/CMSBookmarkBar.css');

## Create a "bookmark" flag on pages ##

Now we'll define which pages are actually bookmarked, a flag that is stored in the database.
For this we need to decorate the page record with a `DataExtension`.
Create a new file called `zzz_admin/code/BookmarkedPageExtension.php` and insert the following code.

	:::php
	<?php
	class BookmarkedPageExtension extends DataExtension {
		public static $db = array('IsBookmarked' => 'Boolean');
		
		public function updateCMSFields(FieldList $fields) {
			$fields->addFieldToTab('Root.Main',
				new CheckboxField('IsBookmarked', "Show in CMS bookmarks?")
			);
		}
	}

Enable the extension with the following line in `zzz_mysite/_config.php`:

	:::php
	Object::add_extension('SiteTree', 'BookmarkedPageExtension');

In order to add the field to the database, run a `dev/build/?flush=all`.
Refresh the CMS, open a page for editing and you should see the new checkbox.

## Retrieve the list of bookmarks from the database

One piece in the puzzle is still missing: How do we get the list of bookmarked
pages from the datbase into the template we've already created (with hardcoded links)? 
Again, we extend a core class: The main CMS controller called `LeftAndMain`.

Add the following code to a new file `zzz_admin/code/BookmarkedLeftAndMainExtension.php`;

	:::php
	<?php
	class BookmarkedPagesLeftAndMainExtension extends LeftAndMainExtension {
		public function BookmarkedPages() {
			return Page::get()->filter("IsBookmarked", 1);
		}
	}
	
Enable the extension with the following line in `zzz_mysite/_config.php`:

	:::php
	Object::add_extension('LeftAndMain', 'BookmarkedPagesLeftAndMainExtension');

As the last step, replace the hardcoded links with our list from the database.
Find the `<ul>` you created earlier in `zzz_admin/admin/templates/LeftAndMain.ss`
and replace it with the following:

	:::ss
	<ul>
		<% loop BookmarkedPages %>
		<li><a href="admin/page/edit/show/$ID">Edit "$Title"</a></li>
		<% end_loop %>
	</ul>

## Summary

In a few lines of code, we've customized the look and feel of the CMS.
While this example is only scratching the surface, it includes most building
blocks and concepts for more complex extensions as well.

## Related

 * [CMS Architecture](../reference/cms-architecture)
 * [Topics: Rich Text Editing](../topics/rich-text-editing)
