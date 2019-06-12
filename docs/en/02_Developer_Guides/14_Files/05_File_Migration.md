title: File migration
summary: Manage migration of legacy files to the new database structure

# File migration

This section describes how to upgrade existing filesystems from earlier versions of SilverStripe.

## Running migration

Since the structure of `File` objects has changed between 3.x and 4.x, a new task `MigrateFileTask`
has been added to assist in migration of legacy files.

You can run this task on the command line:

```
$ ./vendor/bin/sake dev/tasks/MigrateFileTask
```

This task will perform a number of subtasks:

 - `move-files`: Migrates existing `File` objects by adding required metadata to the database (incl. versioning).
   By default, it will not move files on the filesystem (starting with [4.4.0](/changelogs/4.4.0)).
   Publishes to the live stage to ensure
   that previously visible assets remain visible to the public site.
   If additional security or visibility rules should be applied to `File`, then
   make sure to correctly extend `canView` via extensions.
 - `move-thumbnails`: Move existing thumbnails, rather than have them generated on the fly.
   This task is optional, but helps to avoid growing your asset folder (no duplicate thumbnails)
 - `generate-cms-thumbnails`: The new CMS UI needs different thumbnail sizes, which can be pregenerated.
    This can be a CPU and memory intensive task for large asset stores.
    See [Migrating substantial number of files](#performance)
 - `fix-secureassets`: Migrates files secured through the [silverstripe/secureassets](https://github.com/silverstripe/silverstripe-secureassets) module.
    Ensures that previous `.htaccess` folder protections don't interfere with 4.x-style asset protections.
 - `fix-folder-permissions`: Fixes folder permissions which might have been broken by
    previously using the [silverstripe/secureassets](https://github.com/silverstripe/silverstripe-secureassets)

One or more subtasks can be run individually through the `only` argument.
Example: `only=move-files,move-thumbnails`

The output is quite verbose by default. Look for `WARNING` and `ERROR` in the log files.
When executing the task on CLI, you'll get colour coded error messages.

## Background migration through the Queuedjobs module

In general, it's safest to run the file migration on a non-production environment,
and switch over to the migrated assets and new database. That's not always feasible.
You can also run this task without CLI access through the [queuedjobs](https://github.com/symbiote/silverstripe-queuedjobs) module.
Due to the incremental nature of this migration, you need to consider how a partially migrated database
and asset store will impact your own site's behaviour, or how you can recover from migration errors.
When running a background migration in production, we recommend to put your site in maintenance mode to avoid inconsistent behaviour.

Open up `admin/queuedjobs`, then create a job of type `RunBuildTaskJob`.
The only constructor parameter allowed is the full name of the task: `SilverStripe\Dev\Tasks\MigrateFileTask`.
The task output will be progressively written to the job record, and can be inspected via the "Messages" tab within the job in the CMS.
It attempts to continue running to "complete" status even if it encounters errors, so you'll need to review the logs
to ensure if everything went smoothly. Note that it's currently not possible to run specific subtasks via a queuedjob.

While you can run the job directly through the CMS, it'll usually be more constrained by PHP `max_execution_time` settings.
Many platforms such as the New Zealand Government Common Web Platform or SilverStripe Platform
are configured to run jobs automatically without time limits
([1](https://docs.platform.silverstripe.com/development/platform-yml-file/#cron-tasks),
[2](https://www.cwp.govt.nz/developer-docs/en/2/working_with_projects/infrastructural_considerations/)).
It is not recommended to run
[multiple processes](https://github.com/symbiote/silverstripe-queuedjobs/blob/master/docs/en/configuring-multi-process-execution.md)
when executing the file migration job. 

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
SilverStripe\Assets\Dev\Tasks\FileMigrationHelper:
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

## Migrating substantial numbers of files {#performance}

The time it takes to run the file migration will depend on the number of files and their size. The generation of thumbnails will depend on the number and dimension of your images.
In general, the migration task can be restarted if it times out, and will continue where it left off. 

If you are migrating a substantial number of files, you should run the file migration task either as a queued job or on the command line. If the migration task fails or times out, you can start it again and it will pick up where it left off.

If your environment supports the _Imagick_ PHP library, you may want to use that library instead of _GD_. Imagick is considerably faster when resizing images. You can switch back to _GD_ after running the file migration task.

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


## Natural path vs Hash Path

Two types of paths are used by SilverStripe CMS when serving files:
* Natural paths and
* Hash paths.

Natural paths will match the path displayed in SilverStripe's asset management section (e.g.: `assets/Folder/form.pdf`). Hash paths will include a partial hash as directory name (e.g.: `assets/Folder/282318025b/form.pdf`). The hash is generated by computing the sha1 hash of the provided file and keeping the first ten characters.

The hash path has the advantage of being unique for every version of the file your users might upload. This allows multiple versions of the same file to co-exist, enabling features like [file archives](04_File_Storage#archived). This is why hash path is the default file format for the protected file store.

The Natural path has the advantage of being easier to understand and permanent. When a newer version of a file is published to a natural path, it will be accessible at the same URL as the previous version. This is why natural path is the default file format for the public file store.

[Learn more about SilverStripe File Storage](04_File_Storage)

## Migration from SilverStripe 3 to Silverstripe 4.4 or later

While the SilverStripe 3 file paths may look similar to the natural paths in SilverStripe 4.4, the database entries for each file have to be normalised to conform to the new SilverStripe 4 structure. File variants (e.g. image thumbnails) are stored differently in SilverStripe 4. They will automatically be moved to their new location.

SilverStripe 3 did not support draft files which means that all migrated SilverStripe 3 files will automatically be published by the File Migration Task.

File names can not contain "double underscores" in SilverStripe 4 (`__`) because those characters are used to indicate a "file variant". SilverStripe 3 files not conforming to this rule will automatically be renamed by the file migration task to remove extra underscores.

### Will old URLs to SilverStripe 3 files still resolve?

In most cases, the SilverStripe 3 file path will be identical to the public path of the file in SilverStripe 4. Links to SilverStripe 3 file variants (e.g. cropped images) will automatically be redirected to their new location.

### What about file references in `HTMLText` database fields?

The shortcode format in SilverStripe 4 has changed from SilverStripe 3. This means that when CMS editors try to edit existing content and alter image or file links, the reference to the file will not work.

After running the file migration, you can run the short code migration task to update your existing HTML content in your database:

```bash
sake dev/tasks/TagsToShortcode
``` 

This will rewrite your existing shortcodes to the newer format SilverStripe 4 expects as well as convert `img` and `a` tags to use shortcodes..

## Migrating from SilverStripe 4.3 or below to SilverStripe 4.4 or above

Silverstripe 4.0 to 4.3 were using hash paths for the public file store as well as the protected file store. This meant that every time a new version of a file was published, it would be served under a different URL.

SilverStripe 4.4 and later default to using natural paths for public store files. After upgrading to SilverStripe 4.4 or above, you can run the file migration task to normalise your existing files.

This will take all existing files under a hash path and move them to their natural path.

### Do I need to re-migrate my files?

No, you do not need to run the file migration task when upgrading to SilverStripe 4.4 or later. 

Your existing files will still resolve. Old hash paths will be redirected to the latest version of the file. As newer versions of existing files are published, they will be moved to their natural path.

You can choose to run the migration task at any point.

### What if I’m using `legacy_filenames`?

SilverStripe 4.0 to 4.3 allowed you to enable a `legacy_filenames` mode. This would be equivalent to using natural paths for both the public store and the protected store.

With the introduction of natural paths for the public store, the primary reason for `legacy_filenames` mode is no longer relevant. `legacy_filenames` mode is now considered deprecated, however leaving it enabled will not have any impact on your project – it will simply be ignored.

After upgrading to SilverStripe 4.4 or later, you should run the file migration task to normalise your protected files. This will move your protected files to their equivalent hash path.

###  What if I want to keep using hash paths for my public files?

If you want to keep using hash paths for your public store, you can add the following entries to your YML configuration files.

```yml
SilverStripe\Core\Injector\Injector:
  # Define public resolution strategy
  SilverStripe\Assets\FilenameParsing\FileResolutionStrategy.public:
    class: SilverStripe\Assets\FilenameParsing\FileIDHelperResolutionStrategy
    properties:
    DefaultFileIDHelper: '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
    ResolutionFileIDHelpers:
      - '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
      VersionedStage: Live
  # Define protected resolution strategy
  SilverStripe\Assets\FilenameParsing\FileResolutionStrategy.protected:
    class: SilverStripe\Assets\FilenameParsing\FileIDHelperResolutionStrategy
    properties:
      DefaultFileIDHelper: '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
      ResolutionFileIDHelpers:
        - '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
      VersionedStage: Stage
```

## Disabling old file resolution format

When starting a brand new project, or after you’ve ran the file migration task, all your files will be in their default locations. This gives you the option to disable alternative resolution formats. This will slightly speed up file resolution.

You can do this by adding this snippet to your YML configuration.

```yml
SilverStripe\Core\Injector\Injector:
  # Define public resolution strategy
  SilverStripe\Assets\FilenameParsing\FileResolutionStrategy.public:
    class: SilverStripe\Assets\FilenameParsing\FileIDHelperResolutionStrategy
    properties:
      ResolutionFileIDHelpers:
      - '%$SilverStripe\Assets\FilenameParsing\NaturalFileIDHelper'
      DefaultFileIDHelper: '%$SilverStripe\Assets\FilenameParsing\NaturalFileIDHelper'
      VersionedStage: Live
    # Define protected resolution strategy
  SilverStripe\Assets\FilenameParsing\FileResolutionStrategy.protected:
    class: SilverStripe\Assets\FilenameParsing\FileIDHelperResolutionStrategy
    properties:
      DefaultFileIDHelper: '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
      ResolutionFileIDHelpers:
       - '%$SilverStripe\Assets\FilenameParsing\HashFileIDHelper'
      VersionedStage: Stage
```

If you're starting a brand new project using the `silverstripe/installer` 4.4 or above, this code snippet will already be part of your project.

## Customise the File Migration Task (Advanced)

In some context, you may want to disable some other process when the file migration is running. For example, if you have a module that indexes files when they get modified, you'll probably want to wait until the file migration is done to reindex.

The `MigrateFileTask` exposes 4 extension point that can be use to detect the progress of the migration.
* `preFileMigration` that gets fired at the start of the task
* `postFileMigration` that gets fired at the end of the task
* `preFileMigrationSubtask`  that gets fired at the start of each subtasks
* `postFileMigrationSubtask` that gets fired at the end of each subtasks.

`preFileMigrationSubtask` and `postFileMigrationSubtask` will provide a single string parameter matching the name of the subtask (e.g.: `move-files`)

### Example migrate file task extension
```php
<?php

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Extension;

class MigrateFileTaskExtension extends Extension
{

    private static $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.quiet',
    ];

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function preFileMigration()
    {
        $this->logger->info('Run some extension code BEFORE the Migrate File Task');
    }

    public function postFileMigration()
    {
        $this->logger->info('Run some extension code AFTER the Migrate File Task');
    }

    public function preFileMigrationSubtask($subtaskName)
    {
        $this->logger->info(sprintf('Run some extension code BEFORE the %s subtask', $subtaskName));
    }

    public function postFileMigrationSubtask($subtaskName)
    {
        $this->logger->info(sprintf('Run some extension code AFTER the %s subtask', $subtaskName));
    }

}
```

Add the following snippet to your YML config to enable the extension.

```yaml
SilverStripe\Dev\Tasks\MigrateFileTask:
  extensions:
    - MigrateFileTaskExtension
```
