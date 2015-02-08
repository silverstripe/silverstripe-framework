# Contributing Code - Submiting Bugfixes and Enhancements

SilverStripe will never be finished, and we need your help to keep making it better.  If you're a developer a great way to get involved is to contribute patches to our modules and core codebase, fixing bugs or adding feautres.

The SilverStripe core modules (`framework` and `cms`), as well as some of the more popular modules are in
git version control. SilverStripe hosts its modules on [github.com/silverstripe](http://github.com/silverstripe) and [github.com/silverstripe-labs](http://github.com/silverstripe-labs).  After [installing git](http://help.github.com/git-installation-redirect) and creating a [free github.com account](https://github.com/signup/free), you can "fork" a module,
which creates a copy that you can commit to (see github's [guide to "forking"](http://help.github.com/forking/)).

For other modules, our [module list on silverstripe.org](http://silverstripe.org/modules) lists the repository locations, typically using a version control system like "git" or "[subversion](subversion)". 

<div class="hint" markdown="1">
Note: By supplying code to the SilverStripe core team in patches, tickets and pull requests, you agree to assign copyright of that code to SilverStripe Limited, on the condition that SilverStripe Limited releases that code under the BSD license.

We ask for this so that the ownership in the license is clear and unambiguous, and so that community involvement doesn't stop us from being able to continue supporting these projects.  By releasing this code under a permissive license, this copyright assignment won't prevent you from using the code in any way you see fit.
</div>

## Step-by-step: From forking to sending the pull request

1. Install the project through composer. The process is described in detail in "[Installation through Composer](../../installation/composer#contributing)".

 		composer create-project --keep-vcs --dev silverstripe/installer ./my/website/folder 3.1.x-dev

2. Edit the `composer.json`. Remove the `@stable` markers from the core modules in there. 
   Add your fork URLs, in this example a fork of the `cms` module on the `sminnee` github account 
   (replace with your own fork URL). Run a `composer update` afterwards.

		"repositories": [
			{
				"type": "vcs",
				"url": "git@github.com:sminnee/silverstripe-cms.git"
			}
		]

3. Add a new "upstream" remote so you can track the original repository for changes, and rebase/merge your fork as required.

		cd cms
		git remote add -f upstream git://github.com/silverstripe/silverstripe-cms.git

4. [Branch for new issue and develop on issue branch](code#branch-for-new-issue-and-develop-on-issue-branch)

		git branch ###-description
		git checkout ###-description

5. As time passes, the upstream repository accumulates new commits. Keep your working copy's master branch and issue branch up to date by periodically [rebasing your development branch on the latest upstream](code#rebase-your-development-branch-on-the-latest-upstream).

		# [make sure all your changes are committed as necessary in branch]
		git fetch upstream
		git rebase upstream/master

6. When development is complete, [squash all commit related to a single issue into a single commit](code#squash-all-commits-related-to-a-single-issue-into-a-single-commit).

		git fetch upstream
		git rebase -i upstream/master

7. Push release candidate branch to GitHub 

		git push origin ###-description

8. Issue pull request on GitHub.  Visit your forked respoistory on GitHub.com and click the "Create Pull Request" button nex tot the new branch.

The core team is then responsible for reviewing patches and deciding if they will make it into core.  If
there are any problems they will follow up with you, so please ensure they have a way to contact you! 

### Workflow Diagram ###

![Workflow diagram](http://www.silverstripe.org/assets/doc-silverstripe-org/collaboration-on-github.png)

### Quickfire Do's and Don't's

If you aren't familiar with git and GitHub, try reading the ["GitHub bootcamp documentation"](http://help.github.com/). 
We also found the [free online git book](http://git-scm.com/book/) and the [git crash course](http://gitref.org/) useful.
If you're familiar with it, here's the short version of what you need to know. Once you fork and download the code:

  *  **Don't develop on the master branch.** Always create a development branch specific to "the issue" you're working on (mostly on our [bugtracker](/misc/contributing/issues)). Name it by issue number and description. For example, if you're working on Issue #100, a `DataObject::get_one()` bugfix, your development branch should be called 100-dataobject-get-one. If you decide to work on another issue mid-stream, create a new branch for that issue--don't work on both in one branch.

  * **Do not merge the upstream master** with your development branch; *rebase* your branch on top of the upstream master.

  * **A single development branch should represent changes related to a single issue.** If you decide to work on another issue, create another branch.

  * **Squash your commits, so that each commit addresses a single issue.** After you rebase your work on top of the upstream master, you can squash multiple commits into one. Say, for instance, you've got three commits in related to Issue #100. Squash all three into one with the message "Issue #100 Description of the issue here." We won't accept pull requests for multiple commits related to a single issue; it's up to you to squash and clean your commit tree. (Remember, if you squash commits you've already pushed to GitHub, you won't be able to push that same branch again. Create a new local branch, squash, and push the new squashed branch.)

  * **Choose the correct branch**: Assume the current release is 3.0.3, and 3.1.0 is in beta state.
  Most pull requests should go against the `3.1.x-dev` *pre-release branch*, only critical bugfixes
  against the `3.0.x-dev` *release branch*. If you're changing an API or introducing a major feature,
  the pull request should go against `master` (read more about our [release process](/misc/release-process)). Branches are periodically merged "upwards" (3.0 into 3.1, 3.1 into master).

### Editing files directly on GitHub.com

If you see a typo or another small fix that needs to be made, and you don't have an installation set up for contributions, you can edit files directly in the github.com web interface.  Every file view has an "edit this file" link.

After you have edited the file, GitHub will offer to create a pull request for you.  This pull request will be reviewed along with other pull requests.

## Check List

*  Adhere to our [coding conventions](/misc/coding-conventions)
*  If your patch is extensive, discuss it first on the [silverstripe-dev google group](https://groups.google.com/group/silverstripe-dev) (ideally before doing any serious coding)
*  When working on existing tickets, provide status updates through ticket comments
*  Check your patches against the "master" branch, as well as the latest release branch
*  Write [unit tests](/topics/testing)
*  Write [Behat integration tests](https://github.com/silverstripe-labs/silverstripe-behat-extension) for any interface changes
*  Describe specifics on how to test the effects of the patch
*  It's better to submit multiple patches with separate bits of functionality than a big patch containing lots of
changes
*  Only submit a pull request for work you expect to be ready to merge. Work in progress is best discussed in an issue, or on your own repository fork.
*  Document your code inline through [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) syntax. See our 
[API documentation](http://api.silverstripe.org/3.1/) for good examples.
* Check and update documentation on [doc.silverstripe.org](http://doc.silverstripe.org). Check for any references to functionality deprecated or extended through your patch. Documentation changes should be included in the patch.
* If you get stuck, please post to the [forum](http://silverstripe.org/forum) or for deeper core problems, to the [core mailinglist](https://groups.google.com/forum/#!forum/silverstripe-dev)
* When working with the CMS, please read the ["CMS Architecture Guide"](/reference/cms-architecture) first

## Commit Messages

We try to maintain a consistent record of descriptive commit messages. 
Most importantly: Keep the first line short, and add more detail below.
This ensures commits are easy to browse, and look nice on github.com
(more info about [proper git commit messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)).

As we automatically generate [changelogs](http://doc.silverstripe.org/sapphire/en/trunk/changelogs/) from them, we need a way to categorize and filter. 
Please prefix **noteworthy** commit messages with one of the following tags: 

* `NEW`: New feature or major enhancement (both for users and developers)
* `API`: Addition of a new API, or modification/removal/deprecation of an existing API.
  Includes any change developers should be aware of when upgrading.
* `BUG`: Bugfix or minor enhancement on something developers or users are likely to encounter.

All other commits should not be tagged if they are so trivial that most developers
can ignore them during upgrades or when reviewing changes to the codebase.
For example, adding unit tests or documentation would not be considered "noteworthy".
Same goes for version control plumbing like merges, file renames or reverts.

Further guidelines:

* Each commit should form a logical unit - if you fix two unrelated bugs, commit each one separately
* If you are fixing a issue from our bugtracker ([cms](http://github.com/silverstripe/silverstripe-framework) and [framework](http://github.com/silverstripe/silverstripe-framework)), please append `(fixes #<ticketnumber>)`
* If your change is related to another commit, reference it with its abbreviated commit hash. 
* Mention important changed classes and methods in the commit summary.

Example: Bad commit message

	finally fixed this dumb rendering bug that Joe talked about ... LOL
	also added another form field for password validation

Example: Good commit message

	BUG Formatting through prepValueForDB() 

	Added prepValueForDB() which is called on DBField->writeToManipulation() 
	to ensure formatting of value before insertion to DB on a per-DBField type basis (fixes #1234).
	Added documentation for DBField->writeToManipulation() (related to a4bd42fd).

## The steps in more detail

### Branch for new issue and develop on issue branch

Before you start working on a new feature or bugfix, create a new branch dedicated to that one change named by issue number and description. If you're working on Issue #100, a retweet bugfix, create a new branch with the issue number and description, like this:

	$ git branch 100-dataobject-get-one
	$ git checkout 100-dataobject-get-one

Edit and test the files on your development environment. When you've got something the way you want and established that it works, commit the changes to your branch on your local git repo. 

	$ git add <filename>
	$ git commit -m 'Issue #100: Some kind of descriptive message' 

You'll need to use git add for each file that you created or modified. There are ways to add multiple files, but I highly recommend a more deliberate approach unless you know what you're doing.

Then, you can push your new branch to GitHub, like this (replace `100-dataobject-get-one` with your branch name):

	$ git push origin 100-dataobject-get-one

You should be able to log into your GitHub account, switch to the branch, and see that your changes have been committed. Then click the Pull button to request that your commits get merged into the development master.  

### Rebase Your Development Branch on the Latest Upstream

To keep your development branch up to date, rebase your changes on top of the current state of the upstream master. See the [What is git rebase?](code#what-is-git-rebase) section below to learn more about rebasing.

If you've set up an upstream branch as detailed above, and a development branch called `100-dataobject-get-one`, you can update `upstream` and rebase your branch from it like so:

    # [make sure all your changes are committed as necessary in branch]
	$ git fetch upstream
	$ git rebase upstream/master

Note that the example doesn't keep your own master branch up to date.  If you wanted to that, you might take the following approach instead:

	# [make sure all your changes are committed as necessary in branch]
	$ git fetch upstream
	$ git checkout master
	$ git rebase upstream/master
	$ git checkout 100-dataobject-get-one
	$ git rebase master

You may need to resolve conflicts that occur when a file on the development trunk and one of your files have both been changed. Edit each file to resolve the differences, then commit the fixes to your development server repo and test. Each file will need to be "added" before running a "commit." 

Conflicts are clearly marked in the code files. Make sure to take time in determining what version of the conflict you want to keep and what you want to discard. 

	$ git add <filename>
	$ git rebase --continue

### Squash All Commits Related to a Single Issue into a Single Commit

Once you have rebased your work on top of the latest state of the upstream master, you may have several commits related to the issue you were working on. Once everything is done, squash them into a single commit with a descriptive message (see ["Contributing: Commit Messages"](code#commit-messages)).

To squash four commits into one, do the following:

	$ git rebase -i upstream/master

In the text editor that comes up, replace the words "pick" with "squash" next to the commits you want to squash into the commit before it. Save and close the editor, and git will combine the "squash"'ed commits with the one before it. Git will then give you the opportunity to change your commit message to something like, "BUGFIX Issue #100: Fixed DataObject::get_one() parameter order"

Important: If you've already pushed commits to GitHub, and then squash them locally, you will have to force-push to your GitHub again.  Add the `-f` argument to your git push command:

    $ git push -f origin 100-dataobject-get-one

Helpful hint: You can always edit your last commit message by using:

	$ git commit --amend

## Some gotchas

Be careful not to commit any of your configuration files, logs, or throwaway test files to your GitHub repo. These files can contain information you wouldn't want publicly viewable and they will make it impossible to merge your contributions into the main development trunk.

Most of these special files are listed in the `.gitignore` file and won't be included in any commit, but you should carefully review the files you have modified and added before staging them and committing them to your repo. The git status command will display detailed information about any new files, modifications and staged.

	$ git status 

One thing you do not want to do is to issue a git commit with the -a option. This automatically stages and commits every modified file that's not expressly defined in .gitignore, including your crawler logs.

	$ git commit -a 

## What is git rebase?

Using `git rebase` helps create clean commit trees and makes keeping your code up-to-date with the current state of the upstream master easy. Here's how it works.

Let's say you're working on Issue #212 a new plugin in your own branch and you start with something like this:

          1---2---3 #212-my-new-plugin
         /
    A---B #master

You keep coding for a few days and then pull the latest upstream stuff and you end up like this:

          1---2---3 #212-my-new-plugin
         /
    A---B--C--D--E--F #master

So all these new things (C,D,..F) have happened since you started. Normally you would just keep going (let's say you're not finished with the plugin yet) and then deal with a merge later on, which becomes a commit, which get moved upstream and ends up grafted on the tree forever.

A cleaner way to do this is to use rebase to essentially rewrite your commits as if you had started at point F instead of point B. So just do:

git rebase master 212-my-new-plugin

git will rewrite your commits like this:

                      1---2---3 #212-my-new-plugin
                     /
    A---B--C--D--E--F #master

It's as if you had just started your branch. One immediate advantage you get is that you can test your branch now to see if C, D, E, or F had any impact on your code (you don't need to wait until you're finished with your plugin and merge to find this out). And, since you can keep doing this over and over again as you develop your plugin, at the end your merge will just be a fast-forward (in other words no merge at all).

So when you're ready to send the new plugin upstream, you do one last rebase, test, and then merge (which is really no merge at all) and send out your pull request. Then in most cases, we have a simple fast-forward on our end (or at worst a very small rebase or merge) and over time that adds up to a simpler tree.

More info on the ["Rebasing" chapter on git-scm.com](http://git-scm.com/book/ch3-6.html) and the [git rebase man page](http://www.kernel.org/pub/software/scm/git/docs/git-rebase.html).

## License

Portions of this guide have been adapted from the ["Thinkup" developer guide](https://github.com/ginatrapani/ThinkUp/wiki/Developer-Guide%3A-Get-the-Source-Code-from-GitHub-and-Keep-It-Updated),
with friendly permission from Gina Trapani/[thinkupapp.com](http://thinkupapp.com).
