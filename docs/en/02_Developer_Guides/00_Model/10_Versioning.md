title: Versioning
summary: Add versioning to your database content through the Versioned extension.

# Versioning

Database content in SilverStripe can be "staged" before its publication, as well as track all changes through the
lifetime of a database record.

It is most commonly applied to pages in the CMS (the `SiteTree` class). Draft content edited in the CMS can be different
from published content shown to your website visitors.

Versioning in SilverStripe is handled through the [Versioned](api:SilverStripe\Versioned\Versioned) class. As a [DataExtension](api:SilverStripe\ORM\DataExtension) it is possible to be applied to any [DataObject](api:SilverStripe\ORM\DataObject) subclass. The extension class will automatically update read and write operations done via the ORM via the `augmentSQL` database hook.

## Understanding versioning concepts

This section discuss how SilverStripe implements versioning and related high level concepts without digging into technical details.

### Stages

In most cases, you'll want to have one polished version of a `Page` visible to the general public while your editors might be working off a draft version. SilverStripe handles this through the concept of _stage_.

By default, adding the `Versioned` extension to a DataObject will create a 2 stages:
* "Stage" for tracking draft content
* "Live" for tracking content publicly visible.

Publishing a versioned `DataObject` is equivalent to copying the version from the "Stage" stage to the "Live" stage.

You can disable stages if your DataObject doesn't require a published version. This will allow you to keep track of all changes that have been applied to a DataObject and who made them.

### Ownership and relations between DataObject

Typically when publishing versioned DataObjects, it is necessary to ensure that some linked components
are published along with it. Unless this is done, site front-end content can appear incorrectly published.

For instance, a page which has a list of rotating banners will require that those banners are published
whenever that page is.

The solution to this problem is the ownership API, which declares a two-way relationship between
objects along database relations. This relationship is similar to many_many/belongs_many_many
and has_one/has_many, however it relies on a pre-existing relationship to function. 

#### Cascade publishing

If a DataObject object "owns" other DataObjects, you'll usually want to publish the children DataObject when the parent DataObject gets published. If those children DataObjects themselves own other DataObjects, you'll want the grand-children to be published along with the parent.

SilverStripe makes this possible by using the concept of _cascade publishing_. You can choose to recursively publish a DataObject. When a DataObject is recursively published – either through a user action or through code – all other records it owns that implement the Versioned extension will automatically be published. Publication, will also cascade to children of children and so on.

A non-recursive publish operation is also available if you want to publish a new version of a DataObject without cascade publishing all its children.

#### Ownership of unversioned DataObject

An unversioned DataObject can own other versioned DataObject. An unversioned DataObject can be configured to automatically publish children versioned DataObjects on save.

An unversioned DataObject can also be owned by a versioned DataObject. This can be used to recursively publish _children-of-children_ DataObject without requiring the intermediate relationship to go through a versioned DataObject. This behavior can be helpful if you wish to group multiple versioned DataObject together.

#### Ownership through media insertion in content

Images and other files are tracked as versioned DataObject. If a file is referenced through an HTML text field, it needs to be published for it to be accessible to the public. SilverStripe will automatically pick up when a DataObject references a files through an HTML text field and recursively publish those files.

This behavior works both for versioned and unversioned DataObjects.

### Grouping versioned DataObjects into a ChangeSet (aka Campaigns)

Sometimes, multiple pages or records may be related in organic ways that can not be properly expressed through an ownership relation. There's still value in being able to publish those as a block. 

For example, your editors may be about to launch a new contest through their website. They've drafted a page to promote the contest, another page with the rules and conditions, a registration page for users to sign up, some promotional images, new sponsors records, etc. All this content needs to become visible simultaneously. 

Changes to many DataObjects can be grouped together using the [`ChangeSet`](api:SilverStripe\Versioning\ChangeSet) object. In the CMS, editors can manage `ChangeSet` through the "Campaign" section, if the `silverstripe/campaign-admin` module is installed). By grouping a series of content changes together as on74e cohesive unit, content editors can bulk publish an entire body of content all at once, which affords them much more power and control over interdependent content types.

