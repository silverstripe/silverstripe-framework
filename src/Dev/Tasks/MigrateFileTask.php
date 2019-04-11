<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper;
use SilverStripe\ORM\DB;
use SilverStripe\Assets\FileMigrationHelper;
use SilverStripe\Dev\BuildTask;

/**
 * Migrates all 3.x file dataobjects to use the new DBFile field.
 */
class MigrateFileTask extends BuildTask
{

    private static $segment = 'MigrateFileTask';

    protected $title = 'Migrate File dataobjects from 3.x';

    protected $description =
        'Imports all files referenced by File dataobjects into the new Asset Persistence Layer introduced in 4.0. ' .
        'If the task fails or times out, run it again and it will start where it left off.';

    public function run($request)
    {
        if (!class_exists(FileMigrationHelper::class)) {
            DB::alteration_message("No file migration helper detected", "notice");
            return;
        }

        DB::alteration_message(
            'If the task fails or times out, run it again and it will start where it left off.',
            "info"
        );

        $migrated = FileMigrationHelper::singleton()->run();
        if ($migrated) {
            DB::alteration_message("{$migrated} File DataObjects upgraded", "changed");
        } else {
            DB::alteration_message("No File DataObjects need upgrading", "notice");
        }

        if (!class_exists(ImageThumbnailHelper::class)) {
            DB::alteration_message("No image thumbnail helper detected", "notice");
            return;
        }
        ImageThumbnailHelper::singleton()->run();
    }
}
