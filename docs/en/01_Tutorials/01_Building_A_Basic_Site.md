title: Building a basic site
summary: An overview of the SilverStripe installation and an introduction to creating a web page.

# Tutorial 1 - Building a Basic Site

## Overview

Welcome to the first in this series of tutorials on the SilverStripe Content Management System (CMS). 

These tutorials are designed to take you from an absolute beginner to being able to build large, complex websites with SilverStripe. We assume to begin with, that you have some XHTML, CSS and PHP knowledge. This first tutorial provides an absolute
introduction to building a simple website using SilverStripe. It will also teach you how to use the content management system at a basic level.

##  What are we working towards?

We are going to create a site in which all the content can be edited in the SilverStripe CMS. It will have a two-level
navigation system, which will be generated on the fly to include all pages you add in the CMS. We will use two different
templates - one for the home page, and one for the rest of the site.

##  Installation

You need to [download the SilverStripe software](http://www.silverstripe.org/software/download) and install it to your local machine or to a webserver. 

For more information about installing and configuring a webserver read the [Installation instructions and videos](/getting_started/installation). 

This tutorial uses the SilverStripe CMS default theme 'Simple' which you will find in the themes folder. We will investigate the existing template files that make up the theme as well as create some new files to build upon the theme.

##  Exploring the installation

After installation, open up the folder where you installed SilverStripe. 

If you installed on windows with WAMP, it will likely be at *c:\wamp\www*. On Mac OS X, using the built in webserver, it will be in your sites directory */Sites/* (with MAMP, it will likely be at */Applications/MAMP/htdocs/*)

Let's have a look at the folder structure.

 | Directory | | Description  | 
 | --------- | | -----------  | 
 | assets/   | | Contains images and other files uploaded via the SilverStripe CMS. You can also place your own content inside it, and link to it from within the content area of the CMS. | 
 | cms/      | | Contains all the files that form the CMS area of your site. Its structure is similar to the mysite/ directory, so if you find something interesting, it should be easy enough to look inside and see how it was built. | 
 | framework/ | | The framework that builds both your own site and the CMS that powers it. You’ll be utilizing files in this directory often, both directly and indirectly.                                                             | 
 | mysite/   | | Contains all your site's code (mainly PHP).  | 
 | themes/   | | Combines all images, stylesheets, javascript and templates powering your website into a reusable "theme". | 
      
When designing your site you should only need to modify the *mysite*, *themes* and *assets* folders. The rest of the folders contain files and data that are not specific to any site.

##  Using the CMS

### User Interface Basics

![](../_images/tutorial1_cms-basic.jpg)

The CMS is the area in which you can manage your site content. You can access the cms at http://localhost/your_site_name/admin (or http://yourdomain.com/admin if you are using your own domain name). You
will be presented with a login screen. Login using the details you provided at installation. After logging in you
should see the CMS interface with a list of the pages currently on your website (the site tree). Here you can add, delete and reorganize pages. If you need to delete, publish, or unpublish a page, first check "multi-selection" at the top. You will then be able to perform actions on any checked files using the "Actions" dropdown. Clicking on a page will open it in the page editing interface pictured below (we've entered some test content).

![](../_images/tutorial1_cms-numbered.jpg)

1.  This menu allows you to move between different sections of the CMS. There are four core sections - "Pages", "Files", "Users" and "Settings". If you have modules installed, they may have their own sections here. In this tutorial we will be focusing on the "Pages" section.
2.  The breadcrumbs on the left will show you a direct path to the page you are currently looking at. You can use this path to navigate up through a page's hierarchy. On the left there are tabs you may use to flick between different aspects of a page. By default, you should be shown three tabs: "Content", "Settings", and "History". 
 * Content - Allows you to set the title, wysiwyg content, URL and Meta data for your page.  
 * Settings - Here you set the type of page behavior, parent page, show in search, show in menu, and who can view or edit the page.  
 * History - This allows you to view previous version of your page, compare, change, and revert to previous version if need be.  
3.  Within the "Pages" section (provided you are in the "Content" or "Settings" tab) you can quickly move between pages in the CMS using the site tree. To collapse and expand this sidebar, click the arrow at the bottom. If you are in the history tab, you will notice the site tree has been replaced by a list of the alterations to the current page.  
![](../_images/tutorial1_cms-numbered-3.jpg)  
4.  This section allows you to edit the content for the currently selected page, as well as changing other properties of the page such as the page name and URL. The content editor has full [WYSIWYG](http://en.wikipedia.org/wiki/WYSIWYG) capabilities, allowing you to change formatting and insert links, images, and tables.
5.  These buttons allow you to save your changes to the draft copy, publish your draft copy, unpublish from the live website, or remove a page from the draft website. The SilverStripe CMS workflow stores two copies of a page, a draft and a published copy. By having separate draft and published copies, we can preview draft changes on the site before publishing them to the live website. You can quickly preview your draft pages without leaving the CMS by clicking the "Preview" button.

![](../_images/tutorial1_cms-numbered-5.jpg)  

### Try it

There are three pages already created for you - "Home", "About Us" and "Contact Us", as well as a 404 error page. Experiment
with the editor - try different formatting, tables and images. When you are done, click "Save Draft" or "Save
& Publish" to post the content to the live site. 

### New pages
To create a new page, click the "Add New" button above the site tree.  
When you create a new page, you are given the option of setting the structure of the page ("Top level" or "Under another page") and the page type. 
The page type specifies the templates used to render the page, the fields that are able to be edited in the CMS, and page specific behavior. We will explain page types in more depth as we progress; for now, make all pages of the type "Page".

![](../_images/tutorial1_addpage.jpg)

**SilverStripe's friendly URLs**

While you are on the draft or live SilverStripe site, you may notice the URLs point to files that don't exist, e.g.
http://localhost/contact or http://yourdomainname.com/about-us etc. SilverStripe uses the URL field on the Meta-Data tab of the Edit Page -> Content section to look up the appropriate
page in the database.

Note that if you have sub-pages, changing the Top level URL field for a page will affect the URL for all sub-pages. For example, if we changed the URL field "/about-us/" to "/about-silverstripe/" then the sub-pages URLs would now be "/about-silverstripe/URL-of-subpage/" rather than "/about-us/URL-of-subpage/".

![](../_images/tutorial1_url.jpg)

When you create a new page, SilverStripe automatically creates an appropriate URL for it. For example, *About Us* will
become *about-us*. You are able to change it yourself so that you can make long titles more usable or descriptive. For
example, *Employment Opportunities* could be shortened to *jobs*. The ability to generate easy-to-type, descriptive URLs
for SilverStripe pages improves accessibility for humans and search engines.

You should ensure the URL for the home page is *home*, as that is the page SilverStripe loads by default.


## Templates

All pages on a SilverStripe site are rendered using a template. A template is a file 
with a special `*.ss` file extension, containing HTML augmented with some control codes.  Through the use of templates, you can have as much control over your site’s HTML code as you like. In SilverStripe, the template files and others for controlling your sites appearance, such as the CSS, images, and some javascript, are collectively described as a theme. Themes live in the 'themes' folder of your site.

Every page in your site has a **page type**. We will briefly talk about page types later, and go into much more detail
in tutorial two; right now all our pages will be of the page type *Page*. When rendering a page, SilverStripe will look
for a template file in the *simple/templates* folder, with the name `<PageType>`.ss - in our case *Page.ss*.

Open *themes/simple/templates/Page.ss*. It uses standard HTML apart from these exceptions: 

	:::ss
	<% base_tag %>

The base_tag variable is replaced with the HTML [base element](http://www.w3.org/TR/html401/struct/links.html#h-12.4). This
ensures the browser knows where to locate your site's images and css files.

	:::ss
	$Title
	$SiteConfig.Title

These two variables are found within the html `<title>` tag, and are replaced by the "Page Name" and "Settings -> Site Title" fields in the CMS.

	:::ss
	$MetaTags 

The MetaTags variable will add meta tags, which are used by search engines. You can define your meta tags in the tab fields at the bottom of the content editor in the CMS. 
	:::ss
	$Layout 

The Layout variable is replaced with the contents of a template file with the same name as the page type we are using. 

Open *themes/simple/templates/Layout/Page.ss*. You will see more HTML and more SilverStripe template replacement tags and variables.

	:::ss
	$Content

The Content variable is replaced with the content of the page currently being viewed. This allows you to make all changes to
your site's content in the CMS.

These template markers are processed by SilverStripe into HTML before being sent to your
browser and are either prefixed with a dollar sign ($)
or placed between SilverStripe template tags: 

	:::ss
	<%  %>


**Flushing the cache**

Whenever we edit a template file, we need to append *?flush=1* onto the end of the URL, e.g.
http://localhost/your_site_name/?flush=1. SilverStripe stores template files in a cache for quicker load times. Whenever there are
changes to the template, we must flush the cache in order for the changes to take effect.

##  The Navigation System

We are now going to look at how the navigation system is implemented in the template. 

Open up *themes/simple/templates/Includes/Navigation.ss*

The Menu for our site is created using a **loop**. Loops allow us to iterate over a data set, and render each item using a sub-template.

	:::ss 
	<% loop $Menu(1) %>

returns a set of first level menu items. We can then use the template variable
*$MenuTitle* to show the title of the page we are linking to, *$Link* for the URL of the page and *$LinkingMode* to help style our menu with CSS (explained in more detail shortly).

> *$Title* refers to **Page Name** in the CMS, whereas *$MenuTitle* refers to (the often shorter) **Navigation label**


	:::ss
	<ul>
		<% loop $Menu(1) %>	  
			<li class="$LinkingMode">
				<a href="$Link" title="$Title.XML">$MenuTitle.XML</a>
			</li>
		<% end_loop %>
	</ul>

Here we've created an unordered list called *Menu1*, which *themes/simple/css/layout.css* will style into the menu.
Then, using a loop over the page control *Menu(1)*, we add a link to the list for each menu item. 

This creates the navigation at the top of the page:

![](../_images/tutorial1_menu.jpg)



### Highlighting the current page

A useful feature is highlighting the current page the user is looking at. We can do this with the template variable: `$LinkingMode`. It returns one of three values:

*  *current* - This page is being visited
*  *link* - This page is not currently being visited
*  *section* - A page under this page is being visited

For example, if you were here: "Home > Company > Staff > Bob Smith", you may want to highlight 'Company' to say you are in that section. If you add $LinkingMode to your navigation elements as a class, ie:

	:::ss
	<li class="$LinkingMode">
	 	<a href="$Link" title="$Title.XML">$MenuTitle.XML</a>
	</li>

you will then be able to target a section in css (*simple/css/layout.css*), e.g.:

	:::css
	.section { background:#ccc; } 

## A second level of navigation

The top navigation system is currently quite restrictive. There is no way to
nest pages, so we have a completely flat site. Adding a second level in SilverStripe is easy. First (if you haven't already done so), let's add some pages. 

The "About Us" section could use some expansion. 

Select "Add New" in the Pages section, and create two new pages nested under the page "About Us" called "What we do" and "Our History" with the type "Page". 

You can also create the pages elsewhere on the site tree, and drag and drop the pages into place. 

Either way, your site tree should now look something like this:

![](../_images/tutorial1_2nd_level-cut.jpg)

Great, we now have a hierarchical site structure! Let's look at how this is created and displayed in our template.

Adding a second level menu is very similar to adding the first level menu. Open up */themes/simple/templates/Includes/Sidebar.ss* template and look at the following code:

	:::ss
	<ul>
	  <% loop $Menu(2) %>
	    <li class="$LinkingMode">
		    <a href="$Link" title="Go to the $Title.XML page">
		    	<span class="arrow">→</span>
		    	<span class="text">$MenuTitle.XML</span>
		    </a>
	    </li>
	  <% end_loop %>
	</ul>

This should look very familiar. It is the same idea as our first menu, except the loop block now uses *Menu(2)* instead of *Menu(1)*. 
As we can see here, the *Menu* control takes a single
argument - the level of the menu we want to get. Our css file will style this linked list into the second level menu,
using our usual *$LinkingMode* technique to highlight the current page.

To make sure the menu is not displayed on every page, for example, those that *don't* have any nested pages. We use an **if block**. 
Look again in the *Sidebar.ss* file and you will see that the menu is surrounded with an **if block**
like this:

	:::ss
	<% if $Menu(2) %>
		...
			<ul>
				<% loop $Menu(2) %>
				<li class="$LinkingMode">
					<a href="$Link" title="Go to the $Title.XML page">
						<span class="arrow">→</span>
						<span class="text">$MenuTitle.XML</span>
					</a>
				</li>
				<% end_loop %>
			</ul>
		...
	<% end_if %>  	

The if block only includes the code inside it if the condition is true. In this case, it checks for the existence of
*Menu(2)*. If it exists then the code inside will be processed and the menu will be shown. Otherwise the code will not
be processed and the menu will not be shown.

Now that we have two levels of navigation, it would also be useful to include some "breadcrumbs". 

Open up */themes/simple/templates/Includes/BreadCrumbs.ss* template and look at the following code:

	:::ss
	<% if $Level(2) %>
		<div id="Breadcrumbs">
		   	$Breadcrumbs
		</div>
	<% end_if %>	

Breadcrumbs are only useful on pages that aren't in the top level. We can ensure that we only show them if we aren't in
the top level with another if statement.

The *Level* page control allows you to get data from the page's parents, e.g. if you used *Level(1)*, you could use:

	:::ss
	$Level(1).Title 

to get the top level page title. In this case, we merely use it to check the existence of a second level page: if one exists then we include breadcrumbs.

Both the top menu, and the sidebar menu should be updating and highlighting as you move from page to page. They will also mirror changes done in the SilverStripe CMS, such as renaming pages or moving them around.

![](../_images/tutorial1_menu-two-level.jpg)

Feel free to experiment with the if and loop statements. For example, you could create a drop down style menu from the top navigation using a combination of if statements, loops, and some CSS to style it. 

The following example runs an if statement and a loop on *Children*, checking to see if any sub-pages exist within each top level navigation item. You will need to come up with your own CSS to correctly style this approach.

	:::ss
	<ul>
	  <% loop $Menu(1) %>
	    <li class="$LinkingMode">
	      <a href="$Link" title="$Title.XML">$MenuTitle.XML</a>
	      <% if $Children %>
		      <ul>
		        <% loop $Children %>
		          <li class="$LinkingMode">
		          	<a href="$Link" title="Go to the $Title.XML page">
		          		<span class="arrow">→</span>
		          		<span class="text">$MenuTitle.XML</span>
		          	</a>
		          </li>
		        <% end_loop %>
		      </ul>
	      <% end_if %>
	    </li>
	  <% end_loop %>
	</ul>



## Using a different template for the home page

So far, a single template layout *Layouts/Page.ss* is being used for the entire site. This is useful for the purpose of this
tutorial, but in a finished website we can expect there to be several page layouts.

To illustrate how we do this, we will create a new template for the homepage. This template will have a large graphical
banner to welcome visitors.

### Creating a new page type

Earlier we stated that every page in a SilverStripe site has a **page type**, and that SilverStripe will look for a
template, or template layout, corresponding to the page type. Therefore, the first step when switching the homepage template is to create a new page type.

Each page type is represented by two PHP classes: a *data object* and a *controller*. Don't worry about the details of page
types right now, we will go into much more detail in the [next tutorial](/tutorials/extending_a_basic_site).

Create a new file *HomePage.php* in *mysite/code*. Copy the following code into it:

	:::php
	<?php
	class HomePage extends Page {
	}
	class HomePage_Controller extends Page_Controller {
	}


Every page type also has a database table corresponding to it. Every time we modify the database, we need to rebuild it.
We can do this by going to [http://localhost/your_site_name/dev/build](http://localhost/your_site_name/dev/build) (replace *localhost/your_site_name* with your own domain name if applicable). 

It may take a moment, so be patient. This adds tables and fields needed by your site, and modifies any structures that have changed. It
does this non-destructively - it will never delete your data.

As we have just created a new page type, SilverStripe will add this to the list of page types in the database.

### Changing the page type of the Home page

After building the database, we can change the page type of the homepage in the CMS. 

In the CMS, navigate to the "Home" page and switch to the "Settings" tab. Change "Page type" to *Home Page*, and click "Save & Publish".

![](../_images/tutorial1_homepage-type.jpg)

Our homepage is now of the page type *HomePage*. Regardless, it is still
rendered with the *Page* template. SilverStripe does this as our homepage inherits its type from *Page*,
which acts as a fallback if no *HomePage* template can be found. 
It always tries to use the most specific template in an inheritance chain.


### Creating a new template

To create a new template layout, create a copy of *Page.ss* (found in *themes/simple/templates/Layout*) and call it *HomePage.ss*. If we flush the cache (*?flush=1*), SilverStripe should now be using *HomePage.ss* for the homepage, and *Page.ss* for the rest of the site. Now let's customize the *HomePage* template. 

First, we don't need the breadcrumbs and the secondary menu for the homepage. Let's remove them:
	:::ss
	<% include SideBar %> 
	
We'll also replace the title text with an image. Find this line:

	:::ss
	<h1>$Title</h1>

 and replace it with:

	:::ss
	<div id="Banner">
	  <img src="http://www.silverstripe.org/assets/SilverStripe-200.png" alt="Homepage image" />
	</div>


Your Home page should now look like this:


![](../_images/tutorial1_home-template.jpg)

SilverStripe first searches for a template in the *themes/simple/templates* folder. Since there is no *HomePage.ss*,
it will use the *Page.ss* for both *Page* and *HomePage* page types. When it comes across the *$Layout* tag, it will
then descend into the *themes/simple/templates/Layout* folder, and will use *Page.ss* for the *Page* page type, and
*HomePage.ss* for the *HomePage* page type. So while you could create a HomePage.ss in the *themes/simple/templates/* it is better to reuse the navigation and footer common to both our Home page and the rest of the pages on our website.

![](../_images/tutorial1_subtemplates-diagram.jpg)


## Summary

So far we have taken a look at the different areas and functionality within the pages area of the CMS. We have learnt about template variables, controls and if statements and used these to build a basic, but fully functional, website. We have also briefly covered page types, and looked at how they correspond to templates and sub-templates. Using this knowledge, we have customized our website's homepage design.

In the next tutorial, [Extending a Basic Site](/tutorials/extending_a_basic_site), we will explore page types on a deeper level, and look at customising our own page types to extend the functionality of SilverStripe.

[Next tutorial >>](/tutorials/extending_a_basic_site)