Records can be added to a changeset in the CMS by using the "Add to campaign" button
that is available on the edit forms of all pages and files. Programmatically, this is done by creating a `SilverStripe\Versioned\ChangeSet` object and invoking its `addObject(DataObject $record)` method.

<div class="info" markdown="1">
Any DataObject can exist in any number of changesets, and even added to a changeset in advance of being published. While a record need not have modifications to be part of a changeset, for practical purposes, changesets are only concerned with records that have modifications.
</div>

#### Implicit vs. Explicit inclusions

Items can be added to a changeset in two ways -- *implicitly* and *explicitly*.

An *implicit* inclusion occurs when a record is added to a changeset by virtue of another object declaring ownership of it via the `$owns` setting. Implicit inclusion of owned objects ensures that when a changeset is published, the action cascades through not only all of the items explicitly added to the changeset, but also all of the records that each of those items owns.

An *explicit* inclusion is much more direct, occurring only when a user has opted to include a record in a changeset either through the UI or programatically.

It is possible for an item to be included both implicitly and explicitly in a changeset. For instance, if a page owns a file, and the page gets added to a changeset, the file is implicitly added. That same file, however, can still be added to the changeset explicitly through the file editor. In this case, the file is considered to be *explicitly* added. If the file is later removed from the changeset, it is then considered *implicitly* added, due to its owner page still being in the changeset.

## Implementing a versioned DataObject

This section explains how to take a regular DataObject and add versioning to it.

### Applying the `Versioned` extension to your DataObject

```php
<?php
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;

class MyStagedModel extends DataObject
{
    private static $extensions = [
        Versioned::class,
    ];
}
```

Alternatively, staging can be disabled, so that only versioned changes are tracked for your model. This
can be specified by using the `.versioned` service variant that provides only version history, and no
staging.

```php
<?php
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class VersionedModel extends DataObject
{
    private static $extensions = [
        Versioned::class . '.versioned',
    ];
}
```

<div class="notice" markdown="1">
The extension is automatically applied to `SiteTree` class. For more information on extensions see
[Extending](../extending) and the [Configuration](../configuration) documentation.
</div>

<div class="warning" markdown="1">
Versioning only works if you are adding the extension to the base class. That is, the first subclass
of `DataObject`. Adding this extension to children of the base class will have unpredictable behaviour.
</div>


### Defining ownership between related versioned DataObjects

You can use the `owns` static private property on a DataObject to specify which relationships are ownership relationships. The `owns` property should be defined on the _owner_ DataObject.

For example, let's say you have a `MyPage` page type that displays banners containing an image. Each `MyPage` owns many `Banners`, which in turn owns an `Image`. 


```php
<?php
use SilverStripe\Versioned\Versioned;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use Page;

class MyPage extends Page
{
    private static $has_many = [
        'Banners' => Banner::class
    ];
    private static $owns = [
        'Banners'
    ];
}
class Banner extends DataObject
{
    private static $extensions = [
        Versioned::class
    ];
    private static $has_one = [
        'Parent' => MyPage::class,
        'Image' => Image::class,
    ];
    private static $owns = [
        'Image'
    ];
}
```

If a `MyPage` gets published, all its related `Banners` will also be published, which will cause all `Image` DataObjects to be published. 

Note that ownership cannot be used with polymorphic relations. E.g. has_one to non-type specific `DataObject`.

#### Unversioned DataObject ownership (SilverStripe 4.1 and above)

Ownership can be used with non-versioned DataObjects, as the necessary functionality is included by default
by the versioned object through the [`RecursivePublishable`](api:SilverStripe\Versioned\RecursivePublishable) extension which is
applied to all objects.

However, it is important to note that even when saving un-versioned objects, it is necessary to use
`->publishRecursive()` to trigger a recursive publish.

