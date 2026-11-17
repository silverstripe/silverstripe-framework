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

    /**
     * Configuration properties that are intentionally not declared on a class, and should
     * not be reported. Keys are class names, values are lists of property names. An entry
     * also applies to subclasses of the class.
     *
     * Use this for configuration that a module reads from classes it doesn't own, e.g.:
     *
     * ```yml
     * SilverStripe\Dev\Command\ConfigAudit:
     *   ignored_properties:
     *     SilverStripe\ORM\DataObject:
     *       - my_module_setting
     * ```
     */
    private static array $ignored_properties = [];

    /**
     * Configuration properties that are never reported for any class, because they are
     * read by core for every class rather than declared on it.
     */
    private const ALWAYS_IGNORED_PROPERTIES = [
        // Read by Injector::inject() for any class to set its dependencies
        'dependencies',
    ];

    public function getTitle(): string
    {
        return 'Configuration';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $body = '';
        $missing = [];
        $ignored = $this->getIgnoredProperties();

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

            if (str_starts_with($className, '__')) {
                // Keys starting with a double underscore are internal to the config system
                // (e.g. "__deprecated", which holds the deprecation notices for private statics).
                // PHP reserves names starting with "__", so these can never be a class.
                continue;
            }

            foreach ($props as $prop) {
                if (in_array($prop, ConfigAudit::ALWAYS_IGNORED_PROPERTIES)) {
                    continue;
                }

                $defined = false;
                // Check ancestry (private properties don't inherit natively)
                foreach (ClassInfo::ancestry($className) as $cn) {
                    if (property_exists($cn, $prop ?? '')
                        || in_array($prop, $ignored[strtolower($cn)] ?? [])
                    ) {
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

        // Fail when anything is missing, so the audit can be used to fail a CI job
        return count($missing) ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get the ignored_properties config, with lowercase class names as keys
     * so they can be matched against ClassInfo::ancestry() and the config keys.
     */
    private function getIgnoredProperties(): array
    {
        $ignored = [];
        foreach (static::config()->get('ignored_properties') ?? [] as $className => $props) {
            $ignored[strtolower($className)] = array_merge($ignored[strtolower($className)] ?? [], (array) $props);
        }
        return $ignored;
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
