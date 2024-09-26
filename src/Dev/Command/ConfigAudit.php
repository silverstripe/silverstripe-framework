<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Command to audit the configuration.
 * Can be run either via an HTTP request or the CLI.
 */
class ConfigAudit extends DevCommand
{
    protected static string $commandName = 'config:audit';

    protected static string $description = 'Find configuration properties that are not defined (or inherited) by their respective classes';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_CONFIG',
    ];

    public function getTitle(): string
    {
        return 'Configuration';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $body = '';
        $missing = [];

        foreach ($this->arrayKeysRecursive(Config::inst()->getAll(), 2) as $className => $props) {
            $props = array_keys($props ?? []);

            if (!count($props ?? [])) {
                // We can skip this entry
                continue;
            }

            if ($className == strtolower(Injector::class)) {
                // We don't want to check the injector config
                continue;
            }

            foreach ($props as $prop) {
                $defined = false;
                // Check ancestry (private properties don't inherit natively)
                foreach (ClassInfo::ancestry($className) as $cn) {
                    if (property_exists($cn, $prop ?? '')) {
                        $defined = true;
                        break;
                    }
                }

                if ($defined) {
                    // No need to record this property
                    continue;
                }

                $missing[] = sprintf("%s::$%s\n", $className, $prop);
            }
        }

        $body = count($missing ?? [])
            ? implode("\n", $missing)
            : "All configured properties are defined\n";

        $output->writeForHtml('<pre>');
        $output->write($body);
        $output->writeForHtml('</pre>');

        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Missing configuration property definitions';
    }

    /**
     * Returns all the keys of a multi-dimensional array while maintining any nested structure.
     * Does not include keys where the values are not arrays, so not suitable as a generic method.
     */
    private function arrayKeysRecursive(
        array $array,
        int $maxdepth = 20,
        int $depth = 0,
        array $arrayKeys = []
    ): array {
        if ($depth < $maxdepth) {
            $depth++;
            $keys = array_keys($array ?? []);

            foreach ($keys as $key) {
                if (!is_array($array[$key])) {
                    continue;
                }

                $arrayKeys[$key] = $this->arrayKeysRecursive($array[$key], $maxdepth, $depth);
            }
        }

        return $arrayKeys;
    }
}