`owns` works the same regardless of whether these objects are versioned, so you can use any combination of
versioned or unversioned dataobjects. You only need to call `->publishRecursive()` on the top most
object in the tree.

#### DataObject ownership with custom relations

In some cases you might need to apply ownership where there is no underlying db relation, such as
those calculated at runtime based on business logic. In cases where you are not backing ownership
with standard relations (has_one, has_many, etc) it is necessary to declare ownership on both
sides of the relation.

This can be done by creating methods on both sides of your relation (e.g. parent and child class)
that can be used to traverse between each, and then by ensuring you configure both
`owns` config (on the parent) and `owned_by` (on the child).

E.g.

```php
<?php
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;

class MyParent extends DataObject
{
    private static $extensions = [
        Versioned::class
    ];
    private static $owns = [
        'ChildObjects'
    ];
    public function ChildObjects()
    {
        return MyChild::get();
    }
}
class MyChild extends DataObject
{
    private static $extensions = [
        Versioned::class
    ];
    private static $owned_by = [
        'Parent'
    ];
    public function Parent()
    {
        return MyParent::get()->first();
    }
}
```

#### DataObject Ownership in HTML Content

If you are using [`DBHTMLText`](api:SilverStripe\ORM\FieldType\DBHTMLText) or [`DBHTMLVarchar`](api:SilverStripe\ORM\FieldType\DBHTMLVarchar) fields in your `DataObject::$db` definitions,
it's likely that your authors can insert images into those fields via the CMS interface.
These images are usually considered to be owned by the `DataObject`, and should be published alongside it.
The ownership relationship is tracked through an `[image]` [shortcode](/developer-guides/extending/shortcodes),
which is automatically transformed into an `<img>` tag at render time. In addition to storing the image path,
the shortcode references the database identifier of the `Image` object.

### Controlling how CMS users interact with versioned DataObjects

By default the versioned module includes a `VersionedGridfieldDetailForm` that can extend gridfield with versioning support for models.

You can enable this on a per-model basis using the following code:

```php
<?php
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
class MyBanner extends DataObject {
    private static $extensions = [
        Versioned::class,
    ];
    private static $versioned_gridfield_extensions = true;
}
```

This can be manually enabled for a single gridfield, alternatively, by setting the following option on the
GridFieldDetailForm component.

```php
<?php
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;
class Page extends SiteTree
{
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $config = GridFieldConfig_RelationEditor::create();
        $config
            ->getComponentByType(GridFieldDetailForm::class)
            ->setItemRequestClass(VersionedGridFieldItemRequest::class);
        $gridField = GridField::create('Items', 'Items', $this->Items(), $config);
        $fields->addFieldToTab('Root.Items', $gridField);
        return $fields;
    }
}
```

## Interacting with versioned DataObjects

This section deals with specialised oeprations that can be performed on versioned DataObjects.

### Reading latest versions by stage

By default, all records are retrieved from the "Draft" stage (so the `MyRecord` table in our example). You can
explicitly request a certain stage through various getters on the `Versioned` class.

```php
<?php
use SilverStripe\Versioned\Versioned;

// Fetching multiple records
$stageRecords = Versioned::get_by_stage('MyRecord', Versioned::DRAFT);
$liveRecords = Versioned::get_by_stage('MyRecord', Versioned::LIVE);

// Fetching a single record
$stageRecord = Versioned::get_by_stage('MyRecord', Versioned::DRAFT)->byID(99);
$liveRecord = Versioned::get_by_stage('MyRecord', Versioned::LIVE)->byID(99);
```

### Reading historical versions

The above commands will just retrieve the latest version of its respective stage for you, but not older versions stored
in the `<class>_versions` tables.

```php
<?php
use SilverStripe\Versioned\Versioned;
$historicalRecord = Versioned::get_version('MyRecord', <record-id>, <version-id>);
```

