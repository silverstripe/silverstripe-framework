<?php

namespace SilverStripe\Dev\Tasks;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Logging\PreformattedEchoHandler;
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
        $this->addLogHandlers();

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

    /**
     * TODO Refactor this whole mess into Symfony Console on a TaskRunner level,
     * with a thin wrapper to show coloured console output via a browser:
     * https://github.com/silverstripe/silverstripe-framework/issues/5542
     * @throws \Exception
     */
    protected function addLogHandlers()
    {
        if ($logger = Injector::inst()->get(LoggerInterface::class)) {
            if (Director::is_cli()) {
                $logger->pushHandler(new StreamHandler('php://stdout'));
                $logger->pushHandler(new StreamHandler('php://stderr', Logger::WARNING));
            } else {
                $logger->pushHandler(new PreformattedEchoHandler());
            }
        }
    }
}
