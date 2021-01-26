---
title: Maintainer guidelines
summary: Cheat sheet for module maintainers
iconBrand: wpforms
---


# Maintainer Guidelines

This doc is a cheat sheet for maintainers. It explains what that means to be a module maintainer and contains links to other resources with extra information.

Module maintainers are people taking care of the repositories, CI, documentation, source code, conventions, community communications, issue triage, and release management.

One of the most important maintainer responsibilities is to collaborate with other maintainers. Another important role is to facilitate community contributions (such as issue reports and pull requests).

[note]
A lot of extra information is available in Silverstripe CMS documentation section [“Contributing to Silverstripe”](./).
All maintainers should be familiar with those docs as they explain many details about how we work.
[/note]


## What is Silverstripe Core

Silverstripe CMS is powered by a system of components in the form of Composer packages. These packages will be categorised as either a _module_ or a _recipe_.

The "core" of Silverstripe refers to the module packages owned by the "silverstripe" Packagist vendor that fall under one of the following recipes:

* [silverstripe/recipe-core](https://github.com/silverstripe/recipe-cms)
* [silverstripe/recipe-cms](https://github.com/silverstripe/recipe-cms)
* [silverstripe/installer](https://github.com/silverstripe/silverstripe-installer)

## What are Supported Modules

In addition to Silverstripe Core, there are many [Supported Modules](https://www.silverstripe.org/software/addons/supported-modules-definition/)
which have the backing of Silverstripe Ltd. While it's a good idea to apply the rules outlined in this document,
work on these modules is guided by the 
[Supported Modules Standard](https://www.silverstripe.org/software/addons/supported-modules-definition/).
Commit access in Supported Modules is handled by agreement of the repository maintainers,
or any additional guidelines outlined via `README` and `CONTRIBUTING` files.


## Maintainer Roles

### Core Committers

The people looking after the Silverstripe Core modules.
See the details on the [Core Committers](./core_committers) page.

#### House rules of the Core Committer team

 * Be friendly, encouraging and constructive towards other community members
 * Frequently review pull requests and new issues (in particular, respond quickly to @mentions)
 * Treat issues according to our [issue guidelines](issues_and_bugs), and use the [triage resources](triage_resources)
 * Don't commit directly to a release branch, raise pull requests instead (except trivial fixes)
 * Only merge code you have tested and fully understand. If in doubt, ask for a second opinion.
 * Follow the [Supported Modules Standard](https://www.silverstripe.org/software/addons/supported-modules-definition/)
 * Ensure contributions have appropriate [test coverage](../developer_guides/testing), are documented, and pass our [coding conventions](/getting_started/coding_conventions)
 * Keep the codebase "releasable" at all times (check our [release process](release_process))
 * Follow [Semantic Versioning](code/#picking-the-right-version) by putting any changes into the correct branch
 * API changes and non-trivial features should not be merged into release branches. 
 * API changes on master should not be merged until they have the buy-in of at least two Core Committers (or better, through the [core mailing list](https://groups.google.com/forum/#!forum/silverstripe-dev))
 * Be inclusive. Ensure a wide range of SilverStripe developers can obtain an understanding of your code and docs, and you're not the only one who can maintain it.
 * Avoid `git push --force`, and be careful with your git remotes (no accidental pushes)
 * Use your own forks to create feature branches
 * We release using the standard process. See the [Making a SilverStripe Core Release](making_a_silverstripe_core_release)


### Contributing Committers

Beyond the Core Committer role, there can be individuals which
focus on core development work - typically sponsored through full-time product development roles by SilverStripe Ltd.
These Contributing Committers require write access to core repositories to maintain their pace,
often working alongside Core Committers. They are guided by additional rules:

 * Contributing Committers have write access to core repositories in order to work effectively with Github issues. They are expected to use those permissions with good judgement regarding merges of pull requests.
 * Complex or impactful changes need to be reviewed and approved by one or more Core Committers. This includes any additions, removals or changes to commonly used APIs. If that's not possible in the team, ping `@silverstripe/core-team` to get other Core Committers involved.
 * For these complex or impactful changes, Core Committers should be given 1-2 working days to review. Ideally at this point, the API has already been agreed on through issue comments outlining the planned work (see [RFC Process](request_for_comment]).
 * More straightforward changes (e.g. documentation, styling) or areas which require quite specialised expertise (e.g. React) that's less available through most Core Committers can be approved or merged by team members who aren't Core Committers.
 * Self-merges should be avoided, but are preferable to having work go stale or forcing other team members to waste time by context switching into a complex review (e.g. because the original reviewer went on leave). Any self-merge should be accompanied by a comment why this couldn't be handled in another way, and a (preferably written) approval from another team member.

This role may be granted by any Core Committer,
who should give other Core Committers an opportunity to weigh in on the decision.


### Triage

Triage of issues and pull request is an important activity: Reviewing issues, adding labels,
closing stale issues, etc. This does not require write access to the repository as a "Contributing Committer".
This is a great way for active community members to help out, and start a path towards becoming a Core Committer.

Triage roles may be granted by any Core Committer,
who should give other Core Committers an opportunity to weigh in on the decision.


## The Guidelines

With great power (write access) comes great responsibility.

First and foremost rule of a maintainer is to collaborate with other maintainers. Follow the house rules and remember that others also care about the modules they maintain.

Remember to follow the [House rules of the Core Committer team](#house-rules-of-the-core-committer-team) and the supported module [guidelines](https://www.silverstripe.org/software/addons/supported-modules-definition/).

[warning]
Some of the Supported Modules have their own house rules (usually explained in the repository README or CONTRIBUTING files). Please remember to read those carefully.
[/warning]


### How to triage

Triaging issues and pull requests involves review, applying labels, as well as closing invalid submissions.

Applying [labels](./code/#labels) is the main mechanism used to escalate critical and high impact issues, give feedback to the community, or peer review a PR and let other maintainers know it is “good to be merged on green CI tests”.


How to do it
  - Regularly check the repository for new issues
  - Ideally subscribe to the repository via [GitHub Watch](https://docs.github.com/en/free-pro-team@latest/github/managing-subscriptions-and-notifications-on-github/viewing-your-subscriptions#configuring-your-watch-settings-for-an-individual-repository) functionality
  - Read the docs about [how we use labels](./code/#labels)
  - If unsure about anything, it’s usually good to ask other maintainers for their opinions (on Slack or via mentioning them directly on GitHub)


### How to review pull requests

Merging PRs is one of the most critical responsibilities, which often requires a lot of effort and scrutiny.

Bad PRs may contain technical debt, provide problems in the future and require extra attention and time from the maintainers. So, it is usually better not to merge at all, rather than merge half-ready or poorly written code. Especially if a PR comes from a non-maintainer who’s not responsible for taking care of the feature later on. On the other hand, the nature of Open Source implies access to resources of the community, so it’s important to make sure we don’t close the doors for people willing to spend their time and brain energy.

How to do it
  - Follow the [merge checklist](./triage_resources/#merge-checklist). You may even post it straight on GitHub so the contributor sees the PR progress
  - Ensure you’re familiar with the module own conventions (explained in README or CONTRIBUTING files).
  - If the author disappears you may either take the PR over and push it forward yourself by adding your own commits to their branch. Otherwise, it’s fine to close their PR if they don’t respond for over several weeks.
