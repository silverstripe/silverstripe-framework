<?php

namespace SilverStripe\Dev\Upgrade;

use BadMethodCallException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides upgrade interface for bootstrapping.
 *
 * Note: This class is intended to be loaded from outside of a SilverStripe application
 * and should not reference any SilverStripe API.
 *
 * See https://github.com/silverstripe/silverstripe-upgrader/ for information
 * on running this task.
 */
class UpgradeBootstrap
{
    /**
     * List of files to install.
     * Set to true if the file should be re-installed if it doesn't exist.
     *
     * @var array
     */
    protected $files = [
        '.htaccess' => true,
        'index.php' => true,
        'install.php' => false,
    ];

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $basePath
     */
    public function __invoke(InputInterface $input, OutputInterface $output, $basePath)
    {
        $publicPath = file_exists("{$basePath}/public") ? "{$basePath}/public" : $basePath;

        // Fail if destination isn't writable
        $this->ensureWritable($publicPath);

        // Check source
        $source = $basePath . '/vendor/silverstripe/recipe-core/public';
        if (!is_dir($source)) {
            throw new BadMethodCallException("silverstripe/recipe-core is not installed.");
        }

        // Copy scaffolded files from recipe-core
        $output->writeln("Upgrading project bootstrapping files:");
        foreach ($this->files as $file => $canCreate) {
            $fileSource = $source . '/' . $file;
            $fileDest = $publicPath . '/' . $file;

            // Skip if we should only upgrade existing files
            if (!$canCreate && !file_exists($fileDest)) {
                continue;
            }
            $output->writeln("  - Upgrading <info>{$file}</info>");
            $this->copyFile(
                $fileSource,
                $fileDest
            );
        }
    }

    /**
     * Ensure path is writable
     *
     * @param string $path
     */
    protected function ensureWritable($path)
    {
        if (!is_writable($path)) {
            throw new BadMethodCallException("Path $path is not writable");
        }
    }

    /**
     * Copy file
     *
     * @param string $source
     * @param string $dest
     */
    protected function copyFile($source, $dest)
    {
        // Ensure existing file can be overwritten
        if (file_exists($dest)) {
            $this->ensureWritable($dest);
        }
        file_put_contents($dest, file_get_contents($source));
    }
}
