---
title: Contributing Code
summary: Fix bugs and add new features to help make Silverstripe CMS better.
icon: code
---

# Contributing Code - Submitting Bugfixes and Enhancements

Silverstripe CMS will never be finished, and we need your help to keep making it better.  If you're a developer a great way to get involved is to contribute patches to our modules and core codebase, fixing bugs or adding features.

The Silverstripe CMS core modules (`framework` and `cms`), as well as some of the more popular modules are in
git version control. Silverstripe CMS hosts its modules on [github.com/silverstripe](https://github.com/silverstripe/).  After [installing git](https://help.github.com/articles/set-up-git/) and creating a [free github.com account](https://github.com/join/), you can "fork" a module,
which creates a copy that you can commit to (see github's [guide to "forking"](https://help.github.com/articles/fork-a-repo/)).

For other modules, our [add-ons site](https://addons.silverstripe.org/add-ons/) lists the repository locations, typically using the version control system like "git".

If you are modifying CSS or JavaScript files in core modules, you'll need to regenerate some files.
Please check out our [client-side build tooling](build_tooling) guide for details.

[hint]
Note: By supplying code to the Silverstripe CMS core team in patches, tickets and pull requests, you agree to assign copyright of that code to Silverstripe Limited, on the condition that Silverstripe Limited releases that code under the BSD license.

We ask for this so that the ownership in the license is clear and unambiguous, and so that community involvement doesn't stop us from being able to continue supporting these projects.  By releasing this code under a permissive license, this copyright assignment won't prevent you from using the code in any way you see fit.
[/hint]

## Step-by-step: From forking to sending the pull request

[notice]
**Note:** Please adjust the commands below to the version of Silverstripe CMS that you're targeting.
[/notice]

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

The Silverstripe CMS project follows the [Semantic Versioning](http://semver.org) (SemVer) specification for releases. 
It clarifies what to expect from different releases, and also guides you in choosing the right branch to base your pull request on.

If you are unsure what branch your pull request should go to, consider asking in the GitHub issue that you address with your patch, or
simply choose the "default branch" of the repository where you want to contribute to. That would usually target the next minor release of the module.

If you are changing existing APIs, introducing new APIs or major new features,
please review our guidance on [supported versions](release_process#supported-versions).
You will need to choose the branch for your pull request accordingly.

As we follow SemVer, we name the branches in repositories accordingly
(using BNF rules defined by [semver.org](https://semver.org/#backusnaur-form-grammar-for-valid-semver-versions)):
 - `"master"` branch contains the next major and yet unreleased version
 - `<positive digit>` branches contain released major versions and all changes for yet unreleased minor versions
 - `<positive digit> "." <digits>` branches contain released minor versions and all changes for yet to be released patch versions


Silverstripe CMS public APIs explicitly include:
 - namespaces, classes, interfaces and traits
 - public and protected scope (including methods, properties and constants)
 - global functions, variables
 - yml configuration file structure and value types
 - private static class properties (considered to be configuration variables)

Silverstripe CMS public APIs explicitly exclude:
 - private scope (methods and properties with the exception for `private static`)
 - entities marked as `@internal`
 - yml configuration file default values
 - HTML, CSS, JavaScript, TypeScript, SQL and anything else that is not PHP

Other entities might be considered to be included or excluded from the public APIs on case-by-case basis.

Any updates to third party dependencies in composer.json should aim to target the default branch for a minor release if possible. Targeting a non-default branch for a patch release is acceptable if updating dependencies is required to fix a bug.

### The Pull Request Process

Once your pull request is issued, it's not the end of the road. A [core committer](/contributing/core_committers/) will most likely have some questions for you and may ask you to make some changes depending on discussions you have.
If you've been naughty and not adhered to the [coding conventions](coding_conventions), 
expect a few requests to make changes so your code is in-line.

If your change is particularly significant, it may be referred to the [forum](https://forum.silverstripe.org) for further community discussion.

A core committer will also "label" your PR using the labels defined in GitHub, these are to correctly classify and help find your work at a later date.

#### GitHub Labels {#labels}

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
| impact/critical | Broken functionality/experience without any workarounds, or an enhancement that is required to enable a critical task. Typically affecting major usage flows or core interactions. If the issue is `type/bug`, the fix for it will target all [supported minor release](release_process#supported-versions) lines |
| impact/high | Broken functionality/experience with no obvious workarounds available, or an enhancement that provides a clear benefit to users. Typically affecting major usage flows or core interactions |
| impact/medium | Unexpected behaviour, or broken functionality on less common usage flows |
| impact/low | A nuisance but doesn't break any functionality (typos, etc) |
| effort/easy | Someone with limited Silverstripe CMS experience could resolve |
| effort/medium | Someone with a good understanding of Silverstripe CMS could resolve |
| effort/hard | Only an expert with Silverstripe CMS could resolve |
| type/docs | A docs change |
| type/bug | Does not function as intended, or is inadequate for the purpose it was created for |
| type/frontend | A change to front-end (CSS, HTML, etc) |
| type/enhancement | New feature or improvement for either users or developers |
| type/ux | Impact on the CMS user or user interface |
| feedback-required/core-team | Core team members need to give an in-depth consideration |
| feedback-required/author | This issue is awaiting feedback from the original author of the PR |
| rfc/draft | [RFC](request_for_comment) under discussion |
| rfc/accepted | [RFC](request_for_comment) where agreement has been reached |
| affects/* | Issue has been observed on a specific release line |

### Quickfire Do's and Don't's

If you aren't familiar with git and GitHub, try reading the ["GitHub bootcamp documentation"](https://help.github.com/). 
We also found the [free online git book](https://git-scm.com/book/en/v2) and the [git crash course](https://services.github.com/) useful.
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
* If your patch is extensive, discuss it first on the [Silverstripe CMS Forums](https://forum.silverstripe.org/c/feature-ideas) (ideally before doing any serious coding)
* When working on existing tickets, provide status updates through ticket comments
* Check your patches against the "master" branch, as well as the latest release branch
* Write [unit tests](../developer_guides/testing/unit_testing)
* Write [Behat integration tests](https://github.com/silverstripe/silverstripe-behat-extension) for any interface changes
* Describe specifics on how to test the effects of the patch
* It's better to submit multiple patches with separate bits of functionality than a big patch containing lots of changes
* Only submit a pull request for work you expect to be ready to merge. Work in progress is best discussed in an issue, or on your own repository fork.
* Document your code inline through [PHPDoc](https://en.wikipedia.org/wiki/PHPDoc) syntax. See our 
[API documentation](https://api.silverstripe.org/) for good examples.
* Check and update documentation on [docs.silverstripe.org](https://docs.silverstripe.org). Check for any references to functionality deprecated or extended through your patch. Documentation changes should be included in the patch.
* When introducing something "noteworthy" (new feature, API change), [update the release changelog](/changelogs) for the next release this commit will be included in.
* If you get stuck, please post to the [forum](https://www.silverstripe.org/community/forums)
* When working with the CMS, please read the ["CMS Architecture Guide"](/developer_guides/customising_the_admin_interface/cms_architecture) first
* Try to respond to feedback in a timely manner. PRs that go more than a month without a response from the author are considered stale, and will be politely chased up. If a response still isn't received, the PR will be closed two weeks after that.

## Commit Messages

We try to maintain a consistent record of descriptive commit messages. 
Most importantly: Keep the first line short, and add more detail below.
This ensures commits are easy to browse, and look nice on github.com
(more info about [proper git commit messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)).

Our [changelog](https://docs.silverstripe.org/en/changelogs/) generation tool relies upon commit prefixes (tags)
to categorize the patches accordingly and produce more readable output. Prefixes are usually a single case-insensitive word,
at the beginning of the commit message. Although prefixing is optional, **noteworthy** patches should have them to fall into
correct categories.

| Prefix | Description                                                                                                                                      |
| ---    | ---                                                                                                                                              |
| API    | Addition of a new API, or modification/removal/deprecation of an existing API. Includes any change developers should be aware of when upgrading. |
| FIX    | Bugfix on something developers or users are likely to encounter.                                                                                 |
| DOC    | Any documentation changes.                                                                                                                       |
| NEW    | New feature or major enhancement (both for users and developers)                                                                                 |
| ENH    | Improvements of existing functionality (with no API changes), UI/UX enhancements, refactoring and configuration updates.                         |
| MNT    | Maintenance commits that have no impact on users and applications (e.g. CI configs)                                                              |
| DEP    | Dependency version updates (updates for composer.json, package.json etc)                                                                         |
| Merge  | PR merges and merge-ups                                                                                                                          |

If you can't find the correct prefix for your commit, it is alright to leave it untagged, then it will fall into "Other" category.

Further guidelines:

* Each commit should form a logical unit - if you fix two unrelated bugs, commit each one separately
* If you are fixing a issue from our bugtracker (see [Reporting Bugs](issues_and_bugs)), please append `(fixes #<ticketnumber>)`
* When fixing issues across repos (e.g. a commit to `framework` fixes an issue raised in the `cms` bugtracker),
  use `(fixes silverstripe/silverstripe-cms#<issue-number>)` ([details](https://github.com/blog/1439-closing-issues-across-repositories))
* If your change is related to another commit, reference it with its abbreviated commit hash. 
* Mention important changed classes and methods in the commit summary.

Example: Bad commit message

```
finally fixed this dumb rendering bug that Joe talked about ... LOL
also added another form field for password validation
```

Example: Good commit message

```
FIX Formatting through prepValueForDB() 

Added prepValueForDB() which is called on DBField->writeToManipulation() 
to ensure formatting of value before insertion to DB on a per-DBField type basis (fixes #1234).
Added documentation for DBField->writeToManipulation() (related to a4bd42fd).
```
