# LeftAndMain

## Introduction

LeftAndMain is the base class of all the admin area controllers.  

## Best Practices

### Refreshing

Please use LeftAndMain::ForceReload to reload the whole form-area after an Ajax-Request. If you just need to refresh
parts of the form, please use javascript-replacement in the response of the original Ajax-Request. Consider using
`[api:Form]` for  compiling Ajax-Responses and automatic detection of Ajax/Non-Ajax-Calls.

### Custom Access Checking

You can customize access control in `[api:LeftAndMain]`.

	:::php
	// mysite/_config.php
	LeftAndMain::add_extension('MyLeftAndMain');
	
	// MyLeftAndMain.php
	class MyLeftAndMain extends Extension {
	  function augumentInit() {
	    // add custom requirements etc.
	  }
	  function alternateAccessCheck() {
	    // custom permission checks, e.g. to check for an SSL-connection, or an LDAP-group-membership
	  }
	}


## Subclassing

There are a few steps in creating a subclass of LeftAndMain.

### MyAdmin.php

The PHP file defining your new subclass is the first step in the process.  This provides a good starting point:

	:::php
	class MyAdmin extends LeftAndMain {
	
		static $url_segment = 'myadmin';
	
		static $url_rule = '$Action/$ID';
	
		static $menu_title = 'My Admin';
	
		static $menu_priority = 60;
	
		/**
	
		 * Initialisation method called before accessing any functionality that BulkLoaderAdmin has to offer
		 */
		public function init() {
			Requirements::javascript('cms/javascript/MyAdmin.js');
			
			parent::init();
		}
	
		/**
	
		 * Form that will be shown when we open one of the items
		 */	 
		public function getEditForm($id = null) {
			return new Form($this, "EditForm",
				new FieldSet(
					new ReadonlyField('id #',$id)
				),
				new FieldSet(
					new FormAction('go')
				)
			);
		}
	}


### Templates

Next, create templates, (classname)_left.ss and (classname)_right.ss.  Again, here are a couple of starting points:

 * On the left, we're using the tree as a way of providing navigation.  The left and side could be replaced with
anything but LeftAndMain has built-in support for trees.
 * On the right, we have the skeleton that the form will be loaded into.

MyAdmin_left.ss

	:::ss
	<div class="title"><div>Functions</div></div>
	
	<div id="treepanes">
	<div id="sitetree_holder" style="overflow:auto">
		<% if Items %>
			<ul id="sitetree" class="tree unformatted">
			<li id="$ID" class="root Root"><a>Items</a>
				<ul>
				<% control Items %>
					<li id="record-$class">
					<a href="admin/my/show/$ID">$Title</a>
					</li>
				<% end_control %>
				</ul>
			</li>
			</ul>
		<% end_if %>
	</div>
	</div>


MyAdmin_right.ss

	:::ss
	<div class="title"><div>My admin</div></div>
	
	<% if EditForm %>
		$EditForm
	<% else %>
		<form id="Form_EditForm" action="admin/my?executeForm=EditForm" method="post" enctype="multipart/form-data">
			<p>Welcome to my $ApplicationName admin section.  Please choose something from the left.</p>
		</form>
	<% end_if %>
	
	<p id="statusMessage" style="visibility:hidden"></p>



### Customising the main menu

*Minimum Requirement: Silverstripe 2.3*

The static variable $url_segment determines the sub url of the controller.
The static variable $url_rule has the url format for the actions performed by the class.
The static variable $menu_title is the title of the administration panel in the menu.
The static variable $menu_priority tells the CMS where to put the menu item relative to other panels.

For example:

	:::php
	static $url_segment = 'myadmin';
	static $url_rule = '$Action/$ID';
	static $menu_title = 'My Admin';
	static $menu_priority = 60;


See also `[api:CMSMenu]`

### Translatable Menu Titles

Override the function getMenuTitle() to create a translated menu title name. Eg:

	:::php
	public function getMenuTitle() {
	   return _t('LeftAndMain.MYADMIN', 'My Admin', PR_HIGH, 'Menu title');



## 'onload' javascript in the CMS	{#onload-javascript}


You can have custom scripting called when a Page is loaded by clicking on the Site Content Tree.
This can be used to set up event handlers, or populate dropdowns, etc.
You could insert this code using Requirements from a custom page class.

	:::js
	Behaviour.register({
		'#Form_EditForm' : {
			initialize : function() {
				this.observeMethod('PageLoaded', this.adminPageHandler);
				this.adminPageHandler();
			},
			adminPageHandler : function() {
				// Place your custom code here.
			}
		}
	});

See [Javascript in the CMS](/topics/javascript#javascript-cms)


## Related

*  `[api:CMSMain]`
*  `[api:AssetAdmin]`
*  `[api:SecurityAdmin]`
*  `[api:ModelAdmin]` 

## TODO

*  Explain how to build the javascript file
*  Explain how the ajax button handlers work
*  Explain how to create little pop-up dialogs
