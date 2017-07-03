title: Versioning
summary: Add versioning to your database content through the Versioned extension.

# Versioning

Database content in SilverStripe can be "staged" before its publication, as well as track all changes through the 
lifetime of a database record.

It is most commonly applied to pages in the CMS (the `SiteTree` class). Draft content edited in the CMS can be different 
from published content shown to your website visitors. 

Versioning in SilverStripe is handled through the [Versioned](api:SilverStripe\Versioned\Versioned) class. As a [DataExtension](api:SilverStripe\ORM\DataExtension) it is possible to 
be applied to any [DataObject](api:SilverStripe\ORM\DataObject) subclass. The extension class will automatically update read and write operations
done via the ORM via the `augmentSQL` database hook.

Adding Versioned to your `DataObject` subclass works the same as any other extension. It has one of two behaviours,
which can be applied via the constructor argument.

By default, adding the `Versioned extension will create a "Stage" and "Live" stage on your model, and will
also track versioned history.


	:::php
	class MyStagedModel extends DataObject {
		private static $extensions = [
			Versioned::class
		];
	}


Alternatively, staging can be disabled, so that only versioned changes are tracked for your model. This
can be specified by setting the constructor argument to "Versioned"


	:::php
	class VersionedModel extends DataObject {
		private static $extensions = [
			"SilverStripe\\ORM\\Versioning\\Versioned('Versioned')"
		];
	}


<div class="notice" markdown="1">
The extension is automatically applied to `SiteTree` class. For more information on extensions see 
[Extending](../extending) and the [Configuration](../configuration) documentation.
</div>

<div class="warning" markdown="1">
Versioning only works if you are adding the extension to the base class. That is, the first subclass
of `DataObject`. Adding this extension to children of the base class will have unpredictable behaviour.
</div>

## Database Structure

Depending on whether staging is enabled, one or more new tables will be created for your records. `<class>_versions`
is always created to track historic versions for your model. If staging is enabled this will also create a new
`<class>_Live` table once you've rebuilt the database.

<div class="notice" markdown="1">
Note that the "Stage" naming has a special meaning here, it will leave the original table name unchanged, rather than 
adding a suffix.
</div>

 * `MyRecord` table: Contains staged data
 * `MyRecord_Live` table: Contains live data
 * `MyRecord_Versions` table: Contains a version history (new record created on each save)

Similarly, any subclass you create on top of a versioned base will trigger the creation of additional tables, which are 
automatically joined as required:

 * `MyRecordSubclass` table: Contains only staged data for subclass columns
 * `MyRecordSubclass_Live` table: Contains only live data for subclass columns
 * `MyRecordSubclass_Versions` table: Contains only version history for subclass columns

## Usage

### Reading Versions

By default, all records are retrieved from the "Draft" stage (so the `MyRecord` table in our example). You can 
explicitly request a certain stage through various getters on the `Versioned` class.

	:::php
	// Fetching multiple records
	$stageRecords = Versioned::get_by_stage('MyRecord', Versioned::DRAFT);
	$liveRecords = Versioned::get_by_stage('MyRecord', Versioned::LIVE);

	// Fetching a single record
	$stageRecord = Versioned::get_by_stage('MyRecord', Versioned::DRAFT)->byID(99);
	$liveRecord = Versioned::get_by_stage('MyRecord', Versioned::LIVE)->byID(99);

### Historical Versions

The above commands will just retrieve the latest version of its respective stage for you, but not older versions stored 
in the `<class>_versions` tables.

	:::php
	$historicalRecord = Versioned::get_version('MyRecord', <record-id>, <version-id>);

<div class="alert" markdown="1">
The record is retrieved as a `DataObject`, but saving back modifications via `write()` will create a new version, 
rather than modifying the existing one.
</div>

In order to get a list of all versions for a specific record, we need to generate specialized [Versioned_Version](api:SilverStripe\Versioned\Versioned_Version) 
objects, which expose the same database information as a `DataObject`, but also include information about when and how 
a record was published.
	
	:::php
	$record = MyRecord::get()->byID(99); // stage doesn't matter here
	$versions = $record->allVersions();
	echo $versions->First()->Version; // instance of Versioned_Version

### Writing Versions and Changing Stages

The usual call to `DataObject->write()` will write to the draft stage. If the current stage is set
to `Versioned::LIVE` (as defined by `Versioned::current_stage()`) then the record will also be
updated on the live stage. Through this mechanism the draft stage is the latest source of truth
for any record.

In addition, each call will automatically create a new version in the 
`<class>_versions` table. To avoid this, use [Versioned::writeWithoutVersion()](api:SilverStripe\Versioned\Versioned::writeWithoutVersion()) instead.

To move a saved version from one stage to another, call [writeToStage(<stage>)](api:SilverStripe\Versioned\Versioned::writeToStage()) on the 
object. The process of moving a version to a different stage is also called "publishing". This can be
done via one of several ways:

 * `copyVersionToStage` which will allow you to specify a source (which could be either a version
   number, or a stage), as well as a destination stage.
 * `publishSingle` Publishes this record to live from the draft.
 * `publishRecursive` Publishes this record, and any dependant objects this record may refer to.
   See "DataObject ownership" for reference on dependant objects.

	:::php
	$record = Versioned::get_by_stage('MyRecord', Versioned::DRAFT)->byID(99);
	$record->MyField = 'changed';
	// will update `MyRecord` table (assuming Versioned::current_stage() == 'Stage'),
	// and write a row to `MyRecord_versions`.
	$record->write(); 
	// will copy the saved record information to the `MyRecord_Live` table
	$record->publishRecursive();

Similarly, an "unpublish" operation does the reverse, and removes a record from a specific stage.

	:::php
	$record = MyRecord::get()->byID(99); // stage doesn't matter here
	// will remove the row from the `MyRecord_Live` table
	$record->deleteFromStage(Versioned::LIVE);

### Forcing the Current Stage

The current stage is stored as global state on the object. It is usually modified by controllers, e.g. when a preview 
is initialized. But it can also be set and reset temporarily to force a specific operation to run on a certain stage.

	:::php
	$origMode = Versioned::get_reading_mode(); // save current mode
	$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Live' records
	Versioned::set_reading_mode(Versioned::DRAFT); // temporarily overwrite mode
	$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Stage' records
	Versioned::set_reading_mode($origMode); // reset current mode

### DataObject ownership

Typically when publishing versioned dataobjects, it is necessary to ensure that some linked components
are published along with it. Unless this is done, site front-end content can appear incorrectly published.

For instance, a page which has a list of rotating banners will require that those banners are published
whenever that page is.

The solution to this problem is the ownership API, which declares a two-way relationship between
objects along database relations. This relationship is similar to many_many/belongs_many_many
and has_one/has_many, however it relies on a pre-existing relationship to function.

For instance, in order to specify this dependency, you must apply `owns` on the owner to point to any
owned relationships.

When pages of type `MyPage` are published, any owned images and banners will be automatically published,
without requiring any custom code.


	:::php
	class MyPage extends Page {
		private static $has_many = array(
			'Banners' => Banner::class
		);
		private static $owns = array(
			'Banners'
		);
	}
	
	class Banner extends Page {
		private static $extensions = array(
			Versioned::class
		);
		private static $has_one = array(
			'Parent' => MyPage::class,
			'Image' => Image::class,
		);
		private static $owns = array(
			'Image'
		);
	}


Note that ownership cannot be used with polymorphic relations. E.g. has_one to non-type specific `DataObject`. 

#### DataObject ownership with custom relations

In some cases you might need to apply ownership where there is no underlying db relation, such as
those calculated at runtime based on business logic. In cases where you are not backing ownership
with standard relations (has_one, has_many, etc) it is necessary to declare ownership on both
sides of the relation.

This can be done by creating methods on both sides of your relation (e.g. parent and child class)
that can be used to traverse between each, and then by ensuring you configure both
`owns` config (on the parent) and `owned_by` (on the child).

E.g.

	:::php
	class MyParent extends DataObject {
		private static $extensions = array(
			Versioned::class
		);
		private static $owns = array(
			'ChildObjects'
		);
		public function ChildObjects() {
			return MyChild::get();
		}
	}
	class MyChild extends DataObject {
		private static $extensions = array(
			Versioned::class
		);
		private static $owned_by = array(
			'Parent'
		);
		public function Parent() {
			return MyParent::get()->first();
		}
	}

#### DataObject Ownership in HTML Content

If you are using `[DBHTMLText](api:SilverStripe\ORM\FieldType\DBHTMLText)` or `[DBHTMLVarchar](api:SilverStripe\ORM\FieldType\DBHTMLVarchar)` fields in your `DataObject::$db` definitions,
it's likely that your authors can insert images into those fields via the CMS interface.
These images are usually considered to be owned by the `DataObject`, and should be published alongside it.
The ownership relationship is tracked through an `[image]` [shortcode](/developer-guides/extending/shortcodes),
which is automatically transformed into an `<img>` tag at render time. In addition to storing the image path,
the shortcode references the database identifier of the `Image` object.

### Custom SQL

We generally discourage writing `Versioned` queries from scratch, due to the complexities involved through joining 
multiple tables across an inherited table scheme (see [Versioned::augmentSQL()](api:SilverStripe\Versioned\Versioned::augmentSQL())). If possible, try to stick to 
smaller modifications of the generated `DataList` objects.

Example: Get the first 10 live records, filtered by creation date:

	:::php
	$records = Versioned::get_by_stage('MyRecord', Versioned::LIVE)->limit(10)->sort('Created', 'ASC');

### Permissions

By default, `Versioned` will come out of the box with security extensions which restrict
the visibility of objects in Draft (stage) or Archive viewing mode.

<div class="alert" markdown="1">
As is standard practice, user code should always invoke `canView()` on any object before
rendering it. DataLists do not filter on `canView()` automatically, so this must be
done via user code. This be be achieved either by wrapping `<% if $canView %>` in
your template, or by implementing your visibility check in PHP.
</div>

Versioned object visibility can be customised in one of the following ways by editing your user code:

 * Override the `canViewVersioned` method in your code. Make sure that this returns true or
   false if the user is not allowed to view this object in the current viewing mode.
 * Override the `canView` method to override the method visibility completely.
 
E.g.

    :::php
    class MyObject extends DataObject {
        private static $extensions = array(
            Versioned::class,
        );
        
        public function canViewVersioned($member = null) {
            // Check if site is live
            $mode = $this->getSourceQueryParam("Versioned.mode");
            $stage = $this->getSourceQueryParam("Versioned.stage");
            if ($mode === 'Stage' && $stage === 'Live') {
                return true;
            }
            
            // Only admins can view non-live objects
            return Permission::checkMember($member, 'ADMIN');
        }
    }

If you want to control permissions of an object in an extension, you can also use
one of the below extension points in your `DataExtension` subclass:

 * `canView` to update the visibility of the object's `canView`
 * `canViewNonLive` to update the visibility of this object only in non-live mode.

Note that unlike canViewVersioned, the canViewNonLive method will 
only be invoked if the object is in a non-published state.
 
E.g.

    :::php
    class MyObjectExtension extends DataExtension {
        public function canViewNonLive($member = null) {
            return Permission::check($member, 'DRAFT_STATUS');
        }
    }

If none of the above checks are overridden, visibility will be determined by the 
permissions in the `TargetObject.non_live_permissions` config.

E.g.

    :::php
    class MyObject extends DataObject {
        private static $extensions = array(
            Versioned::class,
        );
        private static $non_live_permissions = array('ADMIN');
    }

Versioned applies no additional permissions to `canEdit` or `canCreate`, and such
these permissions should be implemented as per standard unversioned DataObjects.

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
		Versioned::set_stage(Versioned::DRAFT);
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

* [Versioned](api:SilverStripe\Versioned\Versioned)
