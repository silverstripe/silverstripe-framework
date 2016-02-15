# Search

## Searching for Pages (and Files)

Fulltext search for page content (and other attributes like "Title" or "MetaTags") can be easily added to SilverStripe.
See [Tutorial: Site Search](/tutorials/site-search) for details.

## Searching for DataObject's

The [api:SearchContext] class provides a good base implementation that you can hook into your own controllers. 
A working implementation of searchable DataObjects can be seen in the [api:ModelAdmin] class.

[SearchContext](/reference/searchcontext) goes into more detail about setting up a default search form for a [api:DataObject].

## Searching for Documents

SilverStripe does not have a built-in method to search through file content (e.g. in PDF or DOC format).
You can either extract any textual file content into the *Content* property, or use a
dedicated search service like the [sphinx module](https://github.com/silverstripe-labs/silverstripe-fulltextsearch/tree/2.4).

## Related

*  [api:ModelAdmin]
*  [api:RestfulServer]
*  [Tutorial: Site Search](/tutorials/site-search)
*  [SearchContext](/reference/searchcontext)
*  [genericviews module] `http://silverstripe.org/generic-views-module`
*  [sphinx module](https://github.com/silverstripe-labs/silverstripe-fulltextsearch/tree/2.4)
*  [lucene module](https://code.google.com/archive/p/lucene-silverstripe-plugin)
