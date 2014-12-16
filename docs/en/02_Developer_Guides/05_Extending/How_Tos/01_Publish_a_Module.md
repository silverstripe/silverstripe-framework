title: How to Publish a SilverStripe module

# How to Publish a SilverStripe module.

If you wish to submit your module to our public directory, you take responsibility for a certain level of code quality, 
adherence to conventions, writing documentation, and releasing updates. 

SilverStripe uses [Composer](../../getting_started/composer/) to manage module releases and dependencies between 
modules. If you plan on releasing your module to the public, ensure that you provide a `composer.json` file in the root 
of your module containing the meta-data about your module.

For more information about what your `composer.json` file should include, consult the 
[Composer Documentation](http://getcomposer.org/doc/01-basic-usage.md).

A basic usage of a module for 3.1 that requires the CMS would look similar to
this:

**mycustommodule/composer.json**
	:::js
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


Once your module is published online with a service like Github.com or Bitbucket.com, submit the repository to 
[Packagist](https://packagist.org/) to have the module accessible to developers. It'll automatically get picked
up by [addons.silverstripe.org](http://addons.silverstripe.org/) website.

## Releasing versions

Over time you may have to release new versions of your module to continue to work with newer versions of SilverStripe. 
By using Composer, this is made easy for developers by allowing them to specify what version they want to use. Each
version of your module should be a separate branch in your version control and each branch should have a `composer.json` 
file explicitly defining what versions of SilverStripe you support.

Say you have a module which supports SilverStripe 3.0. A new release of this module takes advantage of new features
in SilverStripe 3.1. In this case, you would create a new branch for the 3.0 compatible code base of your module. This 
allows you to continue fixing bugs on this older release branch.

<div class="info" markdown="1">
As a convention, the `master` branch of your module should always work with the `master` branch of SilverStripe.
</div>

Other branches should be created on your module as needed if they're required to support specific SilverStripe releases.

You can have an overlap in supported versions, e.g two branches in your module both support SilverStripe 3.1. In this 
case, you should explain the differences in your `README.md` file.

Here's some common values for your `require` section
(see [getcomposer.org](http://getcomposer.org/doc/01-basic-usage.md#package-versions) for details):

 * `3.0.*`: Version `3.0`, including `3.0.1`, `3.0.2` etc, excluding `3.1`
 * `~3.0`: Version `3.0` or higher, including `3.0.1` and `3.1` etc, excluding `4.0`
 * `~3.0,<3.2`: Version `3.0` or higher, up until `3.2`, which is excluded
 * `~3.0,>3.0.4`: Version `3.0` or higher, starting with `3.0.4`