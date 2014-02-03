# Module Development

## Introduction

Creating a module is a good way to re-use abstract code and templates across
multiple projects. SilverStripe already has certain modules included, for
example "framework" and "cms". These two modules are the core functionality and
templates for any initial installation.

If you want to add generic functionality that isn't specific to your
project, like a forum, an ecommerce package or a blog you can do it like this:

1.  Create another directory at the root level (same level as "framework"
    and "cms"). This will contain all your module files.
2.  The module directory must contain a `_config` sub-directory, or a
    `_config.php` file to be recognised.
3.  Inside your module directory, follow our
    [directory structure guidelines](/topics/directory-structure#module_structure)

Once this is done, SilverStripe will automatically include any PHP classes and
templates from within your module.

## Tips

Try to keep your module as generic as possible - for example if you're making a
forum module, your members section shouldn't contain fields like 'Games You
Play' or 'Your LiveJournal Name' - if people want to add these fields they can
sub-class your class, or extend the fields on to it.

If you're using [api:Requirements] to include generic support files for your project
like CSS or Javascript, and want to override these files to be more specific in
your project, the following code is an example of how to do so using the init()
function on your module controller classes:

	:::php
	class Forum_Controller extends Page_Controller {
	
	   public function init() {
	      if(Director::fileExists(project() . "/css/forum.css")) {
	         Requirements::css(project() . "/css/forum.css");
	      } else {
	         Requirements::css("forum/css/forum.css");
	      }
	      parent::init();	
	   }
	
	}


This will use `<projectname>/css/forum.css` if it exists, otherwise it falls
back to using `forum/css/forum.css`.

## Conventions

### Configuration

SilverStripe has a comprehensive [Configuration](/topics/configuration) system
built on YAML which allows developers to set configuration values in core
classes.

If your module allows developers to customize specific values (for example API
key values) use the existing configuration system for your data.

	:::php
	// use this in your module code
	$varible = Config::inst()->get('ModuleName', 'SomeValue');

Then developers can set that value in their own configuration file. As a module
author, you can set the default configuration values.

	// yourmodule/_config/module.yml
	---
	Name: modulename
	---
	ModuleName:
	  SomeValue: 10

But by using the Config system, developers can alter the value for their
application without editing your code.

	// mysite/_config/module_customizations.yml
	---
	Name: modulecustomizations
	After: "#modulename"
	---
	ModuleName:
	  SomeValue: 10

If you want to make the configuration value user editable in the backend CMS,
provide an extension to [SiteConfig](/reference/siteconfig).

## Publication

If you wish to submit your module to our public directory, you take
responsibility for a certain level of code quality, adherence to conventions,
writing documentation, and releasing updates. See
[contributing](/misc/contributing). All modules should be published
on [addons.silverstripe.org](http://addons.silverstripe.org) to make them
discoverable by others.

### Composer and Packagist

SilverStripe uses [Composer](/installation/composer/) to manage module releases
and dependencies between modules. If you plan on releasing your module to the
public, ensure that you provide a `composer.json` file in the root of your
module containing the meta-data about your module.

For more information about what your `composer.json` file should include,
consult the [Composer Documentation](http://getcomposer.org/doc/01-basic-usage.md).

A basic usage of a module for 3.1 that requires the CMS would look similar to
this:

	{
	  "name": "your-vendor-name/module-name",
	  "description": "One-liner describing your module",
	  "type": "silverstripe-module",
	  "homepage": "http://github.com/your-vendor-name/module-name",
	  "keywords": ["silverstripe", "some-tag", "some-other-tag"],
	  "license": "BSD-3-Clause",
	  "authors": [
	    {"name": "Your Name","email": "your@email.com"}
	  ],
	  "support": {
	    "issues": "http://github.com/your-vendor-name/module-name/issues"
	  },
	  "require": {
	    "silverstripe/cms": "~3.1",
	    "silverstripe/framework": "~3.1"
	  },
	  "extra": {
	    "installer-name": "module-name",
	    "screenshots": [
	      "relative/path/screenshot1.png",
	      "http://myhost.com/screenshot2.png"
	    ]
	  }
	}


Once your module is released, submit it to [Packagist](https://packagist.org/)
to have the module accessible to developers. It'll automatically get picked
up by [addons.silverstripe.org](http://addons.silverstripe.org/).

### Versioning

Over time you may have to release new versions of your module to continue to
work with newer versions of SilverStripe. By using Composer, this is made easy
for developers by allowing them to specify what version they want to use. Each
version of your module should be a separate branch in your version control and
each branch should have a `composer.json` file explicitly defining what versions
of SilverStripe you support.

Say you have a module which supports SilverStripe 3.0.
A new release of this module takes advantage of new features
in SilverStripe 3.1. In this case, you would create a new branch
for the 3.0 compatible codebase of your module.
This allows you to continue fixing bugs on this older release branch.

As a convention, the `master` or `trunk` branch of your
module should always work with the `master` branch of SilverStripe.
Other branches should be created on your module as needed if they're
required to support specific SilverStripe releases.

You can have an overlap in supported versions,
e.g two branches in your module both support SilverStripe 3.1.
In this case, you should explain the differences in your `README.md` file.

Here's some common values for your `require` section
(see [getcomposer.org](http://getcomposer.org/doc/01-basic-usage.md#package-versions) for details):

 * `3.0.*`: Version `3.0`, including `3.0.1`, `3.0.2` etc, excluding `3.1`
 * `~3.0`: Version `3.0` or higher, including `3.0.1` and `3.1` etc, excluding `4.0`
 * `~3.0,<3.2`: Version `3.0` or higher, up until `3.2`, which is excluded
 * `~3.0,>3.0.4`: Version `3.0` or higher, starting with `3.0.4`

## Reference

### How To:

*  [How to customize the CMS Menu](/howto/customize-cms-menu)
*  [How to extend the CMS interface](/howto/extend-cms-interface)

### Reference:

Provide custom functionality for the developer via:

*  [DataExtension](/reference/dataextension)
*  [SiteConfig](/reference/siteconfig)
*  [Page types](/topics/page-types)

Follow SilverStripe best practice:

*  [Partial Caching](/reference/partial-caching)
*  [Injector](/reference/injector)

## Useful Links

*  [Introduction to Composer](http://getcomposer.org/doc/00-intro.md)
*  [Modules](modules)
*  [Directory structure guidelines](/topics/directory-structure#module_structure)
*  [Debugging methods](/topics/debugging)
*  [URL Variable Tools](/reference/urlvariabletools) - Lists a number of page options, rendering tools or special URL variables that you can use to debug your SilverStripe applications
