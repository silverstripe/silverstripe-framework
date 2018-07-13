title: Indexes
summary: Add Indexes to your Data Model to optimize database queries.

# Indexes
Indexes are a great way to improve performance in your application, especially as it grows. By adding indexes to your 
data model you can reduce the time taken for the framework to find and filter data objects. 

The addition of an indexes should be carefully evaluated as they can also increase the cost of other operations such as 
`UPDATE`/`INSERT` and `DELETE`. An index on a column whose data is non unique will actually cost you performance.
E.g. In most cases an index on `boolean` status flag, or `ENUM` state will not increase query performance.

It's important to find the right balance to achieve fast queries using the optimal set of indexes; For SilverStripe 
applications it's a good practice to: 
- add indexes on columns which are frequently used in `filter`, `where` or `orderBy` statements
- for these, only include indexes for columns which are the most restrictive (return the least number of rows)

The SilverStripe framework already places certain indexes for you by default:
- The primary key for each model has a `PRIMARY KEY` unique index
- The `ClassName` column if your model inherits from `DataObject`
- All relationships defined in the model have indexes for their `has_one` entity (for `many_many` relationships 
this index is present on the associative entity).

## Defining an index
Indexes are represented on a `DataObject` through the `DataObject::$indexes` array which maps index names to a 
descriptor. There are several supported notations:

```php
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject 
{
    private static $indexes = [
        '<column-name>' => true,
        '<index-name>' => [
            'type' => '<type>', 
            'columns' => ['<column-name>', '<other-column-name>'],
        ],
        '<index-name>' => ['<column-name>', '<other-column-name>'],
    ];
}
```

The `<column-name>` is used to put a standard non-unique index on the column specified. For complex or large tables 
we recommend building the index to suite the requirements of your data.

The `<index-name>` can be an arbitrary identifier in order to allow for more than one index on a specific database 
column. The "advanced" notation supports more `<type>` notations. These vary between database drivers, but all of them 
support the following:

 * `index`: Standard non unique index. 
 * `unique`: Index plus uniqueness constraint on the value
 * `fulltext`: Fulltext content index

**app/code/MyTestObject.php**

```php
use SilverStripe\ORM\DataObject;

class MyTestObject extends DataObject 
{
    private static $db = [
        'MyField' => 'Varchar',
        'MyOtherField' => 'Varchar',
    ];

    private static $indexes = [
        'MyIndexName' => ['MyField', 'MyOtherField'],
    ];
}
```

<div class="alert" markdown="1">
Please note that if you have previously used the removed `value` key to define an index's contents, SilverStripe will
now throw an error. Use `columns` instead.
</div>

## Complex/Composite Indexes
For complex queries it may be necessary to define a complex or composite index on the supporting object. To create a 
composite index, define the fields in the index order as a comma separated list. 

*Note* Most databases only use the leftmost prefix to optimise the query, try to ensure the order of the index and your 
query parameters are the same. e.g.
- index (col1) - `WHERE col1 = ?`
- index (col1, col2) = `WHERE (col1 = ? AND col2 = ?)`
- index (col1, col2, col3) = `WHERE (col1 = ? AND col2 = ? AND col3 = ?)`

The index would not be used for a query `WHERE col2 = ?` or for `WHERE col1 = ? OR col2 = ?`

As an alternative to a composite index, you can also create a hashed column which is a combination of information from 
other columns. If this is indexed, smaller and reasonably unique it might be faster that an index on the whole column. 

## Index Creation/Destruction
Indexes are generated and removed automatically during a `dev/build`. Caution if you're working with large tables and 
modify an index as the next `dev/build` will `DROP` the index, and then `ADD` it. 

As of 3.7.0 `default_sort` fields will automatically become database indexes as this provides significant performance
benefits.

## API Documentation

* [DataObject](api:SilverStripe\ORM\DataObject)
