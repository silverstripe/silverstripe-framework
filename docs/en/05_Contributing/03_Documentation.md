title: Documentation
summary: Writing guide for contributing to SilverStripe developer and CMS user help documentation. 

# Contributing documentation

Documentation for a software project is a continued and collaborative effort. We encourage everybody to contribute in any way they can, from simply fixing spelling mistakes, to writing recipes, to reviewing existing documentation and translating it to another language.

Modifying documentation requires basic [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) and 
[Markdown](http://daringfireball.net/projects/markdown/) knowledge, and a GitHub user account.

## Editing online

The easiest way of editing any documentation is by clicking the "Edit this page" link at the bottom of the 
page you want to edit. Alternatively, locate the appropriate .md file in the 
[github.com/silverstripe/silverstripe-framework](https://github.com/silverstripe/silverstripe-framework/tree/master/docs/) repository and press the "edit" button. **You will need a free GitHub account to do this**. 


 * After editing the documentation, describe your changes in the "commit summary" and "extended description" fields below then press "Commit Changes".
 * After that you will see a form to submit a Pull Request: "[pull requests](http://help.github.com/pull-requests/)". You should be able to adjust the version your documentation changes apply to and then submit the form. Your changes will be sent to the core committers for approval.

<div class="warning" markdown='1'>
You should make your changes in the lowest branch they apply to. For instance, if you fix a spelling issue that you found in the 3.1 documentation, submit your fix to that branch in Github and it'll be copied to the master (3.2) version of the documentation automatically. *Don't submit multiple pull requests*.
</div>

## Editing on your computer

If you prefer to edit content on your local machine, you can "[fork](http://help.github.com/forking/)" the 
[github.com/silverstripe/silverstripe-framework](http://github.com/silverstripe/silverstripe-framework) and 
[github.com/silverstripe/silverstripe-cms](http://github.com/silverstripe/silverstripe-cms) repositories, and send us "[pull requests](http://help.github.com/pull-requests/)" to incorporate your changes. If you have previously downloaded SilverStripe or a module, chances are that you already have these repositories on your machine.

The documentation is kept alongside the source code in the `docs/` subfolder of any SilverStripe module, framework or CMS folder.

<div class="warning" markdown='1'>
If you submit a new feature or an API change, we strongly recommend that your patch includes updates to the necessary documentation. This helps prevent our documentation from getting out of date.
</div>

## Repositories

*  End-user: [userhelp.silverstripe.org](http://github.com/silverstripe/userhelp.silverstripe.org)
*  Developer guides: [doc.silverstripe.org](http://github.com/silverstripe/doc.silverstripe.org)
*  Developer API documentation: [api.silverstripe.org](http://github.com/silverstripe/api.silverstripe.org)

## Source control

In order to balance editorial control with effective collaboration, we keep documentation alongside the module source code, e.g. in `framework/docs/`, or as code comments within PHP code. Contributing documentation is the same process as providing any other patch (see [Contributing code](code)).

## What to write

See [what to write (jacobian.org)](http://jacobian.org/writing/great-documentation/what-to-write/) for an excellent introduction to the different types of documentation. Also see [producing OSS: "documentation"](http://producingoss.com/en/getting-started.html#documentation) for good rules of thumb 
for documenting open source software.

## Structure

* Keep documentation lines to 120 characters.
* Don't duplicate: Search for existing places to put your documentation. Do you really require a new page, or just a new paragraph of text somewhere?
* Use PHPDoc in source code: Leave low level technical documentation to code comments within PHP, in [PHPDoc](http://en.wikipedia.org/wiki/PHPDoc) format. 
* API and developer guides are two forms of source code documentation that complement each other.
* API documentation should provide context, ie, the "bigger picture", by referring to developer guides inside your PHPDoc.
* Make your documentation easy to find: Documentation is useful only when it is interlinked so please make sure your contribution doesn't become an inaccessible island. At the very least, put a link to your index page in the same folder. A link to your page can also appear
as "related content" on other resource (e.g. `/tutorials/site_search` might link to `/developer_guides/forms/introduction`).

## Writing style

* Write in second person plural form: Use "we" instead of "I". It gives the text an instructive and collaborative style.
* It's okay to address the reader: For example "First you'll install a webserver" is good style.
* Write in an active and direct voice.
* Mark up correctly: Use preformatted text. Emphasis and bold make technical writing more easily "scannable".
* Avoid FAQs: FAQs are not a replacement for coherent, well explained documentation. If you've done a good job
documenting, there shouldn't be any "frequently asked questions" left.
* "SilverStripe" should always appear without a space with both "S"s capitalised.
* Use simple language and words. Avoid uncommon jargon and overly long words.
* Use UK English and not US English. SilverStripe is proudly a New Zealand open source project we use the UK spelling and forms of English. The most common of these differences are -ize vs -ise, or -or vs our (eg color vs colour).
* We use sentence case for titles so only capitalise the first letter of the first word of a title. The only exceptions to this are when using brand names (e.g. SilverStripe), acronyms (e.g. PHP) and class names (e.g. ModelAdmin).
* Use gender neutral language throughout the document, unless referencing a specific person. Use them, they, their, instead of he and she, his or her.
* URLs: if the end of your sentence is a URL then you don't need to use a full stop.
* Bullet points: Sentence case your bullet points. If a bullet point is a full sentence then end with a full stop. If it is a short point or a list, full stops are not required.

## Highlighted blocks

There are several built-in block styles for highlighting a paragraph of text. Please use these graphical elements 
sparingly.

<div class="hint" markdown='1'>
"Tip box": A tip box is great for adding, deepening or accenting information in the main text. They can be used for background knowledge, or to provide links to further information (ie, a "see also" link).
</div>

Code for a Tip box:

	<div class="hint" markdown='1'>
	...
	</div>

<div class="notice" markdown='1'>
"Notification box": A notification box is good for technical notifications relating to the main text. For example, notifying users about a deprecated feature.
</div>

Code for a Notification box:

	<div class="notice" markdown='1'>
	...
	</div>

<div class="warning" markdown='1'>
"Warning box": A warning box is useful for highlighting a severe bug or a technical issue requiring a user's attention. For example, suppose a rare edge case sometimes leads to a variable being overwritten incorrectly. A warning box can be used to alert the user to this case so they can write their own code to handle it.  
</div>

Code for a Warning box:

	<div class="warning" markdown='1'>
	...
	</div>

See [markdown extra documentation](http://michelf.com/projects/php-markdown/extra/#html) for more restrictions
on placing HTML blocks inside Markdown.

## Translating documentation

Documentation is kept alongside the source code, typically in a module subdirectory like `framework/docs/en/`. Each language has its own subfolder, which can duplicate parts of or the entire body of documentation. German documentation would, for example, live in `framework/docs/de/`. The 
[docsviewer](https://github.com/silverstripe/silverstripe-docsviewer) module that drives 
[doc.silverstripe.org](http://doc.silverstripe.org) automatically resolves these subfolders into a language dropdown.

## Further reading

* [Writing great documentation (jacobian.org)](http://jacobian.org/writing/great-documentation/)
* [How tech writing sucks: Five sins](http://www.slash7.com/articles/2006/11/15/tech-writing-the-five-sins)
* [What is good documentation?](http://www.techscribe.co.uk/techw/whatis.htm)
