
# Sitetree

## Introduction

Basic data-object representing all pages within the site tree. The omnipresent *Page* class (located in
*mysite/code/Page.php*) is based on this class.


## Linking

	:::php
	// wrong
	$mylink = $mypage->URLSegment;
	// right
	$mylink = $mypage->Link(); // alternatively: AbsoluteLink(), RelativeLink()


## Querying

Use *SiteTree::get_by_link()* to correctly retrieve a page by URL, as it taked nested URLs into account (a page URL
might consist of more than one *URLSegment*).

	:::php
	// wrong
	$mypage = DataObject::get_one('SiteTree', '"URLSegment" = \'<mylink>\'');
	// right
	$mypage = SiteTree::get_by_link('<mylink>');



## Nested/Hierarchical URLs

In a nutshell, the nested URLs feature means that your site URLs now reflect the actual parent/child page structure of
your site. In SilverStripe 2.3 and earlier, all page URLs would be on the top level, regardless of whether they were
nested under other pages or not. In 2.4 however, the URLs now map directly to the chain of parent and child pages. The
below table shows a quick summary of what these changes mean for your site:

![url table](http://silverstripe.org/assets/screenshots/Nested-URLs-Table.png)

This feature is enabled by default in SilverStripe 2.4 or newer. To activate it for older sites, insert the following
code in your *mysite/_config.php*:

	:::php
	SiteTree::enable_nested_urls();


After activating nested URLs on an existing database, you'll have to run a migration task to rewrite internal URL
references in the *SiteTree.Content* field.

	http://<yourdomain.tld>/dev/tasks/MigrateSiteTreeLinkingTask

## Limiting Children/Parent

By default, any page type can be the child of any other page type.  However, there are 4 static properties that can be
used to set up restrictions that will preserve the integrity of the page hierarchy.

	:::php
	class BlogHolder extends Page {
	
	  // Blog holders can only contain blog entries
	  static $allowed_children = array("BlogEntry");
	
	  static $default_child = "BlogEntry";
	
	...
	
	class BlogEntry extends Page {
	  // Blog entries can't contain children
	  static $allowed_children = "none";
	
	  static $default_parent = "blog";
	
	  static $can_be_root = false;
	
	...
	
	
	class Page extends SiteTree {
	  // Don't let BlogEntry pages be underneath Pages.  Only underneath Blog holders.
	  static $allowed_children = array("*Page,", "BlogHolder");
	  
	}


*  **allowed_children:** This can be an array of allowed child classes, or the string "none" - indicating that this page
type can't have children.  If a classname is prefixed by "*", such as "*Page", then only that class is allowed - no
subclasses.  Otherwise, the class and all its subclasses are allowed.

*  **default_child:** If a page is allowed more than 1 type of child, you can set a default.  This is the value that
will be automatically selected in the page type dropdown when you create a page in the CMS.

*  **default_parent:** This should be set to the *URLSegment* of a specific page, not to a class name.  If you have
asked to create a page of a particular type that's not allowed underneath the page that you have selected, then the
default_parent page will be selected.  For example, if you have a gallery page open in the CMS, and you select add blog
entry, you can set your site up to automatically select the blog page as a parent.

*  **can_be_root:** This is a boolean variable.  It lets you specify whether the given page type can be in the top
level.

Note that there is no allowed_parents control.  To set this, you will need to specify the allowed_children of all other
page types to exclude the page type in question.  IMO this is less than ideal; it's possible that in a future release we
will add allowed_parents, but right now we're trying to limit the amount of mucking around with the API we do.

Here is an overview of everything you can add to a class that extends sitetree.  NOTE: this example will not work, but
it is a good starting point, for choosing your customisation.

	:::php
	class Page extends SiteTree {
	
		// tree customisation
	
		static $icon = "";
		static $allowed_children = array("SiteTree"); // set to string "none" or array of classname(s)
		static $default_child = "Page"; //one classname
		static $default_parent = null; // NOTE: has to be a URL segment NOT a class name
		static $can_be_root = true; //
		static $hide_ancestor = null; //dont show ancestry class
	
		// extensions and functionality
	
		static $versioning = array();
		static $default_sort = "Sort";
		/static $extensions = array();
		public static $breadcrumbs_delimiter = " &raquo; ";
	
	
		public function canCreate() {
			//here is a trick to only allow one (e.g. holder) of a page
			return !DataObject::get_one($this->class);
		}
	
		public function canDelete() {
			return false;
		}
	
		function getCMSFields() {
			$fields = parent::getCMSFields();
			return $fields;
		}


## Recipes

### Automatic Child Selection

By default, `[api:SiteTree]` class to build a tree using the ParentID field.  However, sometimes, you want to change
this default behaviour.

For example, in our e-commerce module, we use a many-to-many join, Product::Parents, to let you put Products in multiple
groups.  Here's how to implement such a change:

*  **Set up your new data model:** Create the appropriate many-many join or whatever it is that you're going to use to
store parents.

*  **Define stageChildren method:** This method should return the children of the current page, for the current version.
 If you use DataObject::get, the `[api:Versioned]` class will rewrite your query to access the live site when
appropriate.

*  **Define liveChildren method:** The method should return the children of the current page, for the live site.

Both the CMS and the site's data controls will make use of this, so navigation, breadcrumbs, etc will be updated.  If 1
node appears in the tree more than once, it will be represented differently. 

**TO DO:** Work out this representation.


###  Custom Children Getters

Returning custom children for a specific `SiteTree` subclass can be handy to influence the tree display within the
CMS. An example of custom children might be products which belong to multiple categories. One category would get its
products from a `$many_many` join rather than the default relations.

Children objects are generated from two functions `stageChildren()` and `liveChildren()` and the tree generation in
the CMS is calculated from `numChildren()`. Please keep in mind that the returned children should still be instances
of `SiteTree`.

Example:

	:::php
	class MyProduct extends Page {
		static $belongs_many_many = array(
			'MyCategories' => 'MyCategory'
		);
	}
	class MyCategory extends Page {
		static $many_many = array(
			'MyProducts' => 'MyProduct'
		);
		function stageChildren($showAll = false) {
			// @todo Implement $showAll
			return $this->MyProducts();
		}
	
		function liveChildren($showAll = false) {
			// @todo Implement $showAll
			return $this->MyProducts();
		}
		function numChildren() {
			return $this->MyProducts()->Count();
		}
	}	}
	}



### Multiple parents in the tree

The `[api:LeftAndMain]` tree supports multiple parents.  We overload CMSTreeClasses and make it include "manyparents" in
the class list.

	:::php
	function CMSTreeClasses($controller) {
		return parent::CMSTreeClasses($controller) . ' manyparents';
	}


Don't forget to define a new Parent() method that also references your new many-many join (or however it is you've set
up the hierarchy!

	:::php
	function getParent() {
	  return $this->Parent();
	}
	function Parent() {
	  $parents = $this->Parents();
	  if($parents) return $parents->First();
	}


Sometimes, you don't want to mess with the CMS in this manner.  In this case, leave stageChildren() and liveChildren()
as-is, and instead make another method, such as ChildProducts(), to get the data from your many-many join.

### Dynamic Grouping

Something that has been talked about [here](http://www.silverstripe.com/site-builders-forum/flat/15416#post15940) is the
concept of "dynamic grouping".  In essence, it means adding navigational tree nodes to the tree that don't correspond to
a database record.

How would we do this?  In our example, we're going to update BlogHolder to show BlogEntry children grouped into months.

We will create a class called BlogMonthTreeNode, which will extend ViewableData instead of DataRecord, since it's not
saved into the database.  This will represent our dynamic groups.

### LeftAndMain::getSiteTreeFor()

Currently LeftAndMain::getSiteTreeFor() Calls LeftAndMain::getRecord($id) to get a new record.  We need to instead
create a new function getTreeRecord($id) which will be able to create BlogMonthTreeNode objects as well as look up
SiteTree records from the database.

The IDs don't **need** be numeric; so we can set the system to allow for 2 $id formats.

*  (ID): A regular SiteTree object
*  BlogMonthTreeNode-(BlogHolderID)-(Year)-(Month): A BlogMonthTreeNode object

To keep the code generic, we will assume that if the $id isn't numeric, then we should explode('-', $id), and use the
first part as the classname, and all the remaining parts as arguments to the constructor.

Your BlogMonthTreeNode constructor will then need to take $blogHolderID, $year, $month as arguments.

### Divorcing front-end site's Children() and the CMS's AllChildrenIncludingDeleted()

We need a way of cleanly specifying that there are two different child sources - children for the CMS tree, and children
for the front-end site.

*  We currently have stageChildren() / liveChildren()
*  We should probably add cmsStageChildren() and cmsLiveChildren() into the mix, for SiteTree.

AllChildrenIncludingDeleted() could then call the "cms..." versions of the functions, but if we were to to this, we
should probably rename AllChildrenIncludingDeleted() to CMSTreeChildren() or something like that.

### BlogHolder::cmsStageChildren() & BlogHolder::cmsLiveChildren()

We will need to define these methods, to 

*  Get the stage/live children of the page, grouped by month
*  For each entry returned, generate a new BlogMonthTreeNode object.
*  Return that as a dataobjectset.

### BlogMonthTreeNode

*  Parameter 'ID': should return 'BlogMonthTreeNode-(BlogHolderID)-(Year)-(Month)'.  You can do  this by implementing
getID().
*  Methods cmsStageChildren() and cmsLiveChildren(): These should return the blog-entries for that month.

After that, there will be some other things to tweak, like the tree icons.

### Where to from here?

This is a lot of work for the specific example of blog-entries grouped by month.  Instead of BlogMonthTreeNode, you
could genericise this to a DynamicTreeGroup class, which would let you specify the parent node, the type of grouping,
and the specific group.

## TODO
Clean up this documentation

## API Documentation
`[api:Sitetree]`
