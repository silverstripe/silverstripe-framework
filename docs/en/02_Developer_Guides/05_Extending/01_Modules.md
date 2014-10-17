# Modules

SilverStripe is designed to be a modular application system - even the CMS is simply a module that plugs into it.  

A module is, quite simply, a collection of classes, templates, and other resources that is loaded into a top-level
directory.  In a default SilverStripe download, even resources in 'framework' and 'mysite' are treated in exactly the
same as every other module.

SilverStripe's `[api:ManifestBuilder]` will find any class, css or template files anywhere under the site's main
directory.  The `_config.php` file in the module directory as well as the [_config/*.yml files](/topics/configuration)
can be used to define director rules, add
extensions, etc.  So, by unpacking a module into site's main directory and viewing the site with
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
*  **Widgets:** Small pieces of functionality such as showing the latest Comments or Flickr Photos. Since SilverStripe 3.0, they have been moved into a standalone module at [github.com/silverstripe/silverstripe-widgets](https://github.com/silverstripe/silverstripe-widgets).
*  **Developer Tools:** A module can provide a number of classes or resource files that do nothing by themselves, but
instead make it easier for developers to build other applications. 

## Finding Modules

* [Official module list on silverstripe.org](http://addons.silverstripe.org/)
* [Packagist.org "silverstripe" tag](https://packagist.org/search/?tags=silverstripe)
* [Github.com "silverstripe" search](https://github.com/search?q=silverstripe&ref=commandbar)

## Installation

Modules should exist in the root folder of your SilverStripe installation
(the directory containing the *framework* and *cms* subdirectories).

The following article explains the generic installation of a module. Individual modules have their own requirements such
as creating folders or configuring API keys. For information about installing or configuring a specific module see the
modules *README* file. Modules should adhere to the [directory-structure](/topics/directory-structure)
guidelines.

### From a Composer Package

Our preferred way to manage module dependencies is through the [Composer][http://getcomposer.org]
package manager. It enables you to install modules from specific versions, checking for
compatibilities between modules and even allowing to track development branches of them.

After [installing Composer](/installation/composer) itself, 
you can run a simple command to install a module.
Each module has a unique identifier, consisting of a vendor prefix and name.
For example, the popular "blog" module has the identifier `silverstripe/blog`,
and would be installed with the following command executed in the root folder:

	composer require silverstripe/blog:*@stable

This will fetch the latest compatible stable version. Every time you run
`composer update` afterwards, Composer will check for a new stable version.
To lock down to a specific version, branch or commit, read up on 
[Composer "lock" files](http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file).
You can also add modules by editing the "require" section of the `composer.json` file.

To find modules and their identifiers, search for them on [packagist.org](http://packagist.org).

<div class="notice" markdown="1">
Older releases (<3.0.3, <2.4.9) don't come with a `composer.json` file in your root folder,
which is required for its operation. In this case, we recommend upgrading to a newer release.
</div>

### From an Archive Download

Alternatively, you can download the archive file from the 
[modules page](http://www.silverstripe.org/modules) 
and extract it to the root folder mentioned above.
Github also provides archive downloads which are generated automatically for every tag/version.

<div class="notice" markdown="1">
The main folder extracted from the archive
might contain the version number or additional "container" folders above the actual module
codebase. You need to make sure the folder name is the correct name of the module
(e.g. "blog/" rather than "silverstripe-blog/"). This folder should contain a `_config/` directory.
While the module might register and operate in other structures,
paths to static files such as CSS or JavaScript won't work.
</div>

<div class="warning" markdown="1">
Some modules might not work at all with this approach since they rely on the
Composer [autoloader](http://getcomposer.org/doc/01-basic-usage.md#autoloading)
or post-install hooks, so we recommend using Composer.
</div>

### Git Submodules and Subversion Externals

Git and Subversion provide their own facilities for managing dependent repositories.
This is essentially a variation of the "Archive Download" approach,
and comes with the same caveats.


## Configuration as a module marker

Configuration files also have a secondary sub-role. Modules are identified by the `[api:ManifestBuilder]` by the
presence of a `_config/` directory (or a `_config.php` file) as a top level item in the module directory.

Although your module may choose not to set any configuration, it must still have a _config directory to be recognised
as a module by the `[api:ManifestBuilder]`, which is required for features such as autoloading of classes and template
detection to work.

## Related

* [Modules Development](/topics/module-development)
