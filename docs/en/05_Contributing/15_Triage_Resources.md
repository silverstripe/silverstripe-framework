---
title: Triage resources
summary: Canned responses and other resources used during triage
icon: users
---

# Triage resources

This page collates common resources that maintainers can use to efficiently and consistently manage incoming issues and PRs.

## Merge Checklist

This list helps to ensure that PRs are in a good state before merging. Ideally it should be applied to the PR upon initial triage, so that the contributor can check items off prior to the reviewer digging in. Some items may not be relevant to every PR, and can be crossed out on a case-by-case basis.

* [ ] The target branch is [correct](https://docs.silverstripe.org/en/4/contributing/code/#picking-the-right-version)
* [ ] All commits are relevant to the change (e.g. no debug statements or arbitrary linting)
* [ ] The commit messages follow [the contribution guidelines](https://docs.silverstripe.org/en/4/contributing/code/#commit-messages)
* [ ] The patch follows [the contribution guidelines](https://docs.silverstripe.org/en/4/contributing/code/)
* [ ] New features are covered with tests (back-end with unit tests, front-end with Behat)
* [ ] Any relevant User Help / Developer documentation is updated; for impactful changes, information is added to the changelog for the intended release
* [ ] CI is green
* [ ] At least one peer reviewer approved; no outstanding changes requested

## Canned Responses

These are optional templates that can be [saved for re-use in GitHub](https://docs.github.com/en/github/writing-on-github/working-with-saved-replies), serving as a starting point for working through common scenarios on issues and pull requests. Explainers are provided below for each message to provide more context to affected contributors, and it often makes sense for the maintainer to expand upon the message with details specific to the given issue or PR.

### Stale PR warning

**Explainer:** In order to minimise the backlog of PRs that need attention from the core team, we periodically check in on PRs that haven't seen any author activity for a month or more. We'll give you a heads up, and you'll have 2 weeks to progress the work or respond to any outstanding feedback.

> This pull request hasn't had any activity for a while. Are you going to be doing further work on it, or would you prefer to close it now?

### Stale PR closure

**Explainer:** If we don't hear back or see any changes within the 2 week timeframe, we'll close the PR out.

> It seems like there's going to be no further activity on this pull request, so we’ve closed it for now. Please open a new pull-request if you want to re-approach this work, or for anything else you think could be fixed or improved.

### Enhancement Issue raised

**Explainer:** See the [linked documentation](https://docs.silverstripe.org/en/4/contributing/issues_and_bugs/#feature-requests) for details.

> Thanks for your suggestion! Just to let you know we're closing this feature request as GitHub issues are not the best place to discuss potential enhancements. You can read more about this in the [contributing guide](https://docs.silverstripe.org/en/4/contributing/issues_and_bugs/#feature-requests), and you are welcome to raise new feature ideas on the [Silverstripe forum](https://forum.silverstripe.org/c/feature-ideas) instead.

### Closing a CMS 3 issue

**Explainer:** We immediately close issues that only affect CMS 3 (or CMS 3 versions of supported modules) unless they are found to have a critical impact, as this version is in limited support and will reach end-of-life in the near future.

> Unfortunately [Silverstripe CMS 3 entered limited support in June 2018](https://www.silverstripe.org/blog/update-on-silverstripe-5-x/). This means we'll only be fixing critical bugs and security issues for CMS 3 going forward.
>
> See the [Silverstripe CMS Roadmap](https://www.silverstripe.org/software/roadmap/) for more information on our support commitments.

### Complex enhancement / new feature that doesn't fit core

**Explainer:** We generally try to avoid major additions to the core codebase, as they increase the maintenance burden on the core team. Instead, we recommend pursuing major new features / enhancements as independent modules, which is possible in most cases due to the broad extensibility of the core APIs.

Examples of high-value features that are developed as independent modules include [dnadesign/silverstripe-elemental](https://github.com/dnadesign/silverstripe-elemental), [symbiote/silverstripe-gridfieldextensions](https://github.com/symbiote/silverstripe-gridfieldextensions) and [jonom/silverstripe-focuspoint](https://github.com/jonom/silverstripe-focuspoint).

> Thanks for your work on this pull request. Unfortunately the core team isn't able to prioritise integrating and supporting complex new features at this time, so we’ll need to close this now.
>
> If you’d like to take your idea further and share it with the community, we highly recommend turning your PR into an open source module and posting about it in the appropriate community channels. See the docs on [how to create a module](https://docs.silverstripe.org/en/4/developer_guides/extending/modules/#create).