<div class="alert" markdown="1">
The record is retrieved as a `DataObject`, but saving back modifications via `write()` will create a new version,
rather than modifying the existing one.
</div>

In order to get a list of all versions for a specific record, we need to generate specialized [Versioned_Version](api:SilverStripe\Versioned\Versioned_Version)
objects, which expose the same database information as a `DataObject`, but also include information about when and how
a record was published.

```php
<?php
$record = MyRecord::get()->byID(99); // stage doesn't matter here
$versions = $record->allVersions();
echo $versions->First()->Version; // instance of Versioned_Version
```

### Writing changes to a versioned DataObject

When you call the `write()` method on a versioned DataObject, this will transparently create a new version of this DataObject in the the _Stage_ stage.

To write your changes without creating new version, call `writeWithoutVersion()` instead.(api:SilverStripe\Versioned\Versioned::writeWithoutVersion()) instead.
```php
<?php

$record = MyRecord::get()->byID(99); // This wil retrieve the latest draft version of record ID 99.
echo $record->Version; // This will output the version ID. Let's assume it's 13. 


$record->Title = "Foo Bar";
$record->write(); // This will create a new version of record ID 99.
echo $record->Version; // Will output 14.

$record->Title = "FOO BAR";
$record->writeWithoutVersion();
echo $record->Version; // Will still output 14.
```

Similarly, an "unpublish" operation does the reverse, and removes a record from a specific stage.

### Publishing a versioned DataObject

There's two main methods used to publish a versioned DataObject:
* `publishSingle()` publishes this record to live from the draft
* `publishRecursive()` publishes this record, and any dependant objects this record may refer to.

In most regular cases, you'll want to use `publishRecursive`.

`publishRecursive` can be called on unversioned DataObject as well if they implement the `RecursivePublishable` extension.

```php
<?php
$record = MyRecord::get()->byID(99);
$record->MyField = 'changed';

// Will create a new revision in Stage. Editors will be able to see this revision, but not visitors to the website.
$record->write();

// This will publish the changes so they are visible publicly.
$record->publishRecursive();
```

### Unpublishing and archiving a versioned DataObject

Archiving and unpublishing are similar operations, both will prevent a versioned DataObject from being publicly accessible. Archiving will also remove the record from the _Stage_ stage ; other ORMs may refer to this concept as _soft-deletion_.

Use `doUnpublish()` to unpublish an item. Simply call `delete()` to archive an item. The SilverStripe ORM doesn't allow you to _hard-delete_ versioned DataObjects. 

```php
<?php
$record = MyRecord::get()->byID(99);

// Visitors to the site won't be able to see this record anymore, but editors can still edit it and re-publish it. 
$record->doUnpublish();


// Editors won't be able to see this record anymore, but it will still be in the database and may be restore.
$record->delete();
```

Note that `doUnpublish()` and `doArchive()` do not work recursively. If you wish to unpublish or archive dependants records, you have to it manually.

### Rolling back to an older version
Rolling back allows you to return a DataObject to a previous state. You can rollback a single DataObject using the `rollbackSingle()` method. You can also rollback all dependant records using the `rollbackRecursive()` method.

Both `rollbackSingle()` and `rollbackRecursive()` expect a single argument, which may be a specific version ID or a stage name.

```php
<?php
use SilverStripe\Versioned\Versioned;

$record = MyRecord::get()->byID(99);

// This will take the current live version of record ID 99 - and all it's associated DataObjects - and copy it to the
// "Stage" stage. This is equivalent to dismissing any draft work and reverting to what was last published.
$record->rollbackRecursive(Versioned::LIVE);

// This will restore version 10 of record ID 99 to "Stage" without affecting any owned DataObjects.
$record->rollbackSingle(10);

// The live version of the record won't be affected unless you publish you're rolled back record.
$record->publishRecursive();
```

Note that internally, rolling back a DataObject creates a new version identical of the restored version ID. For example,
if the live version of `$record` is 10 and the staged version is 13, rolling back to live will create a version 14 in _Stage_ that is identical to version 10.

