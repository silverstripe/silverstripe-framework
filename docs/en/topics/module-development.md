# Module Development

## Introduction

Creating a module is a good way to re-use abstract code and templates across multiple projects. SilverStripe already has
certain modules included, for example "framework" and "cms". These three modules are the core functionality and
templating for any initial installation. If you're wanting to add generic functionality that isn't specific to your
project, like a forum, an ecommerce package or a blog you can do it like this;

1.  Create another directory at the root level (same level as "framework" and "cms")
2.  You must create an _config.php inside your module directory, else SilverStripe will not include it
3.  Inside your module directory, follow our [directory structure guidelines](/topics/directory-structure#module_structure)

## Tips

Try and keep your module as generic as possible - for example if you're making a forum module, your members section
shouldn't contain fields like 'Games You Play' or 'Your LiveJournal Name' - if people want to add these fields they can
sub-class your class, or extend the fields on to it.

If you're using Requirements to include generic support files for your project like CSS or Javascript, and want to
override these files to be more specific in your project, the following code is an example of how to do so using the
init() function on your module controller classes:

	:::php
	class Forum_Controller extends Page_Controller {
	
	   public function init() {
	      if(Director::fileExists(project() . "/css/forum.css")) {
	         Requirements::css(project() . "/css/forum.css");
	      }else{
	         Requirements::css("forum/css/forum.css");
	      }
	      parent::init();	
	   }
	
	}


This will use `<projectname>/css/forum.css` if it exists, otherwise it falls back to using `forum/css/forum.css`.

## Publication

If you wish to submit your module to our public directory, you take responsibility for a certain level of code quality,
adherence to conventions, writing documentation, and releasing updates. See [contributing](/misc/contributing).

## Reference

**How To:**

*  [Add a link to your module in the main SilverStripe Admin Menu](/reference/leftandmain)

**Useful Links:**

*  [Modules](modules)
*  [Module Release Process](module-release-process)
*  [Debugging methods](/topics/debugging)
*  [URL Variable Tools](/reference/urlvariabletools) - Lists a number of ���page options��� , ���rendering tools��� or ���special
URL variables��� that you can use to debug your SilverStripe applications
