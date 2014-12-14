title: Modules
summary: Extend core functionality with modules.

# Modules

SilverStripe is designed to be a modular application system - even the CMS is simply a module that plugs into the core
framework.

A module is a collection of classes, templates, and other resources that is loaded into a top-level directory such as 
the `framework`, `cms` or `mysite` folders. The only thing that identifies a folder as a SilverStripe module is the 
existence of a `_config` directory or `_config.php` at the top level of the directory.

	mysite/
	|
	+-- _config/
	+-- code/
	+-- ..
	|
	my_custom_module/
	|
	+-- _config/
	+-- ...

SilverStripe will automatically include any PHP classes and templates from within your module when you next flush your
cache.

<div class="info" markdown="1"> 
In a default SilverStripe installation, even resources in `framework` and `mysite` are treated in exactly the same as 
every other module. Order of priority is usually alphabetical unless stated.
</div>

Creating a module is a good way to re-use abstract code and templates across multiple projects. SilverStripe already 
has certain modules included, for example the `cms` module and core functionality such as commenting and spam protection
are also abstracted into modules allowing developers the freedom to choose what they want.


## Finding Modules

* [Official module list on silverstripe.org](http://addons.silverstripe.org/)
* [Packagist.org "silverstripe" tag](https://packagist.org/search/?tags=silverstripe)
* [Github.com "silverstripe" search](https://github.com/search?q=silverstripe&ref=commandbar)

## Installation

Modules should exist in the root folder of your SilverStripe installation.

<div class="info" markdown="1">
The root directory is the one containing the *framework* and *mysite* subdirectories. If your site is installed under
`/Users/sam.minnee/Sites/website/` your modules will go in the `/Users/sam.minnee/Sites/website/` directory.
</div>

<div class="notice" markdown="1">
After you add or remove modules make sure you rebuild the database by going to http://yoursite.com/dev/build?flush=1
</div>

### From Composer

Our preferred way to manage module dependencies is through the [Composer](http://getcomposer.org) package manager. It 
enables you to install modules from specific versions, checking for compatibilities between modules and even allowing 
to track development branches of them. To install modules using this method, you will first need to setup SilverStripe
with [Composer](../../getting_started/composer).

Each module has a unique identifier, consisting of a vendor prefix and name. For example, the "blog" module has the 
identifier `silverstripe/blog` as it is published by *silverstripe*. To install, use the following command executed in 
the root folder:
	
	:::bash
	composer require "silverstripe/blog" "*@stable"

This will fetch the latest compatible stable version of the module. To install a specific version of the module give the
tag name.

	:::bash
	composer require "silverstripe/blog" "1.1.0"

<div class="info" markdown="1">
To lock down to a specific version, branch or commit, read up on 
[Composer "lock" files](http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file).
</div>

## From an Archive Download

<div class="alert" markdown="1">
Some modules might not work at all with this approach since they rely on the 
Composer [autoloader](http://getcomposer.org/doc/01-basic-usage.md#autoloading), additional modules or post-install 
hooks, so we recommend using Composer.
</div>

Alternatively, you can download the archive file from the [modules page](http://www.silverstripe.org/modules) and 
extract it to the root folder mentioned above.

<div class="notice" markdown="1">
The main folder extracted from the archive might contain the version number or additional "container" folders above the 
actual module codebase. You need to make sure the folder name is the correct name of the module (e.g. "blog/" rather 
than "silverstripe-blog/"). This folder should contain a `_config/` directory. While the module might register and 
operate in other structures, paths to static files such as CSS or JavaScript won't work.
</div>

## Publishing your own SilverStripe module

See the [How to Publish a SilverStripe Module](how_tos/publish_a_module) for details on how to publish your SilverStripe
modules with the community


## Related

* [How to Publish a SilverStripe Module](how_tos/publish_a_module)
