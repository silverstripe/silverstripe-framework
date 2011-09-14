# Module Maintenance and Release Procedures

## Creating a module

One of the best ways that you can contribute to SilverStripe is by developing a module for SilverStripe.  
If you do, we would love to host your module and have you become an official module maintainer on our site. 
Please read our ["Contributing to SilverStripe"](http://silverstripe.org/contributing-to-silverstripe/) overview.

Once you have created a module, login at [silverstripe.org](http://silverstripe.org) and 
[submit your module](http://silverstripe.org/modules/manage/add)

It's very important to us that users of SilverStripe can come to expect a level of quality from the core product and any 
modules running on it. In order to provide this, we require certain things from module maintainers.

<div class="hint" markdown="1">
The following documentation describes aspects of subversion, you can read about similiar
strategies for git on a [free online book](http://progit.org/book).
</div>

### Principles

Strive for features you add to the CMS to be innovatively usable by a content editor rather than a web-developer.
Think Wordpress and Apple. Most modules should work by merely placing the code in your SilverStripe installation and
running /dev/build. Provide a default set of configuration options that are easily changed in `_config.php`
(for instance the `ecommerce` module works out of the box, and you can easily set up a payment provider), aiding a pleasant
user experience.

### Code

Each line of code you write should be version controlled, in version control systems like 
[subversion](http://subversion.tigris.org) or [Git](http://gitscm.com). There's lots of services that are freely
available for opensource projects, including wiki and bugtracker functionality 
(e.g. [Google Code for Subversion](http://code.google.com) or [Github for Git](http://github.com)).

* Add your module to [silverstripe.org/modules](http://silverstripe.org/modules) (and keep the version compatibility information current)
* Follow our [coding-conventions](coding-conventions)
* Write unit tests and functional tests covering code bundled with the module - see [testing-guide](/topics/testing)
* Ensure your code is [localizable](/topics/i18n)

### Maintenance

* Create releases (see ["Module Releases"](#module-releases) below)
* Ensure that your module is patched to always work with the latest SilverStripe release, and note these compatibilities on 
your modules page on silverstripe.org
* Be involved in our community 
    * Subscripe to our developer mailing list and be available to answer questions on the forum. 
    * Attend [irc:our weekly core discussions on IRC](irc/our weekly core discussions on IRC) as regularly as you can.
* Create an **issue tracker** so your users can file bugs and feature requests (see ["Feedback and Bugtracking"](module-release-process#feedback-and-bugtracking) below)
* Create a **roadmap** and **milestones** outlining future release planning

### Feedback and Bugtracking

Both Google Code and github.com provide their own bugtracker - we encourage you to use any built-in tools that come with
your version control hoster. Most Silverstripe-maintained modules have their bugtracker on 
[open.silverstripe.org](http://open.silverstripe.org).

Providing bugtracking is a major form of communicating with your users in an efficient way, and will provide a good overview
of outstanding work and the stability of your code to an interested user.

If the user community finds bugs that shouldn't be included in the next stable release, you will need to release another
release candidate.  If your release candidate is found to be stable, then you can create the stable release.

### Documentation

You should have both **developer documentation** and **user documentation**, and keep them updated with your releases.
See [Producing OSS: "Documentation"](http://producingoss.com/en/getting-started.html#documentation) and our 
[contributing guide](contributing#writing-documentation).

### README file

Each module should have a `README.md file` in the project root in 
[markdown format](http://daringfireball.net/projects/markdown/), roughly following this template:

	
	# <MODULENAME> Module
		
	## Maintainer Contact	
	
	 * <FULLNAME> (Nickname: <NICKNAME>, <EMAIL>)
	
	## Requirements
	
	 * <Specific SilverStripe version, PHP, MySQL, ...>
	
	## Documentation
	
	<Links to the wiki, blog posts, etc>
	
	## Installation Instructions
	
	<Step by step instructions>
	
	## Usage Overview
	
	<Highlevel usage, refer to wiki documentation for details>
	
	## Known issues
	
	<Popular issues, how to solve them, and links to tickets in the bugtracker>

### The docs/ folder ###

The `README.md` file might get a bit long for bigger modules, and you might want to break it up into multiple files
that you can link from the `README.md` file. Example:

	mymodule/
		README.md
		code/
		docs/
			installation.md
			tutorial.md
			howto-search-mymodule.md

The ["sapphiredocs" module](http://open.silverstripe.org/browser/modules/sapphiredocs/trunk) can be used
to list and render content inside a `docs/` folder (although it is not required, Markdown is designed
to be readable in plain text as well).

### What do you get?

In return for all your hard work in putting a high-quality module on the site, the SilverStripe project has the following 
options to support you:

*  Use of [trac](http://open.silverstripe.org) to keep your bugs and feature requests organised
*  Advertising of your module on the http://silverstripe.org/modules/ modules page once it has reached a beta stage and shown
to meet our requirements above.
*  We might showcase your module on our blog and/or newsletter, when it's first released and/or when a major version with
significant new features is released. We'll work with you to publicise it on other blogs too (it helps if you deliver 
screenshots and screencasts)
*  More influence in suggesting changes to the core product
*  Kudos on [Ohloh](http://www.ohloh.net/projects/5034?p=SilverStripe+CMS)

## Releasing a Module

If you are a module maintaienr, you will be responsible for creating new releases of the module.
Releases are important for each codebase to provide stability for its users,
and clearly communicate dependencies/requirements.

### Release Branches

In order to ensure stability, the first thing we do when making a release is to create a release branch.  This branch
will exist for the duration of the testing and release candidate phase.  The key is that **you should only commit
bugfixes to this branch**.  This lets you focus on getting a stable version of module ready for release, and new
features can still be added to trunk.

Creating a release branch is a simple `svn cp` command.  In the example below, (modulename) would be something like
"ecommerce" or "flickrservice", and (releasenumber) would be something like "0.2.1" (see 
[Producing OSS: Release Numbering](http://producingoss.com/en/development-cycle.html#release-numbering))

	svn cp http://svn.silverstripe.com/open/modules/(modulename)/trunk http://svn.silverstripe.com/open/modules/(modulename)/branches/(releasenumber)

Once you have created a release branch, you should do some testing of the module yourself.  Try installing it on a new
site, and existing site, use the different features, and if possible, install on a couple of different servers.

See [SVN Book: "Release Branches"](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.commonpatterns.html#svn.branchmerge.commonpatterns.release),
[Producing OSS: "Release Branches"](http://producingoss.com/en/release-branches.html) and 
[Producing OSS: "Stabilizing a release"](http://producingoss.com/en/stabilizing-a-release.html) for more details.

### Release Candidates

Once you've done your own testing, it's time to create a release candidate (RC).  This is a copy of your module that
will be sent to the developer community for testing and feedback. Creating a release candidate is a matter of executing
a `svn cp` command.

Note: If you are the only developer on the module, and you aren't going to be creating any new features for the duration
of the release cycle, then you can get away with creating your RCs directly from trunk instead of creating a release
branch. For major modules, we advise against this, but for very simple modules, going through the whole release process
might be overkill.

	svn cp http://svn.silverstripe.com/open/modules/(modulename)/branches/(releasenumber) http://svn.silverstripe.com/open/modules/(modulename)/tags/rc/(releasenumber)-rc1
	svn co http://svn.silverstripe.com/open/modules/(modulename)/tags/rc/(releasenumber)-rc1 (modulename)
	tar czf (modulename)_(releasenumber)-rc1.tar.gz (modulename)

See ["ReleaseBranches" chapter](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.commonpatterns.html#svn.branchmerge.commonpatterns.release)
and ["Tags" chapter](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.tags.html).

### Stabilizing A Release

After you have put a release candidate out for testing and no-one has found any bugs that would prevent a release, you
can create the stable release! Please: **The stable release should always be a copy of a release candidate**.  Even if
"there's just one tiny bug to fix", you shouldn't release that bug fix onto a stable release - there is always the risk
that you inadvertently broke something! As you might guess, `svn cp` is used to create the final release, and then an
export to a tar.gz.

	svn cp http://svn.silverstripe.com/open/modules/(modulename)/tags/rc/(releasenumber)-rc2  http://svn.silverstripe.com/open/modules/(modulename)/tags/(releasenumber)
	svn export http://svn.silverstripe.com/open/modules/(modulename)/tags/(releasenumber) (modulename)
	tar czf (modulename)_(releasenumber).tar.gz (modulename)

### Announcing a Release or Release Candidate

*  See [Producing OSS: "Announcing Releases"](http://producingoss.com/en/testing-and-releasing.html#release-announcement)
*  Update your [documentation](module-release-process#documentation) in the sourcecode, wiki and README
*  Add your release to the [silverstripe.org/modules](http://silverstripe.org/modules) listing
*  Announce the release on [silverstripe-announce](http://groups.google.com/group/silverstripe-announce).  Include a
[changelog](module-release-process#changelogs), the download link and instructions for filing bug reports.
*  If this release is a major release, our [marketing guys](http://silverstripe.com/contact/) will strive to announce it
on the main [silverstripe.com blog](http://silverstripe.com/blog) as well


### Changelogs

Each release you make should contain `CHANGELOG` file in the project root with a highlevel overview of additions and
bugfixes in this release. The `svn log` command gives you all commit messages for a specific project, and is a good
start to build a changelog (see ["Examining historical changes" chapter](http://svnbook.red-bean.com/en/1.5/svn.tour.history.html)). 
Depending on the volume of changes, it is preferred that you summarize these messages in a more "digestible" 
form (see [Producing OSS: "Changes vs. Changelog"](http://producingoss.com/en/packaging.html#changelog)).

A good `CHANGELOG` example from the subversion project itself:

	Version 1.5.2
	(29 Aug 2008, from /branches/1.5.x)
	http://svn.collab.net/repos/svn/tags/1.5.2
	
	 User-visible changes:
	
	  * Set correct permissions on created fsfs shards (r32355, -7)
	  * Pass client capabilities to start-commit hook (issue #3255)
	  * Disallow creating nested repositories (issue #3269)
	
	 Developer-visible changes:
	
	  * make libsvn_ra_neon initialization thread-safe (r32497, r32510)
	
	Version 1.5.1
	(24 Jul 2008, from /branches/1.5.x)
	http://svn.collab.net/repos/svn/tags/1.5.1
	
	...


### Release Branch Maintenance

This is also the time to remove the release branch from the subversion tree - we don't want to have lots of branches on
the source tree to confuse everyone.  However, before you do this, you will need to merge your changes back to the
trunk.

## See Also

* [Module Development](/topics/module-development)
* [Documentation Guide](contributing#writing-documentation)
* [Contributing to SilverStripe](http://silverstripe.org/contributing-to-silverstripe/)
* [Submit your Module](http://silverstripe.org/modules/manage/add)
* [subversion](subversion)
