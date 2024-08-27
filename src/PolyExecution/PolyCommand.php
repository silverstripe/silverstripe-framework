<?php

namespace SilverStripe\PolyExecution;

use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\PolyExecution\HtmlOutputFormatter;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Abstract class for commands which can be run either via an HTTP request or the CLI.
 */
abstract class PolyCommand
{
    use Configurable;
    use Injectable;

    /**
     * Defines whether this command can be run in the CLI via sake.
     * Overridden if DevelopmentAdmin sets allow_all_cli to true.
     *
     * Note that in dev mode the command can always be run.
     */
    private static bool $can_run_in_cli = true;

    /**
     * Defines whether this command can be run in the browser via a web request.
     * If true, user must have the requisite permissions.
     *
     * Note that in dev mode the command can always be run.
     */
    private static bool $can_run_in_browser = true;

    /**
     * Permissions required for users to execute this command via the browser.
     * Users must have at least one of these permissions to run the command in an HTTP request.
     * If `can_run_in_browser` is false, these permissions are ignored.
     * Must be defined in the subclass.
     *
     * If permissions are set as keys, the value must be boolean, indicating whether to check
     * that permission or not. This is useful for allowing developers to turn off permission checks
     * that aren't valid for their project or for their subclass.
     */
    private static array $permissions_for_browser_execution = [];

    /**
     * Name of the command. Also used as the end of the URL segment for browser execution.
     * Must be defined in the subclass.
     */
    protected static string $commandName = '';

    /**
     * Description of what the command does. Can use symfony console styling.
     * See https://symfony.com/doc/current/console/coloring.html.
     * Must be defined in the subclass.
     */
    protected static string $description = '';

    /**
     * Get the title for this command.
     */
    abstract public function getTitle(): string;

    /**
     * Execute this command.
     *
     * Output should be agnostic - do not include explicit HTML in the output unless there is no API
     * on `PolyOutput` for what you want to do (in which case use the writeForHtml() method).
     *
     * Use symfony/console ANSI formatting to style the output.
     * See https://symfony.com/doc/current/console/coloring.html
     *
     * @return int 0 if everything went fine, or an exit code
     */
    abstract public function run(InputInterface $input, PolyOutput $output): int;

    /**
     * Get the name of this command.
     */
    public static function getName(): string
    {
        return static::$commandName;
    }

    /**
     * Get the description of this command. Includes unparsed symfony/console styling.
     */
    public static function getDescription(): string
    {
        return _t(static::class . '.description', static::$description);
    }

    /**
     * Return additional help context to avoid an overly long description.
     */
    public static function getHelp(): string
    {
        return '';
    }

    /**
     * Get input options that can be passed into the command.
     *
     * In CLI execution these will be passed as flags.
     * In HTTP execution these will be passed in the query string.
     *
     * @return array<InputOption>
     */
    public function getOptions(): array
    {
        return [];
    }

    public function getOptionsForTemplate(): array
    {
        $formatter = HtmlOutputFormatter::create();
        $forTemplate = [];
        foreach ($this->getOptions() as $option) {
            $default = $option->getDefault();
            if (is_bool($default)) {
                // Use 1/0 for boolean, since that's what you'd pass in the query string
                $default = $default ? '1' : '0';
            }
            if (is_array($default)) {
                $default = implode(',', $default);
            }
            $forTemplate[] = [
                'Name' => $option->getName(),
                'Description' => DBField::create_field('HTMLText', $formatter->format($option->getDescription())),
                'Default' => $default,
            ];
        }
        return $forTemplate;
    }

    /**
     * Check whether this command can be run in CLI via sake
     */
    public static function canRunInCli(): bool
    {
        static::checkPrerequisites();
        return Director::isDev()
            || static::config()->get('can_run_in_cli')
            || DevelopmentAdmin::config()->get('allow_all_cli');
    }

    /**
     * Check whether this command can be run in the browser via a web request
     */
    public static function canRunInBrowser(): bool
    {
        static::checkPrerequisites();
        // Can always run in browser in dev mode
        if (Director::isDev()) {
            return true;
        }
        if (!static::config()->get('can_run_in_browser')) {
            return false;
        }
        // Check permissions if there are any
        $permissions = static::config()->get('permissions_for_browser_execution');
        if (!empty($permissions)) {
            $usePermissions = [];
            // Only use permissions that aren't set to false
            // Allow permissions to also be simply set as values, for simplicity
            foreach ($permissions as $key => $value) {
                if (is_string($value)) {
                    $usePermissions[] = $value;
                } elseif ($value) {
                    $usePermissions[] = $key;
                }
            }
            return Permission::check($usePermissions);
        }
        return true;
    }

    private static function checkPrerequisites(): void
    {
        $mandatoryMethods = [
            'getName' => 'commandName',
            'getDescription' => 'description',
        ];
        foreach ($mandatoryMethods as $getter => $property) {
            if (!static::$getter()) {
                throw new RuntimeException($property . ' property needs to be set.');
            }
        }
    }
}
