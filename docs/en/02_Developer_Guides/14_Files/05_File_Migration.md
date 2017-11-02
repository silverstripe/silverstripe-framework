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

Because the filesystem now uses the sha1 of file contents in order to version multiple versions under the same
filename, the default storage paths in 4.0 will not be the same as in 3.

Although it is not recommended, it is possible to configure the backend to omit this SHA1 url segment,
meaning that file paths and urls will not be modified during the upgrade.

This is done by setting this config:

```yaml
SilverStripe\Assets\Flysystem\FlysystemAssetStore:
  legacy_filenames: true
```

Note that this will not allow you to utilise certain file versioning features in 4.0.
