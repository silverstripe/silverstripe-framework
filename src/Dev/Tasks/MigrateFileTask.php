<?php

namespace SilverStripe\Dev\Tasks;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper;
use SilverStripe\Assets\Dev\Tasks\LegacyThumbnailMigrationHelper;
use SilverStripe\Assets\Dev\Tasks\FileMigrationHelper;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Assets\Storage\FileHashingService;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Logging\PreformattedEchoHandler;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Assets\Dev\Tasks\SecureAssetsMigrationHelper;

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

        $this->extend('preFileMigration');

        $subtasks = !empty($args['only']) ? explode(',', $args['only']) : $this->defaultSubtasks;

        $subtask = 'move-files';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(FileMigrationHelper::class)) {
                $this->logger->error("No file migration helper detected");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);
                $this->logger->info("### Migrating filesystem and database records ({$subtask})");
                $this->logger->info('If the task fails or times out, run it again and it will start where it left off.');

                $migrated = FileMigrationHelper::singleton()->run();
                if ($migrated) {
                    $this->logger->info("{$migrated} File DataObjects upgraded");
                } else {
                    $this->logger->info("No File DataObjects need upgrading");
                }
                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'move-thumbnails';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(LegacyThumbnailMigrationHelper::class)) {
                $this->logger->error("LegacyThumbnailMigrationHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);
                $this->logger->info("### Migrating existing thumbnails ({$subtask})");

                $moved = LegacyThumbnailMigrationHelper::singleton()
                    ->setLogger($this->logger)
                    ->run($this->getStore());

                if ($moved) {
                    $this->logger->info(sprintf("%d thumbnails moved", count($moved)));
                } else {
                    $this->logger->info("No thumbnails moved");
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
                $this->logger->info("### Generating new CMS UI thumbnails ({$subtask})");
                ImageThumbnailHelper::singleton()->run();
                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $subtask = 'fix-folder-permissions';
        if (in_array($subtask, $subtasks)) {
            if (!class_exists(FixFolderPermissionsHelper::class)) {
                $this->logger->error("FixFolderPermissionsHelper not found");
            } else {
                $this->extend('preFileMigrationSubtask', $subtask);

                $this->logger->info("### Fixing folder permissions ({$subtask})");
                $updated = FixFolderPermissionsHelper::singleton()->run();

                if ($updated > 0) {
                    $this->logger->info("Repaired {$updated} folders with broken CanViewType settings");
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

                $this->logger->info("### Fixing secure-assets ({$subtask})");
                $moved = SecureAssetsMigrationHelper::singleton()
                    ->setLogger($this->logger)
                    ->run($this->getStore());

                $this->extend('postFileMigrationSubtask', $subtask);
            }
        }

        $this->extend('postFileMigration');

        $this->logger->info("Done!");
    }

    public function getDescription()
    {
        return <<<TXT
Imports all files referenced by File dataobjects into the new Asset Persistence Layer introduced in 4.0.
Moves existing thumbnails, and generates new thumbnail sizes for the CMS UI.
Fixes file permissions.
If the task fails or times out, run it again and it will start where it left off.
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