### Restoring an archived version

Archived records can still be retrieved using `get_including_deleted()`. This will include archived as well as current records. You can use the `isArchived()` method to determine if a record is archived or not. Calling the `write()` method on an archived record will restore it to the _Stage_ stage.

```php
<?php
use MyRecord;
use SilverStripe\Versioned\Versioned;

// This script will restore all archived entries for MyRecord.
$allMyRecords = Versioned::get_including_deleted(MyRecord::class);
foreach ($allMyRecords as $myRecord)
{
    if ($myRecord->isArchived()) {
        $myRecord->write();
    }
}
```

## Interacting with ChangeSet

This section explains how you can interact with ChangeSets.

### Adding and removing DataObjects to a change set

* `$myChangeSet->addObject(DataObject $record)`: Add a record and all of its owned records to the changeset (`canEdit()` dependent).
* `$myChangeSet->removeObject(DataObject $record)`: Removes a record and all of its owned records from the changeset (`canEdit()` dependent).

### Performing actions on the ChangeSet object

* `$myChangeSet->publish()`: Publishes all items in the changeset that have modifications, along with all their owned records (`canPublish()` dependent). Closes the changeset on completion.
* `$myChangeSet->sync()`: Find all owned records with modifications for each item in the changeset, and include them implicitly.
* `$myChangeSet->validate()`: Ensure all owned records with modifications for each item in the changeset are included. This method should not need to be invoked if `sync()` is being used on each mutation to the changeset.

### Getting information about the state of the ChangeSet

ChangeSets can exists in three different states:

* `open` No action has been taken on the ChangeSet. Resolves to `publishing` or `reverting`.
* `published`: The ChangeSet has published changes to all of its items and its now closed.
* `reverted`: The ChangeSet has reverted changes to all of its items and its now closed. (Future API, not supported yet)

### Getting information about items in a ChangeSet

Each item in the ChangeSet stores `VersionBefore` and `VersionAfter` fields. As such, they can compute the type of change they are adding to their parent ChangeSet. Change types include:

* `created`: This ChangeSet item is for a record that does not yet exist
* `modified`: This ChangeSet item is for a record that differs from what is on the live stage
* `deleted`: This ChangeSet item will no longer exist when the ChangeSet is published
* `none`: This ChangeSet item is exactly as it is on the live stage

## Advanced versioning topics

These topics are targeted towards more advanced use cases that might require developers to extend the behavior of versioning.

### How versioned DataObjects are tracked in the database

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

