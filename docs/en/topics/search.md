# Search

## Searching for Pages (and Files)

Fulltext search for page content (and other attributes like "Title" or "MetaTags") can be easily added to SilverStripe.
See [Tutorial: Site Search](/tutorials/4-site-search) for details.

## Searching for DataObjects

The `[api:SearchContext]` class provides a good base implementation that you can hook into your own controllers. 
A working implementation of searchable DataObjects can be seen in the `[ModelAdmin](/reference/modeladmin)` class.

[SearchContext](/reference/searchcontext) goes into more detail about setting up a default search form for `[api:DataObject]`s.

### Fulltext search on DataObjects

The `[api:MySQLDatabase]` class now defaults to creating tables using the InnoDB storage engine. As Fulltext search in MySQL
requires the MyISAM storage engine, any DataObject you wish to use with Fulltext search must be changed to use MyISAM storage
engine.

You can do so by adding this static variable to your class definition:

	:::php
	private static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=MyISAM'
	);

### Fulltext Filter
SilverStripe provides a `[api:FulltextFiler]` which you can use to perform custom fulltext searches on `[api:DataList]`'s.

Example DataObject:

	:::php
	class SearchableDataObject extends DataObject {
		
		private static $db = array(
			"Title" => "Varchar(255)",
			"Content" => "HTMLText",
		);

		private static $indexes = array(
			'SearchFields' => array(
				'type' => 'fulltext',
				'name' => 'SearchFields',
				'value' => '"Title", "Content"',
			)
		);

		private static $create_table_options = array(
			'MySQLDatabase' => 'ENGINE=MyISAM'
		);

	}

Performing the search:

	:::php
	SearchableDataObject::get()->filter('SearchFields:fulltext', 'search term');



## Searching for Documents

SilverStripe does not have a built-in method to search through file content (e.g. in PDF or DOC format).
You can either extract any textual file content into the `[File](api:File)->Content` property, or use a
dedicated search service like the [sphinx module](http://silverstripe.org/sphinx-module).

## Related

*  [ModelAdmin](/reference/modeladmin)
*  [RestfulServer module](https://github.com/silverstripe/silverstripe-restfulserver)
*  [Tutorial: Site Search](/tutorials/4-site-search)
*  [SearchContext](/reference/searchcontext)
*  [genericviews module](http://silverstripe.org/generic-views-module)
*  [sphinx module](http://silverstripe.org/sphinx-module)
*  [lucene module](http://silverstripe.org/lucene-module)