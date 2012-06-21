# Modules

SilverStripe is designed to be a modular application system - even the CMS is simply a module that plugs into it.  

A module is, quite simply, a collection of classes, templates, and other resources that is loaded into a top-level
directory.  In a default SilverStripe download, even resources in 'framework' and 'mysite' are treated in exactly the
same as every other module.

SilverStripe's `[api:ManifestBuilder]` will find any class, css or template files anywhere under the site's main
directory.  The _config.php file in the module directory can be used to define director rules, calls to
Object::useCustomClass(), and the like.  So, by unpacking a module into site's main directory and viewing the site with
?flush=1 on the end of the URL, all the module's new behaviour will be incorporated to your site:

*  You can create subclasses of base classes such as SiteTree to extend behaviour.
*  You can use Object::useCustomClass() to replace a built in class with a class of your own.
*  You can use [an extension](api:DataExtension) to extend or alter the behaviour of a built-in class without replacing
it.
*  You can provide additional director rules to define your own controller for particular URLs.

For more information on creating modules, see [module-development](/topics/module-development).

## Types of Modules

Because of the broad definition of modules, they can be created for a number of purposes:

*  **Applications:** A module can define a standalone application that may work out of the box, or may get customisation
from your mysite folder.  "cms" is an example of this.
*  **CMS Add-ons:** A module can define an extension to the CMS, usually by defining special page types with their own
templates and behaviour. "blog", "ecommerce", "forum", and "gallery" are examples of this.
*  **Blog Widgets:** A module can provide 1 or more blog-widget classes.  See [widgets](/topics/widgets) for more information.
*  **Developer Tools:** A module can provide a number of classes or resource files that do nothing by themselves, but
instead make it easier for developers to build other applications. 

## Finding Modules

*  [Official module list on silverstripe.org](http://silverstripe.org/modules)
*  [Subversion repository on open.silverstripe.org](http://open.silverstripe.org/browser/modules)
    

## Installation

Modules should exist in the root folder of your SilverStripe. The root folder being the one that contains the
*framework*, *cms* and other folders.

The following article explains the generic installation of a module. Individual modules have their own requirements such
as creating folders or configuring API keys. For information about installing or configuring a specific module see the
modules *INSTALL* (or *README*) file. Modules should adhere to the [directory-structure](/topics/directory-structure)
guidelines.

### Download

To install a module you need to download the tar.gz file from the [modules page](http://www.silverstripe.org/modules) and extract this tar.gz to the root folder mentioned
above.

Note some times the folders extracted from the tar.gz contain the version number or some other folders. You need to make
sure the folder name is the correct name of the module.

### Subversion

#### Option 1: Checkout

	cd ~/Sites/yourSilverStripeProject/
	svn co http://svn.silverstripe.com/open/modules/modulename/trunk modulename/


Note: Some modules are stored in subfolders.  If you want to use a module that is in a subfolder, such as widgets, put
an _ between the subfolder name and the module name, like this:

	cd /your/website/root
	svn co http://svn.silverstripe.com/open/modules/widgets/twitter/trunk widgets_twitter



#### Option 2: Add to svn:externals

	cd ~/Sites/yourSilverStripeProject/
	svn propedit svn:externals .


In the editor add the following line (lines if you want multiple)

	modulename/ http://svn.silverstripe.com/open/modules/modulename/trunk


Exit the editor and then run 

	svn up


**Useful Links:**

*  [Modules](/topics/module-developement)
*  [Module Release Process](/misc/module-release-process)
