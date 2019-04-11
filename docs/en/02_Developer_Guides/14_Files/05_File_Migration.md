title: File migration
summary: Manage migration of legacy files to the new database structure

# File migration

This section describes how to upgrade existing filesystems from earlier versions of SilverStripe.

## Running migration

Since the structure of `File` dataobjects has changed between 3.0 and 4.0, a new task `MigrateFileTask`
has been added to assist in migration of legacy files.

You can run this task on the command line:

```
$ ./vendor/bin/sake dev/tasks/MigrateFileTask
```

This task will also support migration of existing File DataObjects to file versioning. Any
pre-existing File DataObjects will be automatically published to the live stage, to ensure
that previously visible assets remain visible to the public site.

If additional security or visibility rules should be applied to File dataobjects, then
make sure to correctly extend `canView` via extensions.

*IMPORTANT*: There is a [known bug](https://github.com/silverstripe/silverstripe-versioned/issues/177)
which breaks existing direct links to asset URLs unless `legacy_filenames` is set to `true` (see below).

## Automatic migration

Migration can be invoked by either this task, or can be configured to automatically run during dev build
by setting the `File.migrate_legacy_file` config to true. However, it's recommended that this task is
run manually during an explicit migration process, as this process could potentially consume large
amounts of memory and run for an extended time.

```yml
SilverStripe\Assets\File:
  migrate_legacy_file: true
```

## Migration of thumbnails

If you have the [asset admin](https://github.com/silverstripe/silverstripe-asset-admin) module installed
this will also ensure that thumbnails for these images are generated when running 'MigrateFileTask'.
Existing thumbnails will not be migrated however, and must be re-generated for use in the CMS.

Note: Thumbnails can be regenerated on a one-by-one basis within the CMS by re-saving it
within the file edit details form.

## Discarded files during migration

Note that any File dataobject which is not in the `File.allowed_extensions` config will be deleted
from the database during migration. Any invalid file on the filesystem will not be deleted,
but will no longer be attached to a dataobject anymore, and should be cleaned up manually.

To disable this, set the following config:

```yaml
SilverStripe\Assets\FileMigrationHelper:
  delete_invalid_files: false
```

Note that pre-existing security solutions for 3.x (such as
[secure assets module](https://github.com/silverstripe/silverstripe-secureassets))
are incompatible with core file security.

## Support existing paths

Because the filesystem now uses the hash of file contents in order to version multiple versions under the same
filename, the default storage paths in 4.0 will not be the same as in 3.

Although it is not recommended, it is possible to configure the backend to omit this hash url segment,
meaning that file paths and urls will not be modified during the upgrade.
This configuration needs to be chosen before starting the file migration,
and can't be changed after migration.

```yaml
SilverStripe\Assets\Flysystem\FlysystemAssetStore:
  legacy_filenames: true
```

This setting will still allow creation of protected (draft) files before publishing them.
It'll also keep track of changes to file metadata (e.g. title and description).
But it won't keep track of replaced file contents (not compatible with `keep_archived_assets=true`).
When replacing an already published file, the new file will be public right away (no draft stage). 

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
