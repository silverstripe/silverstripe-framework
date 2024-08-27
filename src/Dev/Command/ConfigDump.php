<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\PermissionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Command to dump the configuration.
 * Can be run either via an HTTP request or the CLI.
 */
class ConfigDump extends DevCommand implements PermissionProvider
{
    protected static string $commandName = 'config:dump';

    protected static string $description = 'View the current config, useful for debugging';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_CONFIG',
    ];

    public function getTitle(): string
    {
        return 'Configuration';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeForHtml('<pre>');

        $output->write(Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));

        $output->writeForHtml('</pre>');
        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Config manifest';
    }

    public function providePermissions(): array
    {
        return [
            'CAN_DEV_CONFIG' => [
                'name' => _t(__CLASS__ . '.CAN_DEV_CONFIG_DESCRIPTION', 'Can view /dev/config'),
                'help' => _t(__CLASS__ . '.CAN_DEV_CONFIG_HELP', 'Can view all application configuration (/dev/config).'),
                'category' => DevelopmentAdmin::permissionsCategory(),
                'sort' => 100
            ],
        ];
    }
}
