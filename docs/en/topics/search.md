# Search

## Searching for Pages (and Files)

Fulltext search for page content (and other attributes like "Title" or "MetaTags") can be easily added to SilverStripe.
See [Tutorial: Site Search](/tutorials/4-site-search) for details.

## Searching for DataObject's

The `[api:SearchContext]` class provides a good base implementation that you can hook into your own controllers. 
A working implementation of searchable DataObjects can be seen in the `[api:ModelAdmin]` class.

[SearchContext](/reference/searchcontext) goes into more detail about setting up a default search form for `[api:DataObject]`s.

## Searching for Documents

SilverStripe does not have a built-in method to search through file content (e.g. in PDF or DOC format).
You can either extract any textual file content into the `[File](api:File)->Content` property, or use a
dedicated search service like the [sphinx module](http://silverstripe.org/sphinx-module).

## Related

*  `[api:ModelAdmin]`
*  `[api:RestfulServer]`
*  [Tutorial: Site Search](/tutorials/4-site-search)
*  [SearchContext](/reference/searchcontext)
*  [genericviews module](http://silverstripe.org/generic-views-module)
*  [sphinx module](http://silverstripe.org/sphinx-module)
*  [lucene module](http://silverstripe.org/lucene-module)