<div markdown='1' style="float: right; margin-left: 20px">
![](../_images/composer.png)
</div>

# Installing and Upgrading SilverStipe with Composer

Composer is a package management tool for PHP that lets you install and upgrade SilverStripe and its modules.  Although installing Composer is one extra step, it will give you much more flexibility than just downloading the file from silverstripe.org. This is our recommended way of downloading SilverStripe and managing your code.

For more information about Composer, visit [its website](http://getcomposer.org/).

## Installing composer

To install Composer, run the following command from your command-line.

	curl -s https://getcomposer.org/installer | php

Or [download composer.phar](http://getcomposer.org/composer.phar) manually.

You can then run Composer commands by calling `php composer.phar`.  For example:

	php composer.phar help
	
## Create a site from the default installer template

Composer can create a new project for you, using the installer as a template.  To do so, run this:

	php composer.phar create-project silverstripe/installer ./my/website/folder 3.0.2.1

`./my/website/folder` should be the root directory where your site will live.  For example, on OS X, you might use a subdirectory of `~/Sites`.

As long as your web server is up and running, this will get all the code that you need.  Now visit the site in your web
browser, and the installation process will be completed.

**Note:** The version, 3.0.2.1, is the first version that we've released that has Composer support.  Shortly, this will be replaced with 3.0.3.  Note that [a planned improvement to Composer](https://github.com/composer/composer/issues/957) would make it choose the latest stable version by default; once this has happened, we will update this document.

## Adding modules to your project

Composer isn't only used to download SilverStripe CMS: it can also be used to manage all the modules.  In the root of your project, there will be a file called `composer.json`.  If you open it up, the contents will look something like this:

	{
	        "name": "silverstripe/installer",
	        "description": "The SilverStripe Framework Installer",
	        "require": {
	                "php": ">=5.3.2",
	                "silverstripe/cms": "3.0.3",
	                "silverstripe/framework": "3.0.3",
	                "silverstripe-themes/simple": "*"
	        },
	        "require-dev": {
	                "silverstripe/compass": "*",
	                "silverstripe/docsviewer": "*"
	        },
	}
	
	
To add modules, you should add more entries into the `"require"` section.  For example, we might add the blog and forum modules.  Be careful with the commas at the end of the lines!

	{
	        "name": "silverstripe/installer",
	        "description": "The SilverStripe Framework Installer",
	        "require": {
	                "php": ">=5.3.2",
	                "silverstripe/cms": "3.0.3",
	                "silverstripe/framework": "3.0.3",
	                "silverstripe-themes/simple": "*",
	
	                "silverstripe/blog": "*",
	                "silverstripe/forum": "*"
	        },
	        "require-dev": {
	                "silverstripe/compass": "*",
	                "silverstripe/docsviewer": "*"
	        },
	}

Save your file, and then run the following command:

	php composer.phar update
	
We currently use [Packagist](https://packagist.org/) to download manage modules, as this is Composer's default repository.  You can find other modules by searching for "SilverStripe" on Packagist.

## Preparing your project for working on SilverStripe

So you want to contribute to SilverStripe? Fantastic! There are a couple modules that are helpful

 * The `compass` module will regenerate CSS if you update the SCSS files
 * The `docsviewer` module will let you preview changes to the project documentation

By default, these modules aren't installed, but you can install them with a special version of composer's update command:

	php composer.phar update --dev
