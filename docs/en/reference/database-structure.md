# Database Structure

SilverStripe is currently hard-coded to use a fix mapping between data-objects and the underlying database structure -
opting for "convention over configuration".  This page details what that database structure is. 

## Base tables

Each direct sub-class of `[api:DataObject]` will have its own table.

The following fields are always created.

*  ID: Primary Key
*  ClassName: An enumeration listing this data-class and all of its subclasses.
*  Created: A date/time field set to the creation date of this record
*  LastEdited: A date/time field set to the date this record was last edited

Every object of this class **or any of its subclasses** will have an entry in this table

### Extra Fields

*  Every field listed in the data object's **$db** array will be included in this table.
*  For every relationship listed in the data object's **$has_one** array, there will be an integer field included in the
table.  This will contain the ID of the data-object being linked to.  The database field name will be of the form
"(relationship-name)ID", for example, ParentID.

### ID Generation

When a new record is created, we don't use the database's built-in auto-numbering system.  Instead, we generate a new ID
by adding 1 to the current maximum ID.

##  Subclass tables

At SilverStripe's heart is an object-relational model.  And a component of object-oriented data is **inheritance**. 
Unfortunately, there is no native way of representing inheritance in a relational database.  What we do is store the
data sub-classed objects across **multiple tables**.

For example, suppose we have the following set of classes:

*  Class `[api:SiteTree]` extends `[api:DataObject]`: Title, Content fields
*  Class `[api:Page]` extends `[api:SiteTree]`: Abstract field
*  Class NewsSection extends `[api:SiteTree]`: *No special fields*
*  Class NewsArticle extend `[api:Page]`: ArticleDate field

The data for the following classes would be stored across the following tables:

*  `[api:SiteTree]`
    * ID: Int
    * ClassName: Enum('SiteTree', 'Page', 'NewsArticle')
    * Created: Datetime
    * LastEdited: Datetime
    * Title: Varchar
    * Content: Text
*  `[api:Page]`
    * ID: Int
    * Abstract: Text
*  NewsArticle
    * ID: Int
    * ArticleDate: Date

The way it works is this:

*  "Base classes" are direct sub-classes of `[api:DataObject]`.  They are always given a table, whether or not they have
special fields.  This is called the "base table"
*  The base table's ClassName field is set to class of the given record.  It's an enumeration of all possible
sub-classes of the base class (including the base class itself)
*  Each sub-class of the base object will also be given its own table, *as long as it has custom fields*.  In the
example above, NewsSection didn't have its own data and so an extra table would be redundant.
*  In all the tables, ID is the primary key.  A matching ID number is used for all parts of a particular record: 
record #2 in Page refers to the same object as record #2 in `[api:SiteTree]`.

To retrieve a news article, SilverStripe joins the `[api:SiteTree]`, `[api:Page]` and NewsArticle tables by their ID fields.  We use a
left-join for robustness; if there is no matching record in Page, we can return a record with a blank Article field.

## Staging and versioning

[todo]

## Schema auto-generation

SilverStripe has a powerful tool for automatically building database schemas.  We've designed it so that you should never have to build them manually.

To access it, visit (site-root)/dev/build?flush=1.  This script will analyze the existing schema, compare it to what's required by your data classes, and alter the schema as required.  

Put the ?flush=1 on the end if you've added PHP files, so that the rest of the system will find these new classes.

It will perform the following changes:

  * Create any missing tables
  * Create any missing fields
  * Create any missing indexes
  * Alter the field type of any existing fields
  * Rename any obsolete tables that it previously created to _obsolete_(tablename)

It **won't** do any of the following

  * Deleting tables
  * Deleting fields
  * Rename any tables that it doesn't recognize - so other applications can co-exist in the same database, as long as their table names don't match a SilverStripe data class.


## Related code

The information documented in this page is reflected in a few places in the code:

*  `[api:DataObject]`
    * requireTable() is responsible for specifying the required database schema
    * instance_get() and instance_get_one() are responsible for generating the database queries for selecting data.
    * write() is responsible for generating the database queries for writing data.
*  `[api:Versioned]`
    * augmentWrite() is responsible for altering the normal database writing operation to handle versions.
    * augmentQuery() is responsible for altering the normal data selection queries to support versions.
    * augmentDatabase() is responsible for specifying the altered database schema to support versions.
*  `[api:MySQLDatabase]`: getNextID() is used when creating new objects; it also handles the mechanics of
updating the database to have the required schema.