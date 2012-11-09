# Installing and Upgrading with Composer

<div markdown='1' style="float: right; margin-left: 20px">
![](../_images/composer.png)
</div>

Composer is a package management tool for PHP that lets you install and upgrade SilverStripe and its modules.  Although installing Composer is one extra step, it will give you much more flexibility than just downloading the file from silverstripe.org. This is our recommended way of downloading SilverStripe and managing your code.

For more information about Composer, visit [its website](http://getcomposer.org/).

# Basic usage

## Installing composer

To install Composer, run the following command from your command-line.

	curl -s https://getcomposer.org/installer | php

Or [download composer.phar](http://getcomposer.org/composer.phar) manually.

You can then run Composer commands by calling `php composer.phar`.  For example:

	php composer.phar help
	
## Create a new site

Composer can create a new site for you, using the installer as a template.  To do so, run this:

	php composer.phar create-project silverstripe/installer ./my/website/folder 3.0.2.1

`./my/website/folder` should be the root directory where your site will live.  For example, on OS X, you might use a subdirectory of `~/Sites`.

As long as your web server is up and running, this will get all the code that you need.  Now visit the site in your web
browser, and the installation process will be completed.

**Note:** The version, 3.0.2.1, is the first version that we've released that has Composer support.  Shortly, this will be replaced with 3.0.3.  Note that [a planned improvement to Composer](https://github.com/composer/composer/issues/957) would make it choose the latest stable version by default; once this has happened, we will update this document.

## Adding modules to your project

Composer isn't only used to download SilverStripe CMS: it can also be used to manage all the modules.  Installing a module can be done with the following command:

	php composer.phar require silverstripe/forum:*

This command has two parts.  First is `silverstripe/forum`. This is the name of the package.  You can find other packages with the following command:

	php composer.phar search silverstripe

This will return a list of package names of the forum `vendor/package`.  If you prefer, you can search for pacakges on [packagist.org](https://packagist.org/search/?q=silverstripe).

The second part, `*`, is a version string.  `*` is a good default: it will give you the latest version that works with the other modules you have installed.  Alternatively, you can specificy a specific version, or a constraint such as `>=3.0`.  For more information, read the [Composer documentation](http://getcomposer.org/doc/01-basic-usage.md#the-require-key).

# Advanced usage

## Manually editing composer.json

To remove dependencies, or if you prefer seeing all your dependencies in a text file, you can edit the `composer.json` file.  It will appear in your project root, and by default, it will look something like this:

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

Save your file, and then run the following command to refresh the installed packages:

	php composer.phar update

## Working with project forks and unreleased modules

By default, Composer will install modules listed on the packagist site.  There a few reasons that you might not
want to do this.  For example:

 * You may have your own fork of a module, either specific to a project, or because you are working on a pull request
 * You may have a module that hasn't been released to the public.

There are many ways that you can address this, but this is one that we recommend, because it minimises the changes you would need to make to switch to an official version in the future.

This is how you do it:

 * **Ensure that all of your fork repositories have correct composer.json files.** Set up the project forks as you would a distributed package.  If you have cloned a repository that already has a composer.json file, then there's nothing you need to do, but if not, you will need to create one yourself.

 * **List all your fork repositories in your project's composer.json files.**  You do this in a `repositories` section.  Set the `type` to `vcs`, and `url` to the URL of the repository.  The result will look something like this:

 		{
 			"name": "silverstripe/installer",
 			"description": "The SilverStripe Framework Installer",

 			"repositories": [
 				{
 				"type": "vcs",
 				"url": "git@github.com:sminnee/advancedworkflow.git"
 				}
 			],
 			...
 		}

 * **Install the module as you would normally.** Use the regular composer function - there are no special flags to use a fork. Your fork will be used in place of the package version.

 		php composer.phar require silverstripe/advancedworkflow

Composer will scan all of the repositories you list, collect meta-data about the packages within them, and use them in favour of the packages listed on packagist.  To switch back to using the mainline version of the package, just remove your the `repositories` section from `composer.json` and run `php composer.phar update`.

For more information, read the ["Repositories" chapter of the Composer documentation](http://getcomposer.org/doc/05-repositories.md).

### Forks and branch names

Generally, you should keep using the same pattern of branch names as the main repositories does. If your version is a fork of 3.0, then call the branch `3.0`, not `3.0-myproj` or `myproj`. Otherwise, the depenency resolution gets confused.

Sometimes, however, this isn't feasible.  For example, you might have a number of project forks stored in a single repository, such as your personal github fork of a project.  Or you might be testing/developing a feature branch.  Or it might just be confusing to other team members to call the branch of your modified version `3.0`.

In this case, you need to use Composer's aliasing feature to specify how you want the project branch to be treated, when it comes to dependency resolution.	

Open `composer.json`, and find the module's `require`.  Then put `as (core version name)` on the end.

	{
		...
		"require": {
			"php": ">=5.3.2",
			"silverstripe/cms": "3.0.3",
			"silverstripe/framework": "dev-myproj as 3.0.x-dev",
			"silverstripe-themes/simple": "*"
		},
		...
	}

What is means is that when the `myproj` branch is checked out into a project, this will satisfy any dependencies that 3.0.x-dev would meet.  So, if another module has `"silverstripe/framework": ">=3.0.0"` in its dependency list, it won't get a conflict.

Both the version and the alias are specified as Composer versions, not branch names.  For the relationship between branch/tag names and Composer vesrions, read [the relevant Composer documentation](http://getcomposer.org/doc/02-libraries.md#specifying-the-version).

This is not the only way to set things up in Composer. For more information on this topic, read the ["Aliases" chapter of the Composer documentation](http://getcomposer.org/doc/articles/aliases.md).

## Setting up an environment for working on SilverStripe

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

In any of the commands above, you can replace `php composer.phar` with `composer`.