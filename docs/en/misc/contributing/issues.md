# Contributing Issues and Opinions

[« Back to Contributing page](../contributing)

## Reporting Bugs

If you have discovered a bug in SilverStripe, we'd be glad to hear about it -
well written bug reports can be half of the solution already!

 * [Framework Bugtracker](https://github.com/silverstripe/silverstripe-framework/issues)
 * [CMS Bugtracker](https://github.com/silverstripe/silverstripe-cms/issues)
 * [Documentation Bugtracker](https://github.com/silverstripe/silverstripe-framework/issues)
 * Search on [http://silverstripe.org/modules](http://silverstripe.org/modules) for module-specific bugtrackers

Before submitting a bug:

 * Ask for assistance on the [forums](http://silverstripe.org/forums), [core mailinglist](http://groups.google.com/group/silverstripe-dev) or on [IRC](http://silverstripe.org/irc) if you're unsure if its really a bug.
 * Search for similar, existing tickets
 * Is this a security issue? Please follow our separate reporting guidelines below.
 * Is this a issue with the core framework or cms? Modules have their own issue trackers (see [silverstripe.org/modules](http://www.silverstripe.org/modules))
 * Try to reproduce your issue on a [clean installation](http://doc.silverstripe.org/framework/en/installation/composer#using-development-versions), maybe the bug has already been fixed on an unreleased branch?
 * The bugtracker is not the place to discuss enhancements, please use the forums or mailinglist.
   Only log enhancement tickets if they gather a large interest in the community
   and the enhancement is likely to be implemented in the next couple of months.

If the issue does look like a new bug:

 * [Create a new ticket](https://github.com/silverstripe/silverstripe-framework/issues/new)
 * Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots and screencasts can help here.
 * Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, Operating System, any installed SilverStripe modules.
 * *(optional)* [Submit a pull request](/misc/contributing/code) which fixes the issue.

Ensure you give us enough information to diagnose your issue:

 * Switch your site to "[dev mode](/topics/debugging)". Paste any PHP errors with their stacktraces. A generic "Server Error" message is not enough information.
 * If you suspect a JavaScript or CSS bug, check if it appears in other browsers
 * Use the [Chrome dev tools](https://developers.google.com/chrome-developer-tools/docs/overview) or [Firefox dev tools](https://developer.mozilla.org/en-US/docs/Tools)
 * Use the JavaScript console in your browser to determine if any errors happened there, and paste the complete info into issue description.
 * Use the "Network" panel to determine if any XHR ("Ajax") requests have returned errors, and paste the HTTP headers as well as HTTP response body into the issue description.

Lastly, don't get your hopes up too high. Unless your issue is a blocker affecting a large
number of users, don't expect SilverStripe developers to jump onto it right away.
Your issue is a starting point where others with the same problem can collaborate
with you to develop a fix. 

## Feature Requests

<div class="warning" markdown='1'>
Please don't file "feature requests" as issues. If there's a new feature you'd like to see
in SilverStripe, you either need to write it yourself (and [submit a pull request](/misc/contributing/code))
or convince somebody else to write it for you. Any "wishlist" type issues without code attached
can be expected to be closed as soon as they're reviewed.
</div>

In order to gain interest and feedback in your feature, we encourage you to present
it to the community through the [forums](http://silverstripe.org/forums), [core mailinglist](http://groups.google.com/group/silverstripe-dev) or on [IRC](http://silverstripe.org/irc).

## Reporting Security Issues

Report security issues to [security@silverstripe.com](mailto:security@silverstripe.com). See our "[Release Process](release-process)" documentation for more info, and read our guide on [how to write secure code](/topics/security).

## Sharing your Opinion

*  [silverstripe.org/forums](http://silverstripe.org/forums): Forums on silverstripe.org
*  [silverstripe-dev](http://groups.google.com/group/silverstripe-dev): Core development mailinglist
*  [silverstripe-documentation](http://groups.google.com/group/silverstripe-documentation): Documentation team mailing list
* [silverstripe-documentation](http://groups.google.com/group/silverstripe-translators): Translation team mailing list
