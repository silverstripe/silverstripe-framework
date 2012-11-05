# Installing and Upgrading with Composer

<div markdown='1' style="float: right; margin-left: 20px">
![](../_images/composer.png)
</div>

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

Adding modules 

Installing a module can be done with the following command

	php composer.phar require silverstripe/forum:*

This command has two parts.  First is `silverstripe/forum`. This is the name of the package.  You can find other packages with the following command:

	php composer.phar search silverstripe

This will return a list of package names of the forum `vendor/package`.  If you prefer, you can search for pacakges on [packagist.org](https://packagist.org/search/?q=silverstripe).

The second part, `*`, is a version string.  `*` is a good default: it will give you the latest version that works with the other modules you have installed.  Alternatively, you can specificy a specific version, or a constraint such as `>=3.0`.

## Manually editing composer.json

To remove dependencies, or if you prefer seeing all your dependencies in a text file, you can edit the composer.json file.  By default, it will look like this:

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
	
## Preparing your project for working on SilverStripe

So you want to contribute to SilverStripe? Fantastic! There are a couple modules that are helpful

 * The `compass` module will regenerate CSS if you update the SCSS files
 * The `docsviewer` module will let you preview changes to the project documentation

By default, these modules aren't installed, but you can install them with a special version of composer's update command:

	php composer.phar update --dev

## Creating a 'composer' binary

Composer is designed to be portable and not require installation in special locations of your computer.  This is
useful in certain circumstances, but sometimes it's helpful simply to have composer installed in the path of your workstation.

To do this, we can make the composer download an executable script.  Go to a directory in your path that you can write to.  I have `~/bin` set up for this purpose.  You could also go to `/usr/bin/` and log in as root.

	cd ~/bin

Then download composer.phar to this directory and create a 1 line binary file

	curl -s https://getcomposer.org/installer | php
	mv composer.phar composer
	chmod +x composer

Now check that it works:

	composer help
	composer list

In other words, in any of the commands above, replace `php composer.phar` with `composer`.