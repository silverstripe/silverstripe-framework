<?php

namespace SilverStripe\Dev\Tasks;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper;
use SilverStripe\Assets\Dev\Tasks\LegacyThumbnailMigrationHelper;
use SilverStripe\Assets\Dev\Tasks\FileMigrationHelper;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\Storage\FileHashingService;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Logging\PreformattedEchoHandler;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Assets\Dev\Tasks\SecureAssetsMigrationHelper;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;

/**
 * Migrates all 3.x file dataobjects to use the new DBFile field.
 */
class MigrateFileTask extends BuildTask
{
    private static $segment = 'MigrateFileTask';

    protected $title = 'Migrate File dataobjects from 3.x and successive iterations in 4.x';

    protected $defaultSubtasks = [
        'move-files',
        'move-thumbnails',
        'generate-cms-thumbnails',
        'fix-folder-permissions',
        'fix-secureassets',
    ];

    private static $dependencies = [
        'logger' => '%$' . LoggerInterface::class,
    ];

    /** @var Logger */
    private $logger;

    public function run($request)
    {
        $this->addLogHandlers();

        $args = $request->getVars();
        $this->validateArgs($args);

        Injector::inst()->get(FileHashingService::class)->enableCache();

        // Set max time and memory limit
        Environment::increaseTimeLimitTo();
        Environment::setMemoryLimitMax(-1);
        Environment::increaseMemoryLimitTo(-1);

        $this->extend('preFileMigration');

        $this->logger->warn(
            'Please read https://docs.silverstripe.org/en/4/developer_guides/files/file_migration/ ' .
            'before running this task.'
        );

        $subtasks = !empty($args['only']) ? explode(',', $args['only']) : $this->defaultSubtasks;

        $subtask = 'move-files';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(FileMigrationHelper::class)) {
                $this->logger->error("No file migration helper detected");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->notice("######################################################");
                $this->logger->notice("Migrating filesystem and database records ({$subtask})");
                $this->logger->notice("######################################################");

                FileMigrationHelper::singleton()
                    ->setLogger($this->logger)
                    ->run();

                // TODO Split file migration helper into two tasks,
                // and report back on their process counts consistently here
                // if ($count) {
                //     $this->logger->info("{$count} File objects upgraded");
                // } else {
                //     $this->logger->info("No File objects needed upgrading");
                // }

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'move-thumbnails';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(LegacyThumbnailMigrationHelper::class)) {
                $this->logger->error("LegacyThumbnailMigrationHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->notice("#############################################################");
                $this->logger->notice("Migrating existing thumbnails to new file format ({$subtask})");
                $this->logger->notice("#############################################################");

                $paths = LegacyThumbnailMigrationHelper::singleton()
                    ->setLogger($this->logger)
                    ->run($this->getStore());

                if ($paths) {
                    $this->logger->info(sprintf("%d thumbnails moved", count($paths)));
                } else {
                    $this->logger->info("No thumbnails needed to be moved");
                }

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'generate-cms-thumbnails';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(ImageThumbnailHelper::class)) {
                $this->logger->error("ImageThumbnailHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->notice("#############################################");
                $this->logger->notice("Generating new CMS UI thumbnails ({$subtask})");
                $this->logger->notice("#############################################");

                $count = ImageThumbnailHelper::singleton()
                    ->setLogger($this->logger)
                    ->run();

                if ($count > 0) {
                    $this->logger->info("Created {$count} CMS UI thumbnails");
                } else {
                    $this->logger->info("No CMS UI thumbnails needed to be created");
                }

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'fix-folder-permissions';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(FixFolderPermissionsHelper::class)) {
                $this->logger->error("FixFolderPermissionsHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->notice("####################################################");
                $this->logger->notice("Fixing secure-assets folder permissions ({$subtask})");
                $this->logger->notice("####################################################");
                $this->logger->debug('Only required if the 3.x project included silverstripe/secure-assets');

                $count = FixFolderPermissionsHelper::singleton()
                    ->setLogger($this->logger)
                    ->run();

                if ($count > 0) {
                    $this->logger->info("Repaired {$count} folders with broken CanViewType settings");
                } else {
                    $this->logger->info("No folders required fixes");
                }

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'fix-secureassets';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(SecureAssetsMigrationHelper::class)) {
                $this->logger->error("SecureAssetsMigrationHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->notice("#####################################################");
                $this->logger->notice("Fixing secure-assets folder restrictions ({$subtask})");
                $this->logger->notice("#####################################################");
                $this->logger->debug('Only required if the 3.x project included silverstripe/secure-assets');

                $paths = SecureAssetsMigrationHelper::singleton()
                    ->setLogger($this->logger)
                    ->run($this->getStore());

                if (count($paths) > 0) {
                    $this->logger->info(sprintf("Repaired %d folders broken folder restrictions", count($paths)));
                } else {
                    $this->logger->info("No folders required fixes");
                }

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $this->logger->info("Done!");

        $this->extend('postFileMigration');
    }

    public function getDescription()
    {
        return <<<TXT
Imports all files referenced by File dataobjects into the new Asset Persistence Layer introduced in 4.0.
Moves existing thumbnails, and generates new thumbnail sizes for the CMS UI. Fixes file permissions.
If the task fails or times out, run it again and if possible the tasks will start where they left off.
You need to flush your cache after running this task via CLI.
See https://docs.silverstripe.org/en/4/developer_guides/files/file_migration/.
TXT;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return AssetStore
     */
    protected function getStore()
    {
        return singleton(AssetStore::class);
    }

    /**
     * @param array $args
     * @throws \InvalidArgumentException
     */
    protected function validateArgs($args)
    {
        if (!empty($args['only'])) {
            if (array_diff(explode(',', $args['only']), $this->defaultSubtasks)) {
                throw new \InvalidArgumentException('Invalid subtasks detected: ' . $args['only']);
            }
        }
    }

    /**
     * TODO Refactor this whole mess into Symfony Console on a TaskRunner level,
     * with a thin wrapper to show coloured console output via a browser:
     * https://github.com/silverstripe/silverstripe-framework/issues/5542
     * @throws \Exception
     */
    protected function addLogHandlers()
    {
        // Using a global service here so other systems can control and redirect log output,
        // for example when this task is run as part of a queuedjob
        $logger = Injector::inst()->get(LoggerInterface::class)->withName('log');

        $formatter = new ColoredLineFormatter();
        $formatter->ignoreEmptyContextAndExtra();

        $errorHandler = new StreamHandler('php://stderr', Logger::ERROR);
        $errorHandler->setFormatter($formatter);

        $standardHandler = new StreamHandler('php://stdout');
        $standardHandler->setFormatter($formatter);

        // Avoid double logging of errors
        $standardFilterHandler = new FilterHandler(
            $standardHandler,
            Logger::DEBUG,
            Logger::WARNING
        );

        $logger->pushHandler($standardFilterHandler);
        $logger->pushHandler($errorHandler);

        $this->logger = $logger;
    }
}
