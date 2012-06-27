# Sitetree

## Introduction

Basic data-object representing all pages within the site tree. 
The omnipresent *Page* class (located in `mysite/code/Page.php`) is based on this class.

## Creating, Modifying and Finding Pages

See the ["datamodel" topic](/topics/datamodel).

## Linking

	:::php
	// wrong
	$mylink = $mypage->URLSegment;
	// right
	$mylink = $mypage->Link(); // alternatively: AbsoluteLink(), RelativeLink()

In a nutshell, the nested URLs feature means that your site URLs now reflect the actual parent/child page structure of
your site. The URLs map directly to the chain of parent and child pages. The
below table shows a quick summary of what these changes mean for your site:

![url table](http://silverstripe.org/assets/screenshots/Nested-URLs-Table.png)

## Querying

Use *SiteTree::get_by_link()* to correctly retrieve a page by URL, as it taked nested URLs into account (a page URL
might consist of more than one *URLSegment*).

	:::php
	// wrong
	$mypage = SiteTree::get()->filter("URLSegment", '<mylink>')->First();
	// right
	$mypage = SiteTree::get_by_link('<mylink>');

### Versioning
	
The `SiteTree` class automatically has an extension applied to it: `[Versioned](api:Versioned)`.
This provides the basis for the CMS to operate on different stages,
and allow authors to save their changes without publishing them to
website visitors straight away.
`Versioned` is a generic extension which can be applied to any `DataObject`,
so most of its functionality is explained in the `["versioning" topic](/topics/versioning)`.

Since `SiteTree` makes heavy use of the extension, it adds some additional
functionality and helpers on top of it.

Permission control:

	:::php
	class MyPage extends Page {
		function canPublish($member = null) {
			// return boolean from custom logic
		}
		function canDeleteFromLive($member = null) {
			// return boolean from custom logic
		}
	}

Stage operations:

 * `$page->doUnpublish()`: removes the "Live" record, with additional permission checks,
	as well as special logic for VirtualPage and RedirectorPage associations
 * `$page->doPublish()`: Inverse of doUnpublish()
 * `$page->doRevertToLive()`: Reverts current record to live state (makes sense to save to "draft" stage afterwards)
 * `$page->doRestoreToStage()`: Restore the content in the active copy of this SiteTree page to the stage site.
	

Hierarchy operations (defined on `[api:Hierarchy]`:

 * `$page->liveChildren()`: Return results only from live table
 * `$page->stageChildren()`: Return results from the stage table
 * `$page->AllHistoricalChildren()`: Return all the children this page had, including pages that were deleted from both stage & live.
 * `$page->AllChildrenIncludingDeleted()`: Return all children, including those that have been deleted but are still in live.

## Limiting Hierarchy

By default, any page type can be the child of any other page type.  
However, there are static properties that can be
used to set up restrictions that will preserve the integrity of the page hierarchy.

Example: Restrict blog entry pages to nesting underneath their blog holder

	:::php
	class BlogHolder extends Page {
	  // Blog holders can only contain blog entries
	  static $allowed_children = array("BlogEntry");
	  static $default_child = "BlogEntry";
	  // ...
	}
	
	class BlogEntry extends Page {
	  // Blog entries can't contain children
	  static $allowed_children = "none";
	  static $can_be_root = false;
	  // ...
	}	
	
	class Page extends SiteTree {
	  // Don't let BlogEntry pages be underneath Pages.  Only underneath Blog holders.
	  static $allowed_children = array("*Page,", "BlogHolder");
	}


*  **allowed_children:** This can be an array of allowed child classes, or the string "none" - indicating that this page
type can't have children.  If a classname is prefixed by "*", such as "*Page", then only that class is allowed - no
subclasses.  Otherwise, the class and all its subclasses are allowed.

*  **default_child:** If a page is allowed more than 1 type of child, you can set a default.  This is the value that
will be automatically selected in the page type dropdown when you create a page in the CMS.

*  **can_be_root:** This is a boolean variable.  It lets you specify whether the given page type can be in the top
level.

Note that there is no allowed_parents` control.  To set this, you will need to specify the `allowed_children` of all other page types to exclude the page type in question.

## Permission Control



## Tree Display (Description, Icons and Badges)

The page tree in the CMS is a central element to manage page hierarchies,
hence its display of pages can be customized as well.

On a most basic level, you can specify a custom page icon
to make it easier for CMS authors to identify pages of this type,
when navigating the tree or adding a new page:

	:::php
	class StaggPage extends Page {
		static $singular_name = 'Staff Directory';
		static $plural_name = 'Staff Directories';
		static $description = 'Two-column layout with a list of staff members';
		static $icon = 'mysite/images/staff-icon.png';
		// ...
	}

You can also add custom "badges" to each page in the tree,
which denote status. Built-in examples are "Draft" and "Deleted" flags.
This is detailed in the ["Customize the CMS Tree" howto](/howto/customize-cms-tree).