Because `many_many` relationships create their own sets of records on their own tables, representing content changes to a DataObject, they can therefore be versioned. This is done using the ["through" setting](https://docs.silverstripe.org/en/4/developer_guides/model/relations/#many-many-through-relationship-joined-on-a-separate-dataobject) on a `many_many` definition. This setting allows you to specify a custom DataObject through which to map the `many_many` relation. As such, it is possible to version your `many_many` data by versioning a "through" dataobject. For example:

```php
<?php
use SilverStripe\ORM\DataObject;

class Product extends DataObject
{
    private static $db = [
        'Title' => 'Varchar(100)',
        'Price' => 'Currency',
    ];

    private static $many_many = [
        'Categories' => [
            'through' => 'ProductCategory',
            'from' => 'Product',
            'to' => 'Category',
        ],
    ];
}
```

```php
<?php
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class ProductCategory extends DataObject
{
    private static $db = [
        'SortOrder' => 'Int',
    ];

    private static $has_one = [
        'Product' => Product::class,
        'Category'=> Category::class,
    ];

    private static $extensions = [
        Versioned::class,
    ];
}
```

### Writing custom queries to retrieve versioned DataObject

We generally discourage writing `Versioned` queries from scratch, due to the complexities involved through joining
multiple tables across an inherited table scheme (see [Versioned::augmentSQL()](api:SilverStripe\Versioned\Versioned::augmentSQL())). If possible, try to stick to smaller modifications of the generated `DataList` objects.

Example: Get the first 10 live records, filtered by creation date:

```php
<?php
use SilverStripe\Versioned\Versioned; 
$records = Versioned::get_by_stage('MyRecord', Versioned::LIVE)->limit(10)->sort('Created', 'ASC');
```

### Controlling what stage is displayed in the front end

The current stage for each request is determined by `VersionedHTTPMiddleware` before any controllers initialize, through
`Versioned::choose_site_stage()`. It checks for a `stage` GET parameter, so you can force a draft stage by appending
`?stage=Stage` to your request.

Since SilverStripe 4.2, the current stage setting is no longer "sticky" in the session.
Any links presented on the view produced with `?stage=Stage` need to have the same GET parameters in order
to retain the stage. If you are using the `SiteTree->Link()` and `Controller->Link()` methods,
this is automatically the case for `DataObject` links, controller links and form actions.
Note that this behaviour applies for unversioned objects as well, since the views
these are presented in might still contain dependant objects that are versioned.

You can opt for a session base stage setting through the `Versioned.use_session` setting.
Warning: This can lead to leaking of unpublished information, if a live URL is viewed in draft mode,
and the result is cached due to agressive cache settings (not varying on cookie values).   

*app/src/MyObject.php*

```php
<?php
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\Controller;

class MyObject extends DataObject {

    private static $extensions = [
        Versioned::class
    ];

    public function Link()
    {
        return Injector::inst()->get(MyObjectController::class)->Link($this->ID);
    }

    public function CustomLink()
    {
        $link = Controller::join_links('custom-route', $this->ID, '?rand=' . rand());
        $this->extend('updateLink', $link); // updates $link by reference
        return $link;
    }

    public function LiveLink()
    {
        // Force live link even when current view is in draft mode
        return Controller::join_links(Injector::inst()->get(MyObjectController::class)->Link($this->ID), '?stage=Live');
    }
}
```

*app/src/MyObjectController.php*

```php
<?php
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class MyObjectController extends Controller
{
    public function index(HTTPRequest $request)
    {
        $obj = MyObject::get()->byID($request->param('ID'));
        if (!$obj) {
            return $this->httpError(404);
        }

        // Construct view
        $html = sprintf('<a href="%s">%s</a>', $obj->Link(), $obj->ID);

        return $html;
    }

    public function Link($action = null)
    {
        // Construct link with graceful handling of GET parameters
        $link = Controller::join_links('my-objects', $action);

        // Allow Versioned and other extension to update $link by reference.
        // Calls VersionedStateExtension->updateLink().
        $this->extend('updateLink', $link, $action);

        return $link;
    }
}
```

*app/_config/routes.yml*

```yaml
SilverStripe\Control\Director:
  rules:
    'my-objects/$ID': 'MyObjectController'
```

<div class="alert" markdown="1">
The `choose_site_stage()` call only deals with setting the default stage, and doesn't check if the user is
authenticated to view it. As with any other controller logic, please use `DataObject->canView()` to determine
permissions, and avoid exposing unpublished content to your users.
</div>

### Controlling permissions to versioned DataObjects

By default, `Versioned` will come out of the box with security extensions which restrict the visibility of objects in Draft (stage) or Archive viewing mode.

<div class="alert" markdown="1">
As is standard practice, user code should always invoke `canView()` on any object before
rendering it. DataLists do not filter on `canView()` automatically, so this must be
done via user code. This be be achieved either by wrapping `<% if $canView %>` in
your template, or by implementing your visibility check in PHP.
</div>

#### Version specific _can_ methods

Versioned DataObjects get additional permission check methods to verify what oepration a Member is allowed to perform:
* `canPublish()`
* `canUnpublish()`
* `canArchive()`
* `canViewVersioned()`.

These methods accept an optional Member argument. If not provided, they will assume you want to check the permission against the current Member. When performing version operation on behalf of a Member, you'll probably want to use these method to confirm they are authorised,

```php
<?php
use SilverStripe\Security\Security;

$record = MyRecord::get()->byID(99);
$member = Security::getCurrentUser();
if ($record->canPublish($member)) {
    $record->publishRecursive();
}

```

There's also a `canViewStage()` method which can be use to check if a Member can access a specific stage.

```php
<?php
use SilverStripe\Versioned\Versioned;

// Check if `$member` can view the Live version of $record.
$record->canViewStage(Versioned::LIVE, $member);

// Check if `$member` can view the Stage version of $record.
$record->canViewStage(Versioned::DRAFT, $member);

// Both parameters are optional. This is equivalent to calling the method with Versioned::LIVE and
// Security::getCurrentUser();
$record->canViewStage();
```

#### Customising permissions for a versioned DataObject

Versioned object visibility can be customised in one of the following ways by editing your user code:

 * Override the `canViewVersioned` method in your code. Make sure that this returns true or
   false if the user is not allowed to view this object in the current viewing mode.
 * Override the `canView` method to override the method visibility completely.

E.g.

```php
<?php
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject
{
    private static $extensions = [
        Versioned::class,
    ];

    public function canViewVersioned($member = null)
    {
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
```

If you want to control permissions of an object in an extension, you can also use
one of the below extension points in your `DataExtension` subclass:

 * `canView` to update the visibility of the object's `canView`
 * `canViewNonLive` to update the visibility of this object only in non-live mode.

Note that unlike canViewVersioned, the canViewNonLive method will
only be invoked if the object is in a non-published state.

E.g.

```php
<?php
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataExtension;

class MyObjectExtension extends DataExtension
{
    public function canViewNonLive($member = null)
    {
        return Permission::check($member, 'DRAFT_STATUS');
    }
}
```

If none of the above checks are overridden, visibility will be determined by the
permissions in the `TargetObject.non_live_permissions` config.

E.g.

```php
<?php
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject
{
    private static $extensions = [
        Versioned::class,
    ];
    private static $non_live_permissions = ['ADMIN'];
}
```

Versioned applies no additional permissions to `canEdit` or `canCreate`, and such
these permissions should be implemented as per standard unversioned DataObjects.

### Page Specific Operations

Since the `Versioned` extension is primarily used for page objects, the underlying `SiteTree` class has some additional helpers.

#### Templates Variables

In templates, you don't need to worry about this distinction. The `$Content` variable contain the published content by
default, and only preview draft content if explicitly requested (e.g. by the "preview" feature in the CMS, or by adding ?stage=Stage to the URL). If you want
to force a specific stage, we recommend the `Controller->init()` method for this purpose, for example:

**app/code/MyController.php**
```php
public function init()
{
    parent::init();
    Versioned::set_stage(Versioned::DRAFT);
}
```

### Low level write and publication methods

SilverStripe will usually call these low level methods for you when you. However if you have specialised needs, you may call them directly.

To move a saved version from one stage to another, call [writeToStage(<stage>)](api:SilverStripe\Versioned\Versioned::writeToStage()) on the object. This is used internally to publish DataObjects.

`copyVersionToStage($versionID, $stage)` allow you to restore a previous version to a specific stage. This is used internally when performing a rollback.
   
The current stage is stored as global state on the `Versioned` object. It is usually modified by controllers, e.g. when a preview is initialized. But it can also be set and reset temporarily to force a specific operation to run on a certain stage.

```php
<?php
$origMode = Versioned::get_reading_mode(); // save current mode
$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Live' records
Versioned::set_reading_mode(Versioned::DRAFT); // temporarily overwrite mode
$obj = MyRecord::getComplexObjectRetrieval(); // returns 'Stage' records
Versioned::set_reading_mode($origMode); // reset current mode
```


## API Documentation

* [Versioned](api:SilverStripe\Versioned\Versioned)
