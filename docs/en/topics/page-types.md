# Page Types

## Introduction

Page Types are the basic building blocks of any SilverStripe website. A page type can define:

*  The template or templates that are used to display content
*  What fields are available to edit in the CMS
*  Behaviour specific to a page type – for example a contact form on the ‘Contact Us’ page that sends an email
when the form is submitted

All the pages on the base installation are of the page type "Page". See
[tutorial:2-extending-a-basic-site](/tutorials/2-extending-a-basic-site) for a good introduction to page-types.

Each page type on your website is a sub-class of the SiteTree class. Usually, you’ll define a class called ‘Page’
and use this template to lay out the basic design elements that don’t change. Take a look at mysite/templates/Page.ss.
It contains standard HTML markup, with some differences. We’ll go over these later, but for now, you can see that this
file only generates some of the content – it sets up the `<html>` tags, deals with the `<head>` section, creates the
first-level navigation, and then closes it all off again. See $Layout? That’s what is doing most of the work when you
visit a page. Now take a look at `mysite/templates/Layout/Page.ss`. This as you can see has a lot more markup in it –
it’s what is included into $Layout when the ‘Page’ page type is rendered. Similarly,
`mysite/templates/Layout/HomePage.ss` would be rendered into $Layout when the ‘HomePage’ page type is selected for the
current page you’re viewing.

Why do we sub-class Page for everything? The easiest way to explain this is to use the example of a search form. If we
create a search form on the Page class, then any other sub-class can also use it in their templates. This saves us
re-defining commonly used forms or controls in every class we use.

![](_images/pagetype-inheritance.png)

Each page type is represented by two classes: a data object and a controller. In the diagrams above and below, the data
objects are black and the controllers are blue. The page controllers are only used when the page type is actually
visited on the website. In our example above, the search form would become a method on the ‘Page_Controller’ class.
Any methods put on the data object will be available wherever we use this page. For example, we put any customizations
we want to do to the CMS for this page type in here.

![](_images/controllers-and-dataobjects.png)

Page types are created using PHP classes. If you’re not sure about how these work, [click here for a gentler
introduction to PHP classes](http://www-128.ibm.com/developerworks/opensource/library/os-phpobj/). 

We put the Page class into a file called Page.php inside `mysite/code`. We also put Page_Controller in here. Any other
classes that are based on Page – for example, the class Page_AnythingElse will also go into Page.php. Likewise, the
StaffPage_Image class will go into StaffPage.php.

## Adding database-fields

Adding database fields is a simple process. You define them in an array of the static variable `$db`, this array is
added on the object class. For example, Page or StaffPage. Every time you run db/build to recompile the manifest, it
checks if any new entries are added to the `$db` array and adds any fields to the database that are missing.

For example, you may want an additional field on a StaffPage class which extends Page, called Author. Author is a
standard text field, and can be [casted](/topics/datamodel) as a variable character object in php (VARCHAR in SQL). In the
following example, our Author field is casted as a variable character object with maximum characters of 50. This is
especially useful if you know how long your source data needs to be.

	:::php
	class StaffPage extends Page {
	
	   static $db = array(
	      'Author' => 'Varchar(50)'
	   );
	
	}
	class StaffPage_Controller extends Page_Controller {
	
	}


See [datamodel](/topics/datamodel) for a more detailed explanation on adding database fields, and how the SilverStripe data
model works.

## Adding formfields and tabs

See [form](/topics/forms) and [tutorial:2-extending-a-basic-site](/tutorials/2-extending-a-basic-site)

## Removing inherited form fields and tabs

### removeFieldFromTab()

Overloading `getCMSFields()` you can call `removeFieldFromTab()` on a `[api:FieldSet]` object. For example, if you don't
want the MenuTitle field to show on your page, which is inherited from `[api:SiteTree]`.

	:::php
	class StaffPage extends Page {
	
	   function getCMSFields() {
	      $fields = parent::getCMSFields();
	      $fields->removeFieldFromTab('Root.Content.Main', 'MenuTitle');
	      return $fields;
	   }
	
	}
	class StaffPage_Controller extends Page_Controller {
	
	}



### removeByName()
 `removeByName()` for normal form fields is useful for breaking inheritance where you know a field in your form isn't
required on a certain page-type.

	:::php
	class MyForm extends Form {
	
	   function __construct($controller, $name) {
	      // add a default FieldSet of form fields
	      $member = singleton('Member');
	
	      $fields = $member->formFields();
	
	      // We don't want the Country field from our default set of fields, so we remove it.
	      $fields->removeByName('Country');
	
	      $actions = new FieldSet(
	         new FormAction('submit', 'Submit')
	      );
	
	      parent::__construct($controller, $name, $fields, $actions);
	   }
	
	}

This will also work if you want to remove a whole tab e.g. $fields->removeByName('Metadata'); will remove the whole
Metadata tab.

For more information on forms, see [form](/topics/forms), [tutorial:2-extending-a-basic-site](/tutorials/2-extending-a-basic-site)
and [tutorial:3-forms](/tutorials/3-forms).

## Creating a new page:

	:::php
	$page = new Page();
	$page->ParentID = 18; //if you want it to be a child of a certain other page...
	$page->Title = "Crazy page"; 
	$page->MetaTitle = "madness";
	$page->PageTitle = "Funny"; 
	$page->writeToStage('Stage'); 
	$page->publish('Stage', 'Live');


## Updating a page:

	:::php
	$page = DataObject::get_one("Page", "ParentID = 18");
	$page->Title = "More Serious";
	$page->writeToStage('Stage');
	$page->Publish('Stage', 'Live');
	$page->Status = "Published";


## Deleting pages:

	:::php
	$pageID = $page->ID;
	$stageRecord = Versioned::get_one_by_stage('SiteTree', 'Stage', "SiteTree.ID = $pageID");
	if ($stageRecord) $stageRecord->delete();
	$liveRecord = Versioned::get_one_by_stage('SiteTree', 'Live', "SiteTree_Live.ID = $pageID");
	if ($liveRecord) $liveRecord->delete();
	