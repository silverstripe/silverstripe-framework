# Installing and Upgrading with Composer

<div markdown='1' style="float: right; margin-left: 20px">
![](../_images/composer.png)
</div>

Composer is a package management tool for PHP that lets you install and upgrade SilverStripe and its modules.  Although installing Composer is one extra step, it will give you much more flexibility than just downloading the file from silverstripe.org. This is our recommended way of downloading SilverStripe and managing your code.

For more information about Composer, visit [its website](http://getcomposer.org/).

# Basic usage

## Installing composer

To install Composer, run the following commands from your command-line.

	# Download composer.phar
	curl -s https://getcomposer.org/installer | php

	# Move to your path
	sudo mv composer.phar /usr/local/bin/composer

Or [download composer.phar](http://getcomposer.org/composer.phar) manually, and rename `composer.phar` as `composer`, and put it in your path.  On Windows, you should call the file `composer.bat`.

You can then run Composer commands by calling `composer`.  For example:

	composer help

<div class="hint" markdown="1">
It is also possible to keep `composer.phar` out of your path, for example, to put it in your project root.  Every command would then start with `php composer.phar` instead of `composer`.  This is handy if need to keep your installation isolated from the rest of your computer's set-up, but we recommend putting composer into the path for most people.
</div>	

#### Updating composer

If you already have composer installed you can update it by running:

	sudo composer self-update
	
Composer updates regularly, so you should run this command fairly often. These instructions assume you are running the latest version.

## Create a new site

Composer can create a new site for you, using the installer as a template.  To do so, run this:

	composer create-project silverstripe/installer ./my/website/folder

`./my/website/folder` should be the root directory where your site will live.  
For example, on OS X, you might use a subdirectory of `~/Sites`.
As long as your web server is up and running, this will get all the code that you need. 
Now visit the site in your web browser, and the installation process will be completed.

By default composer will download the latest stable version. You can also specify
a version to download that version explicitly, i.e. this will download 3.0.3:

	composer create-project silverstripe/installer ./my/website/folder 3.0.3
	
When `create-project` is used with a release version like above,
it will try to get the code from archives instead of creating
git repositories. If you're planning to contribute to SilverStripe,
see [Using development versions](#using-development-versions).

## Adding modules to your project

Composer isn't only used to download SilverStripe CMS, it can also be used to manage all SilverStripe modules.  Installing a module can be done with the following command:

	composer require silverstripe/forum:*

This command has two parts.  First is `silverstripe/forum`. This is the name of the package.  You can find other packages with the following command:

	composer search silverstripe

This will return a list of package names of the forum `vendor/package`.  If you prefer, you can search for pacakges on [packagist.org](https://packagist.org/search/?q=silverstripe).

The second part after the colon, `*`, is a version string.  `*` is a good default: it will give you the latest version that works with the other modules you have installed.  Alternatively, you can specificy a specific version, or a constraint such as `>=3.0`.  For more information, read the [Composer documentation](http://getcomposer.org/doc/01-basic-usage.md#the-require-key).

<div class="warning" markdown="1">
`master` is not a legal version string - it's a branch name.  These are different things.  The version string that would get you the branch is `dev-master`.  The version string that would get you a numeric branch is a little different.  The version string for the `3.0` branch is `3.0.x-dev`.  But, frankly, maybe you should just use `*`.
</div>

## Updating dependencies

Except for the control code of the Voyager space probe, every piece of code in the universe gets updated from time to time.  SilverStripe modules are no exception.

To get the latest updates of the modules in your project, run this command:

	composer update

Updates to the required modules will be installed, and the `composer.lock` file will get updated with the specific commits of each of those.

## Deploying projects with Composer

When deploying projects with composer, you could just push the code and run `composer update`.  However, this is risky.  In particular, if you were referencing development dependencies and a change was made between your testing and your depoyment to production, you would end up deploying untested code.  Not cool!

The `composer.lock` file helps with this.  It references the specific commits that have been checked out, rather than the version string.  You can run `composer install` to install dependencies from this rather than `composer.json`.

So, your deployment process, as it relates to Composer, should be as follows:

 * Run `composer update` on your development version before you start whatever testing you have planned.  Perform all the necessary testing.
 * Check `composer.lock` into your repository.
 * Deploy your project code base, using the deployment tool of your choice.
 * Run `composer install` on your production version.

# Setting up an environment for contributing to SilverStripe {#contributing}

So you want to contribute to SilverStripe? Fantastic! You can do this with composer too.
You have to tell composer three things in order to be able to do this:

  - Keep the full git repository information
  - Include dependancies marked as "developer" requirements
  - Use the development version, not the latest stable version

The first two steps are done as part of the initial create project using additional arguments. For instance:

	composer create-project --keep-vcs --dev silverstripe/installer ./my/website/folder 3.0.x-dev

The process will take a bit longer, since all modules are checked out as full git repositories which you can work on.

The `--keep-vcs` flag will make sure you have access to the git history of the installer and the requirements

The `--dev` flag will add a couple modules which are useful for SilverStripe development:

 * The `docsviewer` module will let you preview changes to the project documentation
 * The `buildtools` module which adds [phing](http://phing.info) tasks for creating SilverStripe releases

Note that you can also include those into an existing project by running `composer update --dev`.
Please read the ["Contributing Code"](/misc/contributing/code) documentation to find out how to
create forks and send pull requests.

# Advanced usage

## Manually editing composer.json

To remove dependencies, or if you prefer seeing all your dependencies in a text file, you can edit the `composer.json` file.  It will appear in your project root, and by default, it will look something like this:

	{
		"name": "silverstripe/installer",
		"description": "The SilverStripe Framework Installer",
		"require": {
			"php": ">=5.3.2",
			"silverstripe/cms": "3.0.*",
			"silverstripe/framework": "3.0.*",
			"silverstripe-themes/simple": "*"
		},
		"require-dev": {
			"silverstripe/compass": "*",
			"silverstripe/docsviewer": "*"
		},
		"minimum-stability": "dev"
	}
	
To add modules, you should add more entries into the `"require"` section.  For example, we might add the blog and forum modules.  Be careful with the commas at the end of the lines!

Save your file, and then run the following command to refresh the installed packages:

	composer update

## Using development versions

Composer will by default download the latest stable version of silverstripe/installer.
The `composer.json` file that comes with silverstripe/installer may also explicitly state it requires the stable version of cms and framework - this is to ensure that when developers are getting started, running `composer update` won't upgrade their project to an unstable version

However it is relatively easy to tell composer to use development versions. Not only
is this required if you want to contribute back to the SilverStripe project, it also allows you to get fixes and API changes early.

This is a two step process. First you get composer to start a project based on
the latest unstable silverstripe/installer

	composer create-project silverstripe/installer ./my/website/folder master-dev

Or for the latest development version in the 3.0.x series

	composer create-project silverstripe/installer ./my/website/folder 3.0.x-dev

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

 		composer require silverstripe/advancedworkflow

Composer will scan all of the repositories you list, collect meta-data about the packages within them, and use them in favour of the packages listed on packagist.  To switch back to using the mainline version of the package, just remove your the `repositories` section from `composer.json` and run `composer update`.

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
			"silverstripe/cms": "3.0.2.1",
			"silverstripe/framework": "dev-myproj as 3.0.x-dev",
			"silverstripe-themes/simple": "*"
		},
		...
	}

What this means is that when the `myproj` branch is checked out into a project, this will satisfy any dependencies that 3.0.x-dev would meet.  So, if another module has `"silverstripe/framework": ">=3.0.0"` in its dependency list, it won't get a conflict.

Both the version and the alias are specified as Composer versions, not branch names.  For the relationship between branch/tag names and Composer vesrions, read [the relevant Composer documentation](http://getcomposer.org/doc/02-libraries.md#specifying-the-version).

This is not the only way to set things up in Composer. For more information on this topic, read the ["Aliases" chapter of the Composer documentation](http://getcomposer.org/doc/articles/aliases.md).

