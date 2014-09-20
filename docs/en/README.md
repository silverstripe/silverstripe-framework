# Todo

This folder is a work in progress for the new SilverStripe.org documentation
project run by Cameron Findlay and Will Rossiter. If you want to contribute 
we'd love you too so flick us a message via email or twitter.

The docsviewer module issue tracker has a set of functional requirements that
we need to make as part of this work.

At the current point, the existing docs have just been dropped into the correct
sections. Index files need to be written as well as perhaps files merged or 
reworked within sections.

## How-tos

How-tos should be each of the learning categories under a `howto` folder which
is visible within the section. This separates the context of reference 
documentation to more tutorial style steps.

## Review *

Below where we say 'review' this relates to writing new index folders, 
organizing the existing pages into a cohesive structure, how-tos out to 
individual files and rewriting documentation pages in a standard and agreed upon
language style.

We are also looking at using a consistent example across all the documentation
and releasing this code on Github so that it gives developers a great reference
of what a beautiful SilverStripe project looks like.

## Writing and Language notes

Todo

## Developer Guide notes

The developer guides are a new concept. Each guide is broken into 2 sections
	
	- How tos (stored within a how-to folder)
	- Reference documentation

How-tos should be short, sweet and full of code. The style of these is for users
to basically copy and paste to get a solution. An example of this would be
`How to add a custom action to a GridField row`. 

Everything else in the developer guide should be written as a reference manual.

Each section should contain an index.md file which summaries the topic, provides
the *entry level* user an introduction guide to the feature and any background
then it can go down into more detailed explanation into detailed references.

If you cannot place a how-to within a single developer guide, that would be an
indication that it should be a tutorial rather than part of a guide. Tutorials
should cover a full working case of a problem, the thought behind the problem 
and a annotated implementation. An example of a new tutorial would be 
'Building a Website without the CMS'. 'Building a contact form' would still sit
under 'Forms' as while it may have templates and controllers involved, as a user
 'Form' is the action word.

## The plan

See our plan and progress at https://trello.com/b/y32uSVM1/silverstripe-documentation
