# Static Publisher

## Introduction

Many sites get too much traffic to justify dynamically sending every request.  Caching is needed. Static Publishing
will generate static versions of your content (HTML) that can be served without ever hitting PHP or the Database.

See `[api:StaticExporter]` for a less flexible, but easier way of building a local static cache from all of
your pages.

See [Partial-Caching](partial-caching) for a much more flexible way of building in caching without statically delivering 
content. Partial Caching is recommended as a basic enhancement to any SilverStripe site however if your site is planning
a vast amount of traffic (eg an article is being dug) then Static Publisher will be appropriate.

## Usage

SilverStripe doesn't have enough information about your template and data-structures to automatically determine which
URLs need to be cached, and at which time they are considered outdated. By adding a custom method allPagesToCache() to
your Page class, you can determine which URLs need caching, and hook in custom logic. This array of URLs is used by the
publisher to generate folders and HTML-files.

	:::php
	class Page extends SiteTree {
	  // ...
	
	  /**
	
	   * Return a list of all the pages to cache
	   */
	  public function allPagesToCache() {
	    // Get each page type to define its sub-urls
	    $urls = array();
	
	    // memory intensive depending on number of pages
	    $pages = SiteTree::get();
	
	    foreach($pages as $page) {
	      $urls = array_merge($urls, (array)$page->subPagesToCache());
	    }
	    
	    // add any custom URLs which are not SiteTree instances
	    $urls[] = "sitemap.xml";
	
	    return $urls;
	  }
	
	 /**
	
	   * Get a list of URLs to cache related to this page
	   */
	  public function subPagesToCache() {
	    $urls = array();
	
	    // add current page
	    $urls[] = $this->Link();
	    
	    // cache the RSS feed if comments are enabled
	    if ($this->ProvideComments) {
	      $urls[] = Director::absoluteBaseURL() . "pagecomment/rss/" . $this->ID;
	    }
	    
	    return $urls;
	  }
	  
	  public function pagesAffectedByChanges() {
	    $urls = $this->subPagesToCache();
	    if($p = $this->Parent) $urls = array_merge((array)$urls, (array)$p->subPagesToCache());
	    return $urls;
	  }
	}

## Excluding Pages

The allPagesToCache function returns all the URLs needed to cache. So if you want to exclude specific pages from the
cache then you unset these URLs from the returned array. If you do not want to cache a specific class (eg UserDefinedForms)
you can also add an exclusion

	:::php
	public function allPagesToCache() {
		$urls = array();
		$pages = SiteTree::get();
		
		// ignored page types
		$ignored = array('UserDefinedForm');
		
		foreach($pages as $page) {
			// check to make sure this page is not in the classname
			if(!in_array($page->ClassName, $ignored)) {
				$urls = array_merge($urls, (array)$page->subPagesToCache());
			}
		}
		
		return $urls;
	}

