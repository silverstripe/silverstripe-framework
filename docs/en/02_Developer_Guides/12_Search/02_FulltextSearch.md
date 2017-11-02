title: Fulltext Search
summary: Fulltext search allows sophisticated searching on text content.

# FulltextSearchable

Fulltext search allows advanced search criteria for searching words within a text based data column. While basic
Fulltext search can be achieved using the built-in [MySQLDatabase](api:SilverStripe\ORM\Connect\MySQLDatabase) class a more powerful wrapper for Fulltext
search is provided through a module.

<div class="notice" markdown="1">
See the [FulltextSearch Module](https://github.com/silverstripe-labs/silverstripe-fulltextsearch/). This module provides
a high level wrapper for running advanced search services such as Solr, Lucene or Sphinx in the backend rather than
`MySQL` search.
</div>

## Adding Fulltext Support to MySQLDatabase

The [MySQLDatabase](api:SilverStripe\ORM\Connect\MySQLDatabase) class defaults to creating tables using the InnoDB storage engine. As Fulltext search in MySQL
requires the MyISAM storage engine, any DataObject you wish to use with Fulltext search must be changed to use MyISAM
storage engine.

You can do so by adding this static variable to your class definition:


```php
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Connect\MySQLSchemaManager;

class MyDataObject extends DataObject 
{
    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM'
    ];
}
```

The [FulltextSearchable](api:SilverStripe\ORM\Search\FulltextSearchable) extension will add the correct `Fulltext` indexes to the data model.

<div class="alert" markdown="1">
The [SearchForm](api:SilverStripe\CMS\Search\SearchForm) and [FulltextSearchable](api:SilverStripe\ORM\Search\FulltextSearchable) API's are currently hard coded to be specific to `Page` and `File`
records and cannot easily be adapted to include custom `DataObject` instances. To include your custom objects in the
default site search, have a look at those extensions and modify as required.
</div>

### Fulltext Filter

SilverStripe provides a [FulltextFilter](api:SilverStripe\ORM\Filters\FulltextFilter) which you can use to perform custom fulltext searches on
[DataList](api:SilverStripe\ORM\DataList)s.

Example DataObject:


```php
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Connect\MySQLSchemaManager;

class SearchableDataObject extends DataObject 
{
    
    private static $db = [
        "Title" => "Varchar(255)",
        "Content" => "HTMLText",
    ];

    private static $indexes = [
        'SearchFields' => [
            'type' => 'fulltext',
            'columns' => ['Title', 'Content'],
        ]
    ];

    private static $create_table_options = [
        MySQLSchemaManager::ID => 'ENGINE=MyISAM'
    ];

}

```

Performing the search:


```php
SearchableDataObject::get()->filter('SearchFields:Fulltext', 'search term');
```

If your search index is a single field size, then you may also specify the search filter by the name of the
field instead of the index.

## API Documentation

* [FulltextSearchable](api:SilverStripe\ORM\Search\FulltextSearchable)
