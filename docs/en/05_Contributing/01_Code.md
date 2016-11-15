title: Contributing Code
summary: Fix bugs and add new features to help make SilverStripe better.

# Contributing Code - Submitting Bugfixes and Enhancements

SilverStripe will never be finished, and we need your help to keep making it better.  If you're a developer a great way to get involved is to contribute patches to our modules and core codebase, fixing bugs or adding features.

The SilverStripe core modules (`framework` and `cms`), as well as some of the more popular modules are in
git version control. SilverStripe hosts its modules on [github.com/silverstripe](http://github.com/silverstripe) and [github.com/silverstripe-labs](http://github.com/silverstripe-labs).  After [installing git](http://help.github.com/git-installation-redirect) and creating a [free github.com account](https://github.com/signup/free), you can "fork" a module,
which creates a copy that you can commit to (see github's [guide to "forking"](http://help.github.com/forking/)).

For other modules, our [add-ons site](http://addons.silverstripe.org/add-ons) lists the repository locations, typically using the version control system like "git".

If you are modifying CSS or JavaScript files in core modules, you'll need to regenerate some files.
Please check out our [client-side build tooling](build_tooling) guide for details.

<div class="hint" markdown="1">
Note: By supplying code to the SilverStripe core team in patches, tickets and pull requests, you agree to assign copyright of that code to SilverStripe Limited, on the condition that SilverStripe Limited releases that code under the BSD license.

We ask for this so that the ownership in the license is clear and unambiguous, and so that community involvement doesn't stop us from being able to continue supporting these projects.  By releasing this code under a permissive license, this copyright assignment won't prevent you from using the code in any way you see fit.
</div>

## Step-by-step: From forking to sending the pull request

<div class="notice" markdown='1'>
**Note:** Please adjust the commands below to the version of SilverStripe that you're targeting.
</div>

1. Create a [fork](https://help.github.com/articles/about-forks/) of the module you want to contribute to (listed on [github.com/silverstripe/](https://github.com/silverstripe/)).

1. Install the project through composer. The process is described in detail in "[Installation through Composer](../getting_started/composer#contributing)".

 		composer create-project --keep-vcs silverstripe/installer ./your-website-folder 4.0.x-dev

1. Add a new "upstream" remote to the module you want to contribute to (e.g. `cms`). 
This allows you to track the original repository for changes, and rebase/merge your fork as required.
Use your Github user name for which you created the fork in Step 1.

		cd framework
		git remote rename origin upstream
		git branch --set-upstream-to upstream
		git remote add -f origin git://github.com/<your-github-user>/silverstripe-framework.git

1. [Branch for new issue and develop on issue branch](code#branch-for-new-issue-and-develop-on-issue-branch)

		# verify current branch 'base' then branch and switch
		cd framework
		git status
		git checkout -b <your-branch-name>

1. As time passes, the upstream repository accumulates new commits. Keep your working copy's branch and issue branch up to date by periodically running a `composer update`.
As a first step, make sure you have committed all your work, then temporarily switch over to the `master` branch while updating.
Alternatively, you can use [composer "repositories"](https://getcomposer.org/doc/05-repositories.md#vcs),
but we've found that dramatically slows down any updates. You may need to [resolve conflicts](https://help.github.com/articles/resolving-merge-conflicts-after-a-git-rebase/).

		(cd framework && git checkout master)
		composer update
		(cd framework && git checkout <your-branch-name>) 
		(cd framework && git rebase upstream/master)

1. When development is complete, run another update, and consider [squashing your commits](https://help.github.com/articles/using-git-rebase-on-the-command-line/)

1. Push your branch to your GitHub fork 

		cd framework
		git push origin <your-branch-name>

1. Issue pull request on GitHub.  Visit your forked repository on gitHub.com and click the "Create Pull Request" button next to the new branch.

Please read [collaborating with pull requests](https://help.github.com/categories/collaborating-with-issues-and-pull-requests/) on github.com
for more details.

The core team is then responsible for reviewing patches and deciding if they will make it into core.  If
there are any problems they will follow up with you, so please ensure they have a way to contact you!


### Picking the right version

SilverStripe core and module releases (since the 3.1.8 release) follow the [Semantic Versioning](http://semver.org) 
(SemVer) specification for releases. Using this specification declares to the entire development community the severity 
and intention of each release. It gives developers the ability to safely declare their dependencies and understand the
scope involved in each upgrade.

Each release is labeled in the format `$MAJOR`.`$MINOR`.`$PATCH`. For example, 3.1.8 or 3.2.0.

* `$MAJOR` version is incremented if any backwards incompatible changes are introduced to the public API. 
* `$MINOR` version is incremented if new, backwards compatible **functionality** is introduced to the public API or 
	improvements are introduced within the private code. 
* `$PATCH` version is incremented if only backwards compatible **bug fixes** are introduced. A bug fix is defined as 
	an internal change that fixes incorrect behavior.

**Public API** refers to any aspect of the system that has been designed to be used by SilverStripe modules & site developers. In SilverStripe 3, because we haven't been clear, in principle we have to treat every public or protected method as *potentially* part of the public API, but sometimes it comes to a judgement call about how likely it is that a given method will have been used in a particular way. If we were strict about never changing publicly exposed behaviour, it would be difficult to fix any bug whatsoever, which isn't in the interests of our user community.

In future major releases of SilverStripe, we will endeavour to be more explicit about documenting the public API.

**Contributing bug fixes**

Bug fixes should be raised against the most recent MINOR release branch. For example, If your project is on 3.3.1 and 3.4.0 is released, please raise your bugfix against the `3.4` branch. Older MINOR release branches are primarily intended for critical bugfixes and security issues.

**Contributing features**

When contributing a backwards compatible change, raise it against the same MAJOR branch as your project. For example, if your project is on 3.3.1, raise it against the `3` branch. It will be included in the next MINOR release, e.g. 3.4.0. And then when it is released, you should upgrade your project to use it. As it is a MINOR change, it shouldn't break anything, and be a relatively painless upgrade.

**Contributing backwards-incompatible public API changes, and removing or radically changing existing feautres**

When contributing a backwards incompatible change, you must raise it against the `master` branch.


### The Pull Request Process

Once your pull request is issued, it's not the end of the road. A [core committer](/contributing/core_committers/) will most likely have some questions for you and may ask you to make some changes depending on discussions you have.
If you've been naughty and not adhered to the [coding conventions](coding_conventions), 
expect a few requests to make changes so your code is in-line.

If your change is particularly significant, it may be referred to the [mailing list](https://groups.google.com/forum/#!forum/silverstripe-dev) for further community discussion.

A core committer will also "label" your PR using the labels defined in GitHub, these are to correctly classify and help find your work at a later date.

#### GitHub Labels

The current GitHub labels are grouped into five sections:

1. *Changes* - These are designed to signal what kind of change they are and how they fit into the [Semantic Versioning](http://semver.org/) schema
2. *Impact* - What impact does this bug/issue/fix have, does it break a feature completely, is it just a side effect or is it trivial and not a bit problem (but a bit annoying)
3. *Effort* - How much effort is required to fix this issue?
4. *Type* - What aspect of the system the PR/issue covers
5. *Feedback* - Are we waiting on feedback, if so who from? Typically used for issues that are likely to take a while to have feedback given

| Label | Purpose |
| ----- | ------- |
| change/major | A change for the next major release (eg: 4.0) |
| change/minor | A change for the next minor release (eg: 3.x) |
| change/patch | A change for the next patch release (eg: 3.1.x) |
| impact/critical | Broken functionality for which no work around can be produced |
| impact/high | Broken functionality but can be mitigated by other non-core code changes |
| impact/medium | Unexpected behaviour but does not break functionality |
| impact/low | A nuisance but doesn't break any functionality (typos, etc) |
| effort/easy | Someone with limited SilverStripe experience could resolve |
| effort/medium | Someone with a good understanding of SilverStripe could resolve |
| effort/hard | Only an expert with SilverStripe could resolve |
| type/docs | A docs change |
| type/frontend | A change to front-end (CSS, HTML, etc) |
| feedback-required/core-team | Core team members need to give an in-depth consideration |
| feedback-required/author | This issue is awaiting feedback from the original author of the PR |

### Quickfire Do's and Don't's

If you aren't familiar with git and GitHub, try reading the ["GitHub bootcamp documentation"](http://help.github.com/). 
We also found the [free online git book](http://git-scm.com/book/) and the [git crash course](http://gitref.org/) useful.
If you're familiar with it, here's the short version of what you need to know. Once you fork and download the code:

* **Don't develop on the master branch.** Always create a development branch specific to "the issue" you're working on (on our [GitHub repository's issues](https://github.com/silverstripe/silverstripe-framework/issues)). Name it by issue number and description. For example, if you're working on Issue #100, a `DataObject::get_one()` bugfix, your development branch should be called 100-dataobject-get-one. If you decide to work on another issue mid-stream, create a new branch for that issue--don't work on both in one branch.

* **Do not merge the upstream master** with your development branch; *rebase* your branch on top of the upstream master.

* **A single development branch should represent changes related to a single issue.** If you decide to work on another issue, create another branch.

* **Squash your commits, so that each commit addresses a single issue.** After you rebase your work on top of the upstream master, you can squash multiple commits into one. Say, for instance, you've got three commits in related to Issue #100. Squash all three into one with the message "Description of the issue here (fixes #100)" We won't accept pull requests for multiple commits related to a single issue; it's up to you to squash and clean your commit tree. (Remember, if you squash commits you've already pushed to GitHub, you won't be able to push that same branch again. Create a new local branch, squash, and push the new squashed branch.)

* **Choose the correct branch**: see [Picking the right version](#picking-the-right-version).

### Editing files directly on GitHub.com

If you see a typo or another small fix that needs to be made, and you don't have an installation set up for contributions, you can edit files directly in the github.com web interface.  Every file view has an "edit this file" link.

After you have edited the file, GitHub will offer to create a pull request for you.  This pull request will be reviewed along with other pull requests.

## Check List

* Adhere to our [coding conventions](/contributing/coding_conventions)
* If your patch is extensive, discuss it first on the [silverstripe-dev google group](https://groups.google.com/group/silverstripe-dev) (ideally before doing any serious coding)
* When working on existing tickets, provide status updates through ticket comments
* Check your patches against the "master" branch, as well as the latest release branch
* Write [unit tests](../developer_guides/testing/unit_testing)
* Write [Behat integration tests](https://github.com/silverstripe-labs/silverstripe-behat-extension) for any interface changes
* Describe specifics on how to test the effects of the patch
* It's better to submit multiple patches with separate bits of functionality than a big patch containing lots of changes
* Only submit a pull request for work you expect to be ready to merge. Work in progress is best discussed in an issue, or on your own repository fork.
* Document your code inline through [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) syntax. See our 
[API documentation](http://api.silverstripe.org/3.1/) for good examples.
* Check and update documentation on [docs.silverstripe.org](http://docs.silverstripe.org). Check for any references to functionality deprecated or extended through your patch. Documentation changes should be included in the patch.
* When introducing something "noteworthy" (new feature, API change), [update the release changelog](/changelogs) for the next release this commit will be included in.
* If you get stuck, please post to the [forum](http://silverstripe.org/forum) or for deeper core problems, to the [core mailinglist](https://groups.google.com/forum/#!forum/silverstripe-dev)
* When working with the CMS, please read the ["CMS Architecture Guide"](/developer_guides/customising_the_admin_interface/cms_architecture) first

## Commit Messages

We try to maintain a consistent record of descriptive commit messages. 
Most importantly: Keep the first line short, and add more detail below.
This ensures commits are easy to browse, and look nice on github.com
(more info about [proper git commit messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)).

As we automatically generate [changelogs](http://localhost/SpiritLevel/SS/doc.silverstripe.org/en/changelogs/) from them, we need a way to categorize and filter. 
Please prefix **noteworthy** commit messages with one of the following tags: 

* `NEW` New feature or major enhancement (both for users and developers)
* `API` Addition of a new API, or modification/removal/deprecation of an existing API. Includes any change developers should be aware of when upgrading.
* `BUG` Bugfix or minor enhancement on something developers or users are likely to encounter.

All other commits should not be tagged if they are so trivial that most developers
can ignore them during upgrades or when reviewing changes to the codebase.
For example, adding unit tests or documentation would not be considered "noteworthy".
Same goes for version control plumbing like merges, file renames or reverts.

Further guidelines:

* Each commit should form a logical unit - if you fix two unrelated bugs, commit each one separately
* If you are fixing a issue from our bugtracker ([cms](http://github.com/silverstripe/silverstripe-framework) and [framework](http://github.com/silverstripe/silverstripe-framework)), please append `(fixes #<ticketnumber>)`
* When fixing issues across repos (e.g. a commit to `framework` fixes an issue raised in the `cms` bugtracker),
  use `(fixes silverstripe/silverstripe-cms#<issue-number>)` ([details](https://github.com/blog/1439-closing-issues-across-repositories))
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
