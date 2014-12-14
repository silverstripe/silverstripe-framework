title: Fulltext Search
summary: Fulltext search allows sophisticated searching on text content.

# FulltextSearchable

Fulltext search allows advanced search criteria for searching words within a text based data column. While basic
Fulltext search can be achieved using the built-in [api:MySQLDatabase] class a more powerful wrapper for Fulltext
search is provided through a module.

<div class="notice" markdown="1">
See the [FulltextSearch Module](https://github.com/silverstripe-labs/silverstripe-fulltextsearch/). This module provides
a high level wrapper for running advanced search services such as Solr, Lucene or Sphinx in the backend rather than
`MySQL` search.
</div>

## Adding Fulltext Support to MySQLDatabase

The [api:MySQLDatabase] class defaults to creating tables using the InnoDB storage engine. As Fulltext search in MySQL
requires the MyISAM storage engine, any DataObject you wish to use with Fulltext search must be changed to use MyISAM
storage engine.

You can do so by adding this static variable to your class definition:

	:::php
	<?php

	class MyDataObject extends DataObject {

		private static $create_table_options = array(
			'MySQLDatabase' => 'ENGINE=MyISAM'
		);
	}

The [api:FulltextSearchable] extension will add the correct `Fulltext` indexes to the data model.

<div class="alert" markdown="1">
The [api:SearchForm] and [api:FulltextSearchable] API's are currently hard coded to be specific to `Page` and `File`
records and cannot easily be adapted to include custom `DataObject` instances. To include your custom objects in the
default site search, have a look at those extensions and modify as required.
</div>

## API Documentation

* [api:FulltextSearchable]