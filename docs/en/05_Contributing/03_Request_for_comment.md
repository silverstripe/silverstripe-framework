# Request for comment (RFC)

## Why RFCs?
This is part of the SilverStripe core decision-making process and addresses the gap between the idea phase and the pull request submission (and merge).

The rationale behind this process is to:
 * Encourage visibility on decision making
 * Clarity on what the proposal is, its rationale and impact
 * Reduce unnecessary work when coding large features is done without community and core-committers buy-in
 * Improved likelihood of an optimal solution being merged

The important thing to understand about the RFCs is that these are NOT a way to request features. Rather, they are a way to bring clarity so people understand what change is being proposed.

## When to write an RFC?

We intend RFCs to be the primary mechanisms for proposing major new features, for collecting community input on an issue, and for documenting the design decisions that have gone into SilverStripe. The RFC author is responsible for building consensus within the community and documenting dissenting opinions.

Before writing the actual summary RFC document, the idea should have already had a wide range of discussion in various community communication channels. Once discussions reach a point where you think most of the difficulties have been worked through then create the RFC using the template provided.

The benefits of writing an RFC for non-trivial feature proposals are:
 * Obtaining a preliminary approval from core-committers on an architecture before code is completed, to mitigate the risk of a non-merge after a PR is submitted
 * Community becomes aware of incoming changes prior to the implementation
 * RFC can be used as a basis for documentation of the feature
	
## How to write an RFC?
### Template
The following heading can act as a template to starting your RFC.
 * **Introduction** - include a reference #, title, author
 * **Metadata** - standardised header containing at least the Author(s), Status and Version fields.
 * **Purpose and outcome** - the purpose of this document, and the expected outcome.
 * **Motivation** - why this is a good idea
 * **Proposal** - how you propose to implement the idea after community discussion
 * **Alternatives** - what other approaches were considered during the community discussion phase and why they were not chosen
 * **Impact** - How will this change potentially impact on SilverStripe core? The good and the bad.

### Submitting
Once complete submit the RFC in the prescribed format above as a GitHub issue as markdown. A core committer will add a tag to your RFC to keep track of the submissions and status (see links to filtered GitHub issues at the bottom of this document). The GitHub Issue will be closed once a pull request containing the feature gets merged. 

## What next?
The RFC will be raised and discussed by the core committers in the monthly Google Hangout sessions, a vote for accepting the RFC will be taken requiring a majority vote (with at least a quorum of more than half of the core committers present).

Once approved it will be announced on the [developer list](https://groups.google.com/forum/#!forum/silverstripe-dev) and if relevant, [UserVoice](http://silverstripe.uservoice.com) and [Roadmap](https://www.silverstripe.org/software/roadmap) will be updated. This now means that if a pull request meeting the idea set out in the RFC was raised that it would be merged by the Core Committers (pending the usual code peer review).

## RFC Archives

[Proposed RFC drafts](https://github.com/silverstripe/silverstripe-framework/labels/rfc%2Fdraft)


[Accepted by Core Committers](https://github.com/silverstripe/silverstripe-framework/labels/rfc%2Faccepted)

