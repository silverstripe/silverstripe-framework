title: Making a SilverStripe core release
summary: Development guide for core contributors to build and publish a new release 

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

* PHP 5.5+
* Python 2.7 / 3.5
* [cow release tool](https://github.com/silverstripe/cow#install). This should typically
  be installed in a global location via the below command. Please see the installation
  docs on the cow repo for more setup details.
  - `composer global require silverstripe/cow dev-master`
* [transifex client](http://docs.transifex.com/client/). You will also need to ensure that
  your transifex account has been granted the necessary write permissions on the cms, framework,
  installer, simple theme, siteconfig and reports modules:
  - `pip install transifex-client`
* [AWS CLI tools](https://aws.amazon.com/cli/):
  - `pip install awscli`
* The `tar` and `zip` commands
* A good _ss_environment.php setup in your localhost webroot.

Example `_ss_environment.php`:

    :::php
    <?php
    // Environent
    define('SS_TRUSTED_PROXY_IPS', '*');
    define('SS_ENVIRONMENT_TYPE', 'dev');
    
    // DB Credentials
    define('SS_DATABASE_CLASS', 'MySQLDatabase');
    define('SS_DATABASE_SERVER', '127.0.0.1');
    define('SS_DATABASE_USERNAME', 'root');
    define('SS_DATABASE_PASSWORD', '');
    
    // Each release will have its own DB
    define('SS_DATABASE_CHOOSE_NAME', true);
    
    // So you can test releases
    define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
    define('SS_DEFAULT_ADMIN_PASSWORD', 'password');
    
    // Basic CLI hostname
    global $_FILE_TO_URL_MAPPING;
    $_FILE_TO_URL_MAPPING[__DIR__] = "http://localhost";


You will also need to be assigned the following permissions. Contact one of the SS staff from
the [core committers](core_committers), who will assist with setting up your credentials.

* Write permissions on the [silverstripe](https://github.com/silverstripe) and
  [silverstripe-labs](https://github.com/silverstripe-labs) organisations.
* Moderator permissions on the [community forum](http://www.silverstripe.org/community/forums/releases-and-announcements/).
* Admin permissions on [transifex](https://www.transifex.com/silverstripe/).
* AWS write permissions on the `silverstripe-ssorg-releases` s3 bucket.
* Permission on [silverstripe release announcement](https://groups.google.com/forum/#!forum/silverstripe-announce).
* Moderator permissions in the #silverstripe [IRC channel](http://www.silverstripe.org/community/contributing-to-silverstripe/irc-channel/)
* Administrator account on [docs.silverstripe.org](https://docs.silverstripe.org) and
  [userhelp.silverstripe.org](https://userhelp.silverstripe.org).

### First time setup: Security releases

For doing security releases the following additional setup tasks are necessary:

* Write permissions on the [silverstripe-security](https://github.com/silverstripe-security)
  organisation.
* Permission granted on the [open source security JIRA](https://silverstripe.atlassian.net/secure/RapidBoard.jspa?rapidView=198&view=detail)
* Permissions to write to the [security releases page](http://www.silverstripe.org/download/security-releases)
  and the [silverstripe.org cms](http://www.silverstripe.org/admin).
* Permission on [security pre-announcement mailing list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).

## Security release process

When doing a security release, typically one or more (or sometimes all) of the below
steps will need to be performed manually. As such, this guide should not be followed
exactly the same for these.

Standard practice is to produce a pre-release for any patched modules on the security 
forks for cms and framework (see [silverstripe-security](https://github.com/silverstripe-security)).

<div class="warning" markdown="1">
Security issues are never disclosed until a public stable release containing this fix
is available, or within a reasonable period of time of such a release.
</div>

Producing a security fix follows this general process:

* When a security issue is disclosed on security@silverstripe.com it should be given
  a CVE (common vulnerability exposure) code. E.g. ss-2015-020. Make sure you thank
  anyone who disclosed this issue, and confirm with them as soon as possible whether
  this issue is a verified security issue.
* Log this CVE, along with description, release version, and name of reporter in
  JIRA at [open source security jira](https://silverstripe.atlassian.net/secure/RapidBoard.jspa?rapidView=198&view=detail).
* Create a similar record of this issue on the [security releases page](http://www.silverstripe.org/download/security-releases)
  in draft mode.
* Post a pre-announcement to the [security pre-announcement list](https://groups.google.com/a/silverstripe.com/forum/#!forum/security-preannounce).
  It's normally ideal to include a [VCSS](https://nvd.nist.gov/CVSS-v2-Calculator)
  (common vulnerability scoring system) along with this pre-announcement. If the
  release date of the final stable is not known, then it's ok to give an estimated
  release schedule.
* Push the current upstream target branches (e.g. 3.2) to the corresponding security fork
  to a new branch named for the target release (e.g. 3.2.4). Security fixes should be 
  applied to this branch only. Once a fix (or fixes) have been applied to this branch, then
  a tag can be applied, and a private release can then be developed in order
  to test this release.
* Once release testing is completed and the release is ready for stabilisation, then these fixes
  can then be pushed to the upstream module fork, and the release completed as per normal.
  Make sure to publish any draft security pages at the same time as the release is published (same day).
* After the final release has been published, close related JIRA issues 
  at [open source security jira](https://silverstripe.atlassian.net/secure/RapidBoard.jspa?rapidView=198&view=detail)

<div class="warning" markdown="1">
Note: It's not considered acceptable to disclose any security vulnerability until a fix exists in
a public stable, not an RC or dev-branch. Security warnings that do not require a stable release
can be published as soon as a workaround or usable resolution exists.
</div>

## Standard release process

The release process, at a high level, involves creating a release, publishing it, and 
reviewing the need for either another pre-release or a final stable tag within a short period
(normally within 3-5 business days).

During the pre-release cycle a temporary branch is created, and should only receive
absolutely critical fixes during the cycle. Any changes to this branch should
result in the requirement for a new release, thus a higher level of scrutiny is typically
placed on any pull request to these branches.

When creating a new pre-release or stable, the following process is broken down into two
main sets of commands:

### Stage 1: Release preparation:

If you are managing a release, it's best to first make sure that SilverStripe marketing
are aware of any impending release. This is so that they can ensure that a relevant blog
post will appear on [www.silverstripe.org/blog](http://www.silverstripe.org/blog/), and
cross-posted to other relevant channels such as Twitter and Facebook.
Sending an email to [marketing@silverstripe.com](mailto:marketing@silverstripe.com)
with an overview of the release and a rough release timeline.

Check all tickets ([framework](https://github.com/silverstripe/silverstripe-framework/milestones), 
[cms](https://github.com/silverstripe/silverstripe-cms/milestones), 
[installer](https://github.com/silverstripe/silverstripe-installer/milestones)) assigned to that milestone are 
either closed or reassigned to another milestone.

Merge up from other older supported release branches (e.g. merge `3.1`->`3.2`, `3.2`->`3.3`, `3.3`->`3`, `3`->`master`).

This is the part of the release that prepares and tests everything locally, but
doe not make any upstream changes (so it's safe to run without worrying about
any mistakes migrating their way into the public sphere).

Invoked by running `cow release` in the format as below:
 
    cow release <version> --from=<prior-version> --branch-auto -vvv

This command has the following parameters:

* `<version>` The version that is to be released. E.g. 3.2.4 or 3.2.4-rc1
* `<prior-version>` The version from which to compare to generate a changelog.
  E.g. 3.2.3 (if releasing 3.2.4), or 3.2.5 (if releasing 3.3.0 and that's the
  newest 3.2.x version). You can normally leave this out for patch releases,
  and the code will normally be able to guess the right version, but you may
  as well declare it every time.
* `--branch-auto` Will automatically create a new temporary release branch (e.g. 3.2.4) if
  one does not exist.

This can take between 5-15 minutes, and will invoke the following steps,
each of which can also be run in isolation (in case the process stalls
and needs to be manually advanced):

* `realease:create` The release version will be created in the `release-<version>`
  folder directly underneath the folder this command was invoked in. Cow
  will look at the available versions and branch-aliases of silverstripe/installer
  to determine the best version to install from. E.g. installing 4.0.0 will
  know to install dev-master, and installing 3.3.0 will install from 3.x-dev.
  If installing pre-release versions for stabilisation, it will use the correct
  temporary release branch.
* `release:branch` If release:create installed from a non-rc branch, it will
  create the new temporary release branch (via `--branch-auto`). You can also customise this branch
  with `--branch=<branchname>`, but it's best to use the standard.
* `release:translate` All upstream transifex strings will be pulled into the
  local master strings, and then the [api:i18nTextCollector] task will be invoked
  and will merge these strings together, before pushing all new master strings
  back up to transifex to make them available for translation. Changes to these
  files will also be automatically committed to git.
* `release:test` Will run all unit tests on this release. Make sure that you
  setup your `_ss_environment.php` correctly (as above) so that this will work.
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
the build status of Behat end-to-end tests manually on travis-ci.org
for the various modules (e.g. [framework](https://travis-ci.org/silverstripe/silverstripe-framework))
and [cms](https://travis-ci.org/silverstripe/silverstripe-cms)).

It's also ideal to eyeball the git changes generated by the release tool, making sure
that no translation strings were unintentionally lost, no malicious changes were
introduced in the (community contributed) translations, and that the changelog
was generated correctly.

In particular, double check that all necessary information is included in the release notes,
including:

* Upgrading notes
* Security fixes included
* Major changes

Once this has been done, then the release is ready to be published live.

### Stage 2: Release publication

Once a release has been generated, has its translations updated, changelog generated,
and tested, the next step is to publish the release. This involves tagging,
building an archive, and uploading to
[www.silverstripe.org](http://www.silverstripe.org/software/download/) download page.

Invoked by running `cow release:publish` in the format as below:

    cow release:publish <version> -vvv

As with the `cow release` command, this step is broken down into the following
subtasks which are invoked in sequence:

* `release:tag` Each module will have the appropriate tag applied (except the theme).
* `release:push` The temporary release branches and all tags are pushed up to origin on github.
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

Once all of these commands have completed there are a couple of final tasks left that
aren't strictly able to be automated:

* If this is a stable release, it will be necessary to perform a post-release merge
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
  3.0 or 3.1) should usually be omitted.
* Set the github milestones to completed, and create placeholders for the next
  minor versions. It may be necessary to re-assign any issues assigned to the prior
  milestones to these new ones. See the below links for each module milestone list:
   * [installer](https://github.com/silverstripe/silverstripe-installer/milestones)
   * [framework](https://github.com/silverstripe/silverstripe-framework/milestones)
   * [cms](https://github.com/silverstripe/silverstripe-cms/milestones)
* Make sure that the [releases page](https://github.com/silverstripe/silverstripe-installer/releases)
  on github shows the new tag.

*Updating non-patch versions*

If releasing a new major or minor version it may be necessary to update various SilverStripe portals. Normally a new
minor version will require a new branch option to be made available on each site menu. These sites include:

* [docs.silverstripe.org](https://docs.silverstripe.org):
  * New branches (minor releases) require a code update. Changes are made to
    [github](https://github.com/silverstripe/doc.silverstripe.org) and deployed via
    [SilverStripe Platform](https://platform.silverstripe.com/naut/project/SS-Developer-Docs/environment/Production/)
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

It's also a good idea to check that `Deprecation::notification_version('4.0.0');` in framework/_config.php points to
the right major version. This should match the major version of the current release. E.g. all versions of 4.x
should be set to `4.0.0`.

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
  updated documentation by running the build task in the root folder. If
  you do not have ssh access to this server, then contact a SilverStripe staff member
  to update this for you. Make sure that the download link below links to the
  correct changelog page. E.g.
  [https://docs.silverstripe.org/en/3.2/changelogs/3.2.1/](https://docs.silverstripe.org/en/3.2/changelogs/3.2.1/)
* Post a release announcement on the [silverstripe release announcement](https://groups.google.com/forum/#!forum/silverstripe-announce)
  google group.
* Create a release announcement forum sticky on the
  [releases and announcements](http://www.silverstripe.org/community/forums/releases-and-announcements/)
  forum category. Make this a global read-only sticky, and un-sticky any older release.
* Update the #silverstripe [IRC](https://www.silverstripe.org/community/contributing-to-silverstripe/irc-channel/) topic to include the new release version.

### Stage 4: Web platform installer release

The web platform installer is available [on the web app gallery](http://www.microsoft.com/web/gallery/silverstripecms.aspx).

In order to update this you will need a Microsoft live account, and have it authorised
by SilverStripe staff in order to publish these releases.


To update this release there is an additional download tool at 
`[https://code.platform.silverstripe.com/silverstripe/webpi](https://code.platform.silverstripe.com/silverstripe/webpi)`
which will guide you through the process of generating a new zip release.

    ./make-package 3.2.4 3.2.4
    aws s3 cp ./silverstripe-3.2.4-webpi.zip s3://silverstripe-ssorg-releases/sssites-ssorg-prod/assets/downloads/webpi/silverstripe-3.2.4-webpi.zip --acl public-read --profile silverstripe

Once you have a new release, update the necessary details at 
[http://www.microsoft.com/web/gallery/appsubmit.aspx?id=57](http://www.microsoft.com/web/gallery/appsubmit.aspx?id=57)
to submit a new version, including:
 
* Set the version number
* Update the release date
* Submit the package URL
* Submit the package SHA

## See also

* [Release Process](release_process)
* [Translation Process](translation_process)
* [Core committers](core_committers)
* [WebPI Installer](https://docs.silverstripe.org/en/getting_started/installation/other_installation_options/windows_platform_installer/)

If at any time a release runs into an unsolveable problem contact the
core committers on the [discussion group](https://groups.google.com/forum/#!forum/silverstripe-committers)
to ask for support.