You can also pass the filtering to the original `SiteTree::get()`;

	:::php
	public function allPagesToCache() {
		$urls = array();
		$pages = SiteTree::get()->where("ClassName != 'UserDefinedForm'");
		...

## Single server Caching

This setup will store the cached content on the same server as the CMS.  This is good for a basic performance enhancement.

### Setup

Put this in mysite/_config.php.  This will create static content in a "cache/" subdirectory, with an HTML suffix.

	:::php
	Object::add_extension("SiteTree", "FilesystemPublisher('cache/', 'html')");


*  Put this into your .htaccess.  It will serve requests from the cache, statically, if the cache file exists.  Replace
**sitedir** with the a subdirectory that you would like to serve the site from (for example, in your dev environment).

[View .htaccess
example](http://open.silverstripe.com/browser/modules/cms/trunk/code/staticpublisher/htaccess_example_rsyncsingleserver)

*  We use a simple PHP script, static-main.php, to control cache lookup.  This makes the .htaccess update simpler.

Just look for this line:

	RewriteRule .* framework/main.php?url=%1&%{QUERY_STRING} [L]


And change the PHP script from main.php to static-main.php:

	RewriteRule .* framework/static-main.php?url=%1&%{QUERY_STRING} [L]

## Using Static Publisher With Subsites Module

Append the following code to mysite/config.php

	:::php
	FilesystemPublisher::$domain_based_caching = true;


Instead of the above code snippet for Page.php, use the following code: 

	:::php
	class Page extends SiteTree {
	
	        // ...
	
		public function allPagesToCache() {
	            // Get each page type to define its sub-urls
		    $urls = array();
	
		    // memory intensive depending on number of pages
		    $pages = Subsite::get_from_all_subsites("SiteTree");
	
		    foreach($pages as $page) {
			$urls = array_merge($urls, (array)$page->subPagesToCache());
		    }
	
		    return $urls;
		}
	
		public function subPagesToCache() {
			$urls = array();
			$urls[] = $this->AbsoluteLink();
			return $urls;
		}
	
		public function pagesAffectedByChanges() {
			$urls = $this->subPagesToCache();
			if($p = $this->Parent) $urls = array_merge((array)$urls, (array)$p->subPagesToCache());
			return $urls;
		}
	
	        // ... some other code ...
	
	}


And the last thing you need to do is adding your main site's host mapping to subsites/host-map.php. For example, your
main site's host is mysite.com the content of the file would be: 

	:::php
	<?php 
	$subsiteHostmap = array (
	  // .. subsite hots mapping ..,
	  'mysite.com', 'mysite.com' 
	);


Remember that you need to add main site's host mapping every time a subsite is added or modified because the operation
overwrites your manual modification to the file and subsite module does not add main site's hot mapping automatically at
the moment.

Another note for host-map.php file. This file doesn't not exist until you have created at least one subsite. 

## Multiple Server Caching

In this setup, you have one server that is your dynamic CMS server, and one or more separate servers that are
responsible for serving static content.  The publication system on the CMS will rsync changes to the static content
servers as needed. No PHP files will be synced to the static content servers unless explicitly requested. All static
assets (images, javascript, etc.) will be rsynced from their original locations. You can then put a load-balancer on the
front of the static content servers. 

This approach is very secure, because you can lock the CMS right down (for example, by IP) and hide all the PHP code
away from potential hackers.  It is also good for high-traffic situations.

### Setup

Add the RsyncMultiHostPublisher extension to your SiteTree objects in mysite/_config.php.  This will create static
content in a "cache/" subdirectory, with an HTML suffix.

	:::php
	Object::add_extension("SiteTree", "RsyncMultiHostPublisher('cache/', 'html')");
	RsyncMultiHostPublisher::set_targets(array(
		'<rsyncuser>@<static-server1>:<webroot>',
		'<rsyncuser>@<static-server2>:<webroot>',
	));


Where `<rsyncuser>` is a unix account with write permissions to `<webroot>` (e.g. `/var/www`), and
`<static-server1>` and `<static-server2>` are the names of your static content servers.  The number of servers is
flexible and depends on your infrastructure and scalability needs.

*  Ensure that the `rsync` unix tool is installed on the CMS server, and ssh access is enabled on the static content
servers.

*  No password can be specified for the SSH connection . The class assumes a key-based authentication without requiring
a password for the username specified in `<rsyncuser>` (see [http://www.csua.berkeley.edu/~ranga/notes/ssh_nopass.html
tutorial](http://www.csua.berkeley.edu/~ranga/notes/ssh_nopass.html tutorial)).

*  Put the .htaccess file linked below into the webroot of each static content server (and rename it to `.htaccess`). 
It will serve requests from the cache, statically, if the cache file exists.  Replace **sitedir** with the a
subdirectory that you would like to serve the site from (for example, in your dev environment).

[View .htaccess
example](http://open.silverstripe.com/browser/modules/cms/trunk/code/staticpublisher/htaccess_example_rsyncmultiservers)

## Cache Control 

There is also the option to wrap some PHP logic around the static HTML content served by the content servers, which can
greatly reduce the bandwidth required on your content servers. This code takes care of cache control through HTTP
headers (''Cache-control'', `If-modified-since`), meaning the files will only be delivered if they changed since the
browser client last requested them. The last modification date for each static file is controlled by the publication
script, meaning the cache gets invalidated on each publication.

To enable cache control, specify "php" instead of "html" in the RsyncMultiHostPublisher definition.

	:::php
	Object::add_extension("SiteTree", "RsyncMultiHostPublisher('cache/', 'php')");


And use this slightly different .htaccess file. Make sure that index.php can be used as a directory index!

[View .htaccess
example](http://open.silverstripe.com/browser/modules/cms/trunk/code/staticpublisher/htaccess_example_rsyncwithphp)

## Deployment

Once you've set up your rewrite rules and defined which pages need caching, you can build the static HTML files. This is
done by the `[api:RebuildStaticCacheTask]`

Execution via URL

	http://www.example.com/dev/buildcache?flush=1

Execution on CLI (via [sake](/topics/commandline))

	sake dev/buildcache flush=1

Depending on which extension you've set up for your SiteTree (FilesystemPublisher or RsyncMultiHostPublisher), the
method publishPages() either stores the generated HTML-files on the server's filesystem, or deploys them to other
servers via rsync.

It is adviseable to set dev/buildcache up as an automated task (e.g. unix cron) which continually rebuilds and redeploys
the cache. 

## Related

*  `[api:StaticExporter]`
*  [Partial-Caching](partial-caching)

## API Documentation
*  `[api:StaticPublisher]`
