title: Static Publishing
summary: Export your web pages as static HTML and serve the web like it's 1999.

# Static Publishing

One of the best ways to get the top performance out of SilverStripe is to bypass it completely. This saves on any loading
time, connecting to the database and formatting your templates. This is only appropriate approach on web pages that 
have completely static content. 

<div class="info" markdown="1">
If you want to cache part of a page, or your site has interactive elements such as forms, then 
[Partial Caching](partial_caching) is more suitable.
</div>

By publishing the page as HTML it's possible to run SilverStripe from behind a corporate firewall, on a low performance 
server or serve millions of hits an hour without expensive hardware.

This functionality is available through the [StaticPublisher](https://github.com/silverstripe-labs/silverstripe-staticpublisher)
module. The module provides hooks for developers to generate static HTML files for the whole application or publish key
pages (e.g a web applications home page) as HTML to reduce load on the server.