title: File migration
summary: Manage migration of legacy files to the new database structure

# File migration

This section describes how to upgrade existing filesystems from earlier versions of SilverStripe.

## Running migration

Since the structure of `File` dataobjects has changed between 3.x and 4.x, a new task `MigrateFileTask`
has been added to assist in migration of legacy files.

You can run this task on the command line:

```
$ ./vendor/bin/sake dev/tasks/MigrateFileTask
```

This task will also support migration of existing File objects to file versioning. Any
pre-existing File objects will be automatically published to the live stage, to ensure
that previously visible assets remain visible to the public site.
If additional security or visibility rules should be applied to File dataobjects, then
make sure to correctly extend `canView` via extensions.

Imports all files referenced by File dataobjects into the new Asset Persistence Layer introduced in 4.0.
Moves existing thumbnails, and generates new thumbnail sizes for the CMS UI.
If the task fails or times out, run it again and it will start where it left off.

Arguments:

 - `only`: Comma separated list of tasks to run on the multi-step migration (see "Available subtasks").
   Example: `only=move-files,move-thumbnails`

Availabile subtasks:

 - `move-files`: The main task, moves database and filesystem data
 - `move-thumbnails`: Move existing thumbnails, rather than have them generated on the fly.
    This task is optional, but helps to avoid growing your asset folder (no duplicate thumbnails)
 - `generate-cms-thumbnails`: The new CMS UI needs different thumbnail sizes, which can be pregenerated.
    This can be a CPU and memory intensive task for large asset stores.

You can also run this task without CLI access through the [queuedjobs](https://github.com/symbiote/silverstripe-queuedjobs) module.

## Migration of existing thumbnails

Thumbnails generated through SilverStripe's image manipulation layer can be created by authors
resizing images in the rich text editor, through template or PHP code, or by SilverStripe's built-in CMS logic.
They are now called "variants", and are placed in a different folder structure. In order to avoid re-generating those thumbnails,
and cluttering up your asset store with orphaned files, the task will move them to the new location by default.

## Discarded files during migration

Note that any File object which is not in the `File.allowed_extensions` config will be deleted
from the database during migration. Any invalid file on the filesystem will not be deleted,
but will no longer be attached to a dataobject anymore, and should be cleaned up manually.

To disable this, set the following config:

```yaml
SilverStripe\Assets\FileMigrationHelper:
  delete_invalid_files: false
```

Pre-existing file security solutions for 3.x (such as
[secure assets module](https://github.com/silverstripe/silverstripe-secureassets))
are likely incompatible with core file security. You should check the module README for potential upgrade paths.

## Keeping archived assets

By default, "archived" assets (deleted from draft and live stage) retain their
historical database entries with the file metadata, but the actual file contents are removed from the filesystem
in order to avoid bloat. If you need to retain file contents (e.g. for auditing purposes),
you can opt-in to this behaviour:

```yaml
SilverStripe\Assets\Flysystem\FlysystemAssetStore:
  keep_archived_assets: true
```

## Migrating substantial number of files

The time it takes to run the file migration will depend on the number of files and their size. The generation of thumbnails will depend on the number and dimension of your images.

If you are migrating a substantial number of files, you should run file migration task either as a queued job or on the command line. If the migration task fails or times out, you can start it again and it will pick up where it left off.

If your environement supports the _Imagick_ PHP library, you may want to use that library instead of _GD_. Imagick is considerably faster when resizing images. You can switch back to _GD_ after running the file migration task.

[Changing the image manipulation driver to Imagick](images#changing-the-manipulation-driver-to-imagick)

If your project hosts big images (e.g. 4K images), this can also affect the amount of memory used to generate the thumbnails. The file migration task assumes that it will have at least 512MB of memory available. 

By default the file migration task will not generate thumbnails for files greater than 9MB to avoid exhausting the available memory. To increase this limit, add the following code to your YML configuration:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper:
    constructor:
      0: '100MB'
```

You can also set this to `0` to disable the limit.

## System Requirements

The approach to running your file migration depends on your system and how many files you are migrating.

Use the following estimates to decide how you will run your file migration:

| Number of files | Method | Expected Execution Time | Approximate Memory Usage |
| --- | --- | --- | --- |
| < 150 | Web Request | 30 seconds | 6 MB |
| < 500 | Queued Job | 120 seconds | 8 MB |
| < 10000 | Command Line | 10000 seconds | 950 MB |
| 10000+ | Command Line or contact support | n/a | n/a |

Your exact experience will vary based on your host server, the size of your files and other conditions. If your site is hosted on a managed environement (e.g.: [Common Web Platform](https://www.cwp.govt.nz/service-desk) or [SilverStripe Platform](https://docs.platform.silverstripe.com/support/)), you may not have access to the command line to manually run the migration task. Contact your hosting provider's helpdesk if that's your case.
