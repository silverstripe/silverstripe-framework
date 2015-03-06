title: Versioning
summary: Add versioning to your database content through the Versioned extension.

# Versioning

Database content in SilverStripe can be "staged" before its publication, as well as track all changes through the 
lifetime of a database record.

It is most commonly applied to pages in the CMS (the `SiteTree` class). Draft content edited in the CMS can be different 
from published content shown to your website visitors. 

Versioning in SilverStripe is handled through the [api:Versioned] class. As a [api:DataExtension] it is possible to 
be applied to any `[api:DataObject]` subclass. The extension class will automatically update read and write operations
done via the ORM via the `augmentSQL` database hook.

Adding Versioned to your `DataObject` subclass works the same as any other extension. It accepts two or more arguments 
denoting the different "stages", which map to different database tables. 

**mysite/_config/app.yml**
	:::yml
	MyRecord:
	  extensions:
	    - Versioned("Stage","Live")

<div class="notice" markdown="1">
The extension is automatically applied to `SiteTree` class. For more information on extensions see 
[Extending](../extending) and the [Configuration](../configuration) documentation.
</div>

## Database Structure

Depending on how many stages you configured, two or more new tables will be created for your records. In the above, this
will create a new `MyRecord_Live` table once you've rebuilt the database.

<div class="notice" markdown="1">
Note that the "Stage" naming has a special meaning here, it will leave the original table name unchanged, rather than 
adding a suffix.
</div>

 * `MyRecord` table: Contains staged data
 * `MyRecord_Live` table: Contains live data
 * `MyRecord_versions` table: Contains a version history (new record created on each save)

Similarly, any subclass you create on top of a versioned base will trigger the creation of additional tables, which are 
automatically joined as required:

 * `MyRecordSubclass` table: Contains only staged data for subclass columns
 * `MyRecordSubclass_Live` table: Contains only live data for subclass columns
 * `MyRecordSubclass_versions` table: Contains only version history for subclass columns

## Usage

### Reading Versions

By default, all records are retrieved from the "Draft" stage (so the `MyRecord` table in our example). You can 
explicitly request a certain stage through various getters on the `Versioned` class.

	:::php
	// Fetching multiple records
	$stageRecords = Versioned::get_by_stage('MyRecord', 'Stage');
	$liveRecords = Versioned::get_by_stage('MyRecord', 'Live');

	// Fetching a single record
	$stageRecord = Versioned::get_by_stage('MyRecord', 'Stage')->byID(99);
	$liveRecord = Versioned::get_by_stage('MyRecord', 'Live')->byID(99);

### Historical Versions

The above commands will just retrieve the latest version of its respective stage for you, but not older versions stored 
in the `<class>_versions` tables.

	:::php
	$historicalRecord = Versioned::get_version('MyRecord', <record-id>, <version-id>);

<div class="alert" markdown="1">
The record is retrieved as a `DataObject`, but saving back modifications via `write()` will create a new version, 
rather than modifying the existing one.
</div>

In order to get a list of all versions for a specific record, we need to generate specialized `[api:Versioned_Version]` 
objects, which expose the same database information as a `DataObject`, but also include information about when and how 
a record was published.
	
	:::php
	$record = MyRecord::get()->byID(99); // stage doesn't matter here
	$versions = $record->allVersions();
	echo $versions->First()->Version; // instance of Versioned_Version

### Writing Versions and Changing Stages

The usual call to `DataObject->write()` will write to whatever stage is currently active, as defined by the 
`Versioned::current_stage()` global setting. Each call will automatically create a new version in the 
`<class>_versions` table. To avoid this, use `[writeWithoutVersion()](api:Versioned->writeWithoutVersion())` instead.

To move a saved version from one stage to another, call `[writeToStage(<stage>)](api:Versioned->writeToStage())` on the 
object. The process of moving a version to a different stage is also called "publishing", so we've created a shortcut 
for this: `publish(<from-stage>, <to-stage>)`.

	:::php
	$record = Versioned::get_by_stage('MyRecord', 'Stage')->byID(99);
	$record->MyField = 'changed';
	// will update `MyRecord` table (assuming Versioned::current_stage() == 'Stage'),
	// and write a row to `MyRecord_versions`.
	$record->write(); 
	// will copy the saved record information to the `MyRecord_Live` table
	$record->publish('Stage', 'Live');

Similarly, an "unpublish" operation does the reverse, and removes a record from a specific stage.

	:::php
	$record = MyRecord::get()->byID(99); // stage doesn't matter here
	// will remove the row from the `MyRecord_Live` table
	$record->deleteFromStage('Live');

### Forcing the Current Stage

The current stage is stored as global state on the object. It is usually modified by controllers, e.g. when a preview 
is initialized. But it can also be set and reset temporarily to force a specific operation to run on a certain stage.

	:::php
	$origMode = Versioned::get_reading_mode(); // save current mode
	$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Live' records
	Versioned::set_reading_mode('Stage'); // temporarily overwrite mode
	$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Stage' records
	Versioned::set_reading_mode($origMode); // reset current mode

### Custom SQL

We generally discourage writing `Versioned` queries from scratch, due to the complexities involved through joining 
multiple tables across an inherited table scheme (see `[api:Versioned->augmentSQL()]`). If possible, try to stick to 
smaller modifications of the generated `DataList` objects.

Example: Get the first 10 live records, filtered by creation date:

	:::php
	$records = Versioned::get_by_stage('MyRecord', 'Live')->limit(10)->sort('Created', 'ASC');

### Permissions

The `Versioned` extension doesn't provide any permissions on its own, but you can have a look at the `SiteTree` class 
for implementation samples, specifically `canPublish()` and `canDeleteFromStage()`.

### Page Specific Operations

Since the `Versioned` extension is primarily used for page objects, the underlying `SiteTree` class has some additional 
helpers.

### Templates Variables

In templates, you don't need to worry about this distinction. The `$Content` variable contain the published content by 
default, and only preview draft content if explicitly requested (e.g. by the "preview" feature in the CMS, or by adding ?stage=Stage to the URL). If you want 
to force a specific stage, we recommend the `Controller->init()` method for this purpose, for example:

**mysite/code/MyController.php**
	:::php
	public function init() {
		parent::init();
		Versioned::set_reading_mode('Stage.Stage');
	}


### Controllers

The current stage for each request is determined by `VersionedRequestFilter` before any controllers initialize, through 
`Versioned::choose_site_stage()`. It checks for a `Stage` GET parameter, so you can force a draft stage by appending 
`?stage=Stage` to your request. The setting is "sticky" in the PHP session, so any subsequent requests will also be in 
draft stage.

<div class="alert" markdown="1">
The `choose_site_stage()` call only deals with setting the default stage, and doesn't check if the user is 
authenticated to view it. As with any other controller logic, please use `DataObject->canView()` to determine 
permissions, and avoid exposing unpublished content to your users.
</div>

## API Documentation

* [api:Versioned]
