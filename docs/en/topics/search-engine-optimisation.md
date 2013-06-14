# Search Engine Optimisation (SEO)

Ranking high in a search engine is one of the key factors of your sites awerness on the web today. 
The SilverStripe platform have a default set of features that will help you provide a structure that
helps search engines to index and rank your site.

 > Search engine optimization (SEO) is the process of improving the visibility of a website or a web 
 > page in a search engine's "natural" or un-paid ("organic" or "algorithmic") search results.
 Source: [wikipedia](http://en.wikipedia.org/wiki/Search_engine_optimization)

## Page title

## URLs

### Nested URLs

By adding a the following line in your mysite/_config.php

	:::php
	if(class_exists('SiteTree')) SiteTree::enable_nested_urls();

Will make the url for any page 'inherit' the parent folders url. Site tree example:

    - Category (url: category/)
        - SubCategory (URL: subcategory/) 

The SubCategory page URL will then be `category/subcategory/`

If you disable the nested URL functionality, 

	:::php
	SiteTree::disable_nested_urls()

the SubCategory page URL will then be `subcategory/`

### Trailing slash

By the default SilverStripe adds trailing slashes on all URLs that are passed through the internal 
framework (see `Controller::join_links()`).

This automatic functionlity can be disabled by adding 
the following configuration in a `mysite/_config/controller.yml` file.

	---
	Name: mysitecontroller
	After: '#corecontroller'
	---
	Controller:
	  links_have_trailing_slash:
	    false

*Note:* URLs that looks like files, ie have a dot (.) in them will never have trailing slash on 
them.

## Meta tags

## Sitemap

## Reports

## Modified since

