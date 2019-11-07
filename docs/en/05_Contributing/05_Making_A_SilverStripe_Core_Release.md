---
title: Making a SilverStripe core release
summary: Development guide for core contributors to build and publish a new release
icon: github-alt
---

# Making a SilverStripe core release

## Introduction

This guide is intended to be followed by core contributors, allowing them to take
the latest development branch of each of the core modules, and building a release.
The artifacts for this process are typically:

 * A downloadable tar / zip on silverstripe.org
 * A published announcement
 * A new composer installable stable tag for silverstripe/installer

While this document is not normally applicable to normal silverstripe contributors,
it is still useful to have it available in a public location so that these users
are aware of these processes.

## First time setup

As a core contributor it is necessary to have installed the following set of tools:

### First time setup: Standard releases

* PHP 5.6+
* Python 2.7 / 3.5
* [cow release tool](https://github.com/silverstripe/cow#install). This should typically
  be installed in a global location via the below command. Please see the installation
  docs on the cow repo for more setup details.
  `composer global require silverstripe/cow ^2`
* [satis repository tool](https://github.com/composer/satis). This should be installed
  globally for minimum maintenance.
  `composer global require composer/satis ^1`
* [transifex client](http://docs.transifex.com/client/).
  `pip install transifex-client`
  If you're on OSX 10.10+, the standard Python installer is locked down.
  Use `brew install python; sudo easy_install pip` instead
* [AWS CLI tools](https://aws.amazon.com/cli/):
  `pip install awscli`
* The `tar` and `zip` commands
* A good `.env` setup in your localhost webroot.

Example `.env`:

```
# Environent
SS_TRUSTED_PROXY_IPS="*"
SS_ENVIRONMENT_TYPE="dev"

# DB Credentials
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_SERVER="127.0.0.1"
SS_DATABASE_USERNAME="root"
SS_DATABASE_PASSWORD=""

# Each release will have its own DB
SS_DATABASE_CHOOSE_NAME=1

# So you can test releases
SS_DEFAULT_ADMIN_USERNAME="admin"
SS_DEFAULT_ADMIN_PASSWORD="password"

# Basic CLI request url default
SS_BASE_URL="http://localhost/"
```

You will also need to be assigned the following permissions. Contact one of the SilverStripe staff from
the [core committers](core_committers), who will assist with setting up your credentials.

* Write permissions on the [silverstripe](https://github.com/silverstripe) organisation.
* Admin permissions on [transifex](https://www.transifex.com/silverstripe/).
  Set up a [~/.transifexrc](https://docs.transifex.com/client/client-configuration) with your credentials.
* AWS write permissions on the `silverstripe-ssorg-releases` s3 bucket
  ([add credentials](http://docs.aws.amazon.com/cli/latest/userguide/cli-chap-getting-started.html) via `aws configure`).
* Permission on [silverstripe release announcement](https://groups.google.com/forum/#!forum/silverstripe-announce).
* Moderator permissions in the [Slack channel](https://www.silverstripe.org/community/slack-signup/)
* Administrator account on [docs.silverstripe.org](https://docs.silverstripe.org) and
  [userhelp.silverstripe.org](https://userhelp.silverstripe.org).

### First time setup: Security releases

For doing security releases the following additional setup tasks are necessary:

* Write permissions on the [silverstripe-security](https://github.com/silverstripe-security)
  organisation.
* Permissions to write to the [security releases page](http://www.silverstripe.org/download/security-releases)
  and the [silverstripe.org CMS](http://www.silverstripe.org/admin).
* Permission on [security pre-announcement mailing list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).

## Security release process

### Overview

When doing a security release, typically one or more (or sometimes all) of the below
steps will need to be performed manually. As such, this guide should not be followed
exactly the same for these.

Standard practice is to produce a pre-release for any patched modules on the security 
forks, e.g. for cms and framework (see [silverstripe-security](https://github.com/silverstripe-security)).

<div class="warning" markdown="1">
Security issues are never disclosed until a public stable release containing this fix
is available, or within a reasonable period of time of such a release.
</div>

### When receiving a report

   * Perform initial criticality assessment, and ensure that the reporter is given a justification for all issues we classify or demote as non-security vulnerabilities.
   * If encrypted information is provided, add pass phrases into the SilverStripe Ltd. LastPass account. Keep encrypted documents in Google Drive and only share directly with relevant participants
   * Add a new issue in the "Backlog" on the [project board](https://github.com/silverstripe-security/security-issues/projects/1).
     Add a link to the [Google Groups](https://groups.google.com/a/silverstripe.com/forum/#!forum/security) discussion thread so it's easy to review follow up messages.
   * Use the [CVSS Calculator](https://nvd.nist.gov/vuln-metrics/cvss/v3-calculator) to determine the issue severity
   * Once the issue is confirmed, [request a CVE identifier](https://cveform.mitre.org/) under the security@silverstripe.org contact email (see "Acknowledgement and disclosure").
   * Once a CVE has been assigned, respond to issue reporter and add it to the Github issue 
   * Clarify who picks up and owns the issue (assign in Github).
     The owner can be separate from the developer resolving the issue,
     their primary responsibility is to ensure the issue keeps moving through the process correctly.

### When developing a fix

   * Ensure you're working on the oldest supported minor release branch of every supported major release (see [Supported Versions](#supported-versions))
   * Move the issue into "In Progress" on the [project board](https://github.com/silverstripe-security/security-issues/projects/1)
   * Add fixes on the [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repo. Don't forget to update branches from the upstream repo.
   * Ensure that all security commit messages are prefixed with the CVE. E.g. "[CVE-2019-001] Fixed invalid XSS"
   * Get them peer reviewed by posting on security@silverstripe.org with a link to the Github issue

### Before release (or release candidate)

   * For issues rated "high" or "critical" (CVSS of >=7.0), post a pre-announcement to the [security pre-announcement list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).
     It should include a basic "preannouncement description" which doesn't give away too much,
     the CVSS score as well as the CVE identifier.
   * Create a draft page under [Open Source > Download > Security Releases](https://www.silverstripe.org/admin/pages/edit/show/794).
     Populate it with the information from the [Github project board](https://github.com/silverstripe-security/security-issues/projects/1).
   * Link to silverstripe.org security release page in the changelog.
   * Move the issue to "Awaiting Release" in the [project board](https://github.com/silverstripe-security/security-issues/projects/1)

### Perform release

   * Public disclosure of security vulnerabilities need to happen in stable releases (not pre-releases)
   * Merge back from [http://github.com/silverstripe-security](http://github.com/silverstripe-security) repos shortly at the release (minimise early disclosure through source code)
   * Merge up to newer minor release branches (see [Supported Versions](#supported-versions))
   * Setup a temporary [satis](https://github.com/composer/satis) repository which points to all relevant repositories
  containing security fixes. See below for setting up a temporary satis repository.
   * Once release testing is completed and the release is ready for stabilisation, then these fixes
  can then be pushed to the upstream module fork, and the release completed as per normal.
   * Follow the steps for [making a core release](making_a_silverstripe_core_release)
 
### After release

   * Publish silverstripe.org security release page
   * Respond to issue reporter with reference to the release on the same discussion thread (cc security@silverstripe.org)
   * File a [CVE Publication Request](https://cveform.mitre.org/), and add a link to the security release
     through the "Link to the advisory" field. Note on the security issue thread
     that you've requested publication (to avoid double ups)
   * Move the issue to "Done" in the [project board](https://github.com/silverstripe-security/security-issues/projects/1)


### Setting up satis for hosting private security releases

When installing a project from protected repositories, it's necessary prior to creating your project
to override the public repository URLs with the private repositories containing undisclosed fixes. For
this we use [satis](https://github.com/composer/satis).

To setup a Satis project for a release:

* Ensure Satis is installed globally: `composer global require composer/satis ^1` 
* `cd ~/Sites/` (or wherever your web-root is located)
* `mkdir satis-security && cd satis-security` (or some directory specific to your release)
* Create a config file (e.g. config.json) of the given format (add only those repositories necessary).

Note:
- The homepage path should match the eventual location of the package content
- You should add the root repository (silverstripe/installer) to ensure
 `create-project` works (even if not a private security fork).
- You should add some package version constraints to prevent having to parse
 all legacy tags and all branches.

```json
{
    "name": "SilverStripe Security Repository",
    "homepage": "http://localhost/satis-security/public",
    "repositories": {
        "installer": {
            "type": "vcs",
            "url": "https://github.com/silverstripe/silverstripe-installer.git"
        },
        "framework": {
            "type": "vcs",
            "url": "https://github.com/silverstripe-security/silverstripe-framework.git"
        }
    },
    "require": {
		"silverstripe/installer": "^3.5 || ^4",
		"silverstripe/framework": "^3.5 || ^4"
	},
    "require-all": true
}
```

* Build the repository:
  `satis build config.json ./public`
* Test you can view the satis home page at `http://localhost/satis-security/public/`
* When performing the release ensure you use `--repository=http://localhost/satis-security/public` (below)

<div class="warning" markdown="1">
It's important that you re-run `satis build` step after EVERY change that is pushed upstream; E.g. between
each release, if making multiple releases.
</div>

### Detailed CVE and CVSS Guidance

 * In the [CVE Request Form](https://cveform.mitre.org/), we follow certain conventions on fields:
   * Request with the `security@silverstripe.org` group email
   * **Vendor of the product(s):** SilverStripe
   * **Affected product(s)/code base - Product:** Composer module name (e.g. `silverstripe/framework`).
     Indirectly affected dependencies of the module should not be listed here.
   * **Affected product(s)/code base - Version:** Use Composer constraint notation,
     with one entry per major release line.
     Example for an issue affecting all framework releases: First line `^3.0`, second line `^4.0`.
     We don't know the target release version at this point, so can't set an upper constraint yet.
     It should include all affected versions, including any unsupported release lines.
   * **Affected component(s):** E.g. ORM, Template layer
   * **Suggested description of the vulnerability:** Keep this short, usually the issue title only.
     We want to retain control over editing the description in our own systems without going
     through lengthy CVE change processes.
   * **Reference(s):** Leave this blank. We'll send through a "Link to the advisory" as part of the publication request

## Standard release process

The release process, at a high level, involves creating a release, publishing it, and
reviewing the need for either another pre-release or a final stable tag within a short period
(normally within 3-5 business days).

When creating a new pre-release or stable, the following process is broken down into two
main sets of commands:

### Stage 1: Release preparation:

If you are managing a release, it's best to first make sure that SilverStripe marketing
are aware of any impending release. This is so that they can ensure that a relevant blog
post will appear on [www.silverstripe.org/blog](http://www.silverstripe.org/blog/), and
cross-posted to other relevant channels such as social media.
Blog posts should be prepared for each major, minor and security releases.
Patch releases, alphas, betas and release candidates usually don't need blog posts,
unless they're introducing important changes (e.g. for a new major release). 
Sending an email to [marketing@silverstripe.com](mailto:marketing@silverstripe.com)
with an overview of the release and a rough release timeline.

Check all tickets assigned to that milestone are either closed or reassigned to another milestone.
Use the [list of all issues across modules](https://www.silverstripe.org/community/contributing-to-silverstripe/github-all-core-issues)
as a starting point, and add a `milestone:"your-milestone"` filter.

Merge up from other older [supported release branches](release-process#supported-versions) (e.g. merge `4.0`->`4.1`, `4.1`->`4.2`, `4.2`->`4`, `4`->`master`).

This is the part of the release that prepares and tests everything locally, but
doe not make any upstream changes (so it's safe to run without worrying about
any mistakes migrating their way into the public sphere).

Invoked by running `cow release` in the format as below:

`cow release <version> [recipe] -vvv`

E.g.

`cow release 4.0.1 -vvv`

* `<version>` The recipe version that is to be released. E.g. `4.1.4` or `4.3.0-rc1`
* `<recipe>` `Optional: the recipe that is being released (default: "silverstripe/installer")

This command has these options (note that --repository option is critical for security releases):

* `-vvv` to ensure all underlying commands are echoed
* `--directory <directory>` to specify the folder to create or look for this project in. If you don't specify this,
it will install to the path specified by `./release-<version>` in the current directory.
* `--repository <repository>` will allow a custom composer package url to be specified. E.g. `http://packages.cwp.govt.nz`
  See the above section "Setting up satis for hosting private security releases" on how to prepare a custom
  repository for a security release.
* `--branching <type>` will specify a branching strategy. This allows these options:
  * `auto` - Default option, will branch to the minor version (e.g. 1.1) unless doing a non-stable tag (e.g. rc1)
  * `major` - Branch all repos to the major version (e.g. 1) unless already on a more-specific minor version.
  * `minor` - Branch all repos to the minor semver branch (e.g. 1.1)
  * `none` - Release from the current branch and do no branching.
* `--skip-tests` to skip tests
* `--skip-i18n` to skip updating localisations

This can take between 5-15 minutes, and will invoke the following steps,
each of which can also be run in isolation (in case the process stalls
and needs to be manually advanced):

* `release:create` The release version will be created in the `release-<version>`
  folder directly underneath the folder this command was invoked in. Cow
  will look at the available versions and branch-aliases of silverstripe/installer
  to determine the best version to install from. E.g. installing 4.0.0 will
  know to install dev-master, and installing 3.3.0 will install from 3.x-dev.
  If installing pre-release versions for stabilisation, it will use the correct
  temporary release branch.
* `release:plan` The release planning will take place, this reads the various dependencies of the recipe being released
  and determines what new versions of those dependencies need to be tagged to create the final release. Note that
  the patch version numbers of each module may differ. This step requires the latest versions to be released are
  determined and added to the plan. The conclusion of the planning step is output to the screen and requires user
  confirmation.
* `release:branch` If release:create installed from a non-rc branch, it will
  create the new temporary release branch (via `--branch-auto`). You can also customise this branch
  with `--branch=<branchname>`, but it's best to use the standard.
* `release:translate` All upstream transifex strings will be pulled into the
  local master strings, and then the [i18nTextCollector](api:SilverStripe\i18n\TextCollection\i18nTextCollector)
  task will be invoked and will merge these strings together, before pushing all
  new master strings back up to transifex to make them available for translation.
  Changes to these files will also be automatically committed to git.
* `release:test` Will run all unit tests on this release. Make sure that you
  setup your `.env` correctly (as above) so that this will work.
* `release:changelog` Will compare the current branch head with `--from` parameter
  version in order to generate a changelog file. This wil be placed into the
  `./framework/docs/en/04_Changelogs/` folder. If an existing file named after
  this version is already in that location, then the changes will be automatically
  regenerated beneath the automatically added line:
  `<!--- Changes below this line will be automatically regenerated -->`.
  It may be necessary to edit this file to add details of any upgrading notes
  or special considerations. If this is a security release, make sure that any
  links to the security registrar (http://www.silverstripe.org/download/security-releases)
  match the pages saved in draft.

Once the release task has completed, it may be ideal to manually test the site out
by running it locally (e.g. `http://localhost/release-3.3.4`) to do some smoke-testing
and make sure that there are no obvious issues missed.

Since `cow` will only run the unit test suite, you'll need to check
the build status of Behat end-to-end tests manually on travis-ci.org.
Check the badges on the various modules available on [github.com/silverstripe](http://github.com/silverstripe).

It's also ideal to eyeball the Git changes generated by the release tool, making sure
that no translation strings were unintentionally lost, and that the changelog was generated correctly.

In particular, double check that all necessary information is included in the release notes,
including:

* Upgrading notes
* Security fixes included
* Major changes

Before publication, ensure that the release plan has been peer reviewed by another member of the core team.

Once this has been done, then the release is ready to be published live.

### Stage 2: Release publication

Once a release has been generated, has its translations updated, changelog generated,
and tested, the next step is to publish the release. This involves tagging,
building an archive, and uploading to
[www.silverstripe.org](http://www.silverstripe.org/software/download/) download page.

Invoked by running `cow release:publish` in the format as below:

`cow release:publish <version> [<recipe>] -vvv`

E.g.

`cow release:publish 4.0.1 silverstripe/installer`

This command has these options:

* `-vvv` to ensure all underlying commands are echoed
* `--directory <directory>` to specify the folder to look for the project created in the prior step. As with
  above, it will be guessed if omitted. You can run this command in the `./release-<version>` directory and
  omit this option.
* `--aws-profile <profile>` to specify the AWS profile name for uploading releases to s3. Check with
  damian@silverstripe.com if you don't have an AWS key setup.
* `--skip-archive-upload` to disable both "archive" and "upload". This is useful if doing a private release and
  you don't want to upload this file to AWS.
* `--skip-upload` to disable the "upload" command (but not archive)

As with the `cow release` command, this step is broken down into the following
subtasks which are invoked in sequence:

* `release:tag` Each module will have the appropriate tag applied (except the theme). All tags are pushed up to origin
  on github.
* `release:archive` This will generate a new tar.gz and zip archive, each for
  cms and framework-only installations. These will be copied to the root folder
  of the release directory, although the actual build will be created in temporary
  directories (so any temp files generated during testing will not end up in the release).
  If the tags generated in the prior step are not yet available on packagist (which can
  take a few minutes at times) then this task will cycle through a retry-cycle,
  which will re-attempt the archive creation periodically until these tags are available.
* `release:upload` This will invoke the AWS CLI command to upload these archives to the
  s3 bucket `silverstripe-ssorg-releases`. If you have setup your AWS profile
  for silverstripe releases under a non-default name, you can specify this profile
  on the command line with the `--aws-profile=<profile>` command.
  See "Stage 3: Let the world know" to check if this worked correctly.

Once all of these commands have completed there are a couple of final tasks left that
aren't strictly able to be automated:

* It will be necessary to perform a post-release merge
  on open source. This normally will require you to merge the temporary release branch into the
  source branch (e.g. merge 3.2.4 into 3.2), or sometimes create new branches if
  releasing a new minor version, and bumping up the branch-alias in composer.json.
  E.g. branching 3.3 from 3, and aliasing 3 as 3.4.x-dev. You can then delete
  the temporary release branches. This will need to be done before updating the
  release documentation in stage 3.
* Merging up the changes in this release to newer branches, following the
  SemVer pattern (e.g. 3.2.4 > 3.2 > 3.3 > 3 > master). The more often this is
  done the easier it is, but this can sometimes be left for when you have
  more free time. Branches not receiving regular stable versions anymore (e.g.
  3.0 or 3.1) can be omitted.
* Set the github milestones to completed, and create placeholders for the next
  minor versions. It may be necessary to re-assign any issues assigned to the prior
  milestones to these new ones.
* Make sure that the [releases page](https://github.com/silverstripe/silverstripe-installer/releases)
  on github shows the new tag.

*Updating non-patch versions*

If releasing a new major or minor version it may be necessary to update various SilverStripe portals. Normally a new
minor version will require a new branch option to be made available on each site menu. These sites include:

* [docs.silverstripe.org](https://docs.silverstripe.org):
  * New branches (minor releases) require a code update. Changes are made to
    [github](https://github.com/silverstripe/doc.silverstripe.org) and deployed via
    [SilverStripe Platform](https://platform.silverstripe.com/naut/project/SS-Developer-Docs/environment/Production/)
  * The new version needs to be added to `app/_config/docs-repositories.yml`
  * Update the version for the "contributing" rewrite rule in `.htaccess` (`RewriteRule ^(.*)/(.*)/contributing/?(.*)?$ ...`)
  * Updates to markdown only can be made via the [build tasks](https://docs.silverstripe.org/dev/tasks).
    See below for more details.
* [userhelp.silverstripe.org](https://userhelp.silverstripe.org/en/3.2):
  * Updated similarly to docs.silverstripe.org: Code changes are made to
    [github](https://github.com/silverstripe/userhelp.silverstripe.org) and deployed via
    [SilverStripe Platform](https://platform.silverstripe.com/naut/project/SS-User-Docs/environment/Production/).
  * The content for this site is pulled from [silverstripe-userhelp-content](https://github.com/silverstripe/silverstripe-userhelp-content)
  * Updates to markdown made via the [build tasks](https://userhelp.silverstripe.org/dev/tasks).
    See below for more details.
* [demo.silverstripe.org](http://demo.silverstripe.org/): Update code on
  [github](https://github.com/silverstripe/demo.silverstripe.org/)
  and deployed via [SilverStripe Platform](https://platform.silverstripe.com/naut/project/ss3demo/environment/live).
* [api.silverstripe.org](https://api.silverstripe.org): Update on [github](https://github.com/silverstripe/api.silverstripe.org)
  and deployed via [SilverStripe Platform](https://platform.silverstripe.com/naut/project/api/environment/live). Currently
  the only way to rebuild the api docs is via SSH in and running the apigen task.

Further manual work on major or minor releases:

 * Check that `Deprecation::notification_version('4.0.0');` in framework/_config.php points to
the right major version. This should match the major version of the current release. E.g. all versions of 4.x
should be set to `4.0.0`.
 * Update the [userhelp.silverstripe.org](userhelp.silverstripe.org) version link in `LeftAndMain.help_links`

*Updating markdown files*

When updating markdown on sites such as userhelp.silverstripe.org or docs.silverstripe.org, the process is similar:

* Run `RefreshMarkdownTask` to pull down new markdown files.
* Then `RebuildLuceneDocsIndex` to update search indexes.

Running either of these tasks may time out when requested, but will continue to run in the background. Normally
only the search index rebuild takes a long period of time.

Note that markdown is automatically updated daily, and this should only be done if an immediate refresh is necessary.

### Stage 3: Let the world know

Once a release has been published there are a few places where user documentation
will need to be regularly updated.

* Make sure that the [download page](http://www.silverstripe.org/download) on
  silverstripe.org has the release available. If it's a stable, it will appear
  at the top of the page. If it's a pre-release, it will be available under the
  [development builds](http://www.silverstripe.org/download#download-releases)
  section. If it's not available, you might need to check that the release was
  properly uploaded to aws s3, or that you aren't viewing a cached version of
  the download page. You can cache-bust this by adding `?release=<version>` to
  the url. If things aren't working properly (and you have admin permissions)
  you can run the [CoreReleaseUpdateTask](http://www.silverstripe.org/dev/tasks/CoreReleaseUpdateTask)
  to synchronise with packagist.
* Ensure that [docs.silverstripe.org](http://docs.silverstripe.org) has the
  updated documentation and the changelog link in your announcement works.
* Announce the release on the ["Releases" forum](https://forum.silverstripe.org/c/releases).
  Needs to happen on every minor release for previous releases, see [supported versions](https://docs.silverstripe.org/en/4/contributing/release_process/#supported-versions)
* Announce any new EOLs for minor versions on the ["Releases" forum](https://forum.silverstripe.org/c/releases).
* Update the [roadmap](https://www.silverstripe.org/roadmap) with new dates for EOL versions ([CMS edit link](https://www.silverstripe.org/admin/pages/edit/EditForm/3103/field/TableComponentItems/item/670/edit))
* Update the [Slack](https://www.silverstripe.org/community/slack-signup/) topic to include the new release version.
* For major or minor releases: Work with SilverStripe marketing to get a blog post out.
  They might choose to announce the release on social media as well. 
* If the minor or major release includes security fixes, follow the publication instructions in the [Security Release Process](#security-release-process) section.

## See also

* [Release Process](release_process)
* [Translation Process](translation_process)
* [Core committers](core_committers)

If at any time a release runs into an unsolveable problem contact the
core committers on the [discussion group](https://groups.google.com/forum/#!forum/silverstripe-committers)
to ask for support.
