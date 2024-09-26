<?php

namespace SilverStripe\Dev;

use LogicException;
use SilverStripe\Core\Extensible;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * A task that can be run either from the CLI or via an HTTP request.
 * This is often used for post-deployment tasks, e.g. migrating data to fit a new schema.
 */
abstract class BuildTask extends PolyCommand
{
    use Extensible;

    /**
     * Shown in the overview on the {@link TaskRunner}
     * HTML or CLI interface. Should be short and concise.
     * Do not use HTML markup.
     */
    protected string $title;

    /**
     * Whether the task is allowed to be run or not.
     * This property overrides `can_run_in_cli` and `can_run_in_browser` if set to false.
     */
    private static bool $is_enabled = true;

    /**
     * Describe the implications the task has, and the changes it makes.
     * Do not use HTML markup.
     */
    protected static string $description = 'No description available';

    private static array $permissions_for_browser_execution = [
        'ADMIN',
        'ALL_DEV_ADMIN' => true,
        'BUILDTASK_CAN_RUN' => true,
    ];

    public function __construct()
    {
    }

    /**
     * The code for running this task.
     *
     * Output should be agnostic - do not include explicit HTML in the output unless there is no API
     * on `PolyOutput` for what you want to do (in which case use the writeForHtml() method).
     *
     * Use symfony/console ANSI formatting to style the output.
     * See https://symfony.com/doc/current/console/coloring.html
     *
     * @return int 0 if everything went fine, or an exit code
     */
    abstract protected function execute(InputInterface $input, PolyOutput $output): int;

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $output->writeForAnsi("<options=bold>Running task '{$this->getTitle()}'</>", true);
        $output->writeForHtml("<h1>Running task '{$this->getTitle()}'</h1>", false);

        $before = DBDatetime::now();
        $exitCode = $this->execute($input, $output);
        $after = DBDatetime::now();

        $message = "Task '{$this->getTitle()}' ";
        if ($exitCode === Command::SUCCESS) {
            $message .= 'completed successfully';
        } else {
            $message .= 'failed';
        }
        $timeTaken = DBDatetime::getTimeBetween($before, $after);
        $message .= " in $timeTaken";
        $output->writeln(['', "<options=bold>{$message}</>"]);
        return $exitCode;
    }

    public function isEnabled(): bool
    {
        return $this->config()->get('is_enabled');
    }

    public function getTitle(): string
    {
        return $this->title ?? static::class;
    }

    public static function getName(): string
    {
        return 'tasks:' . static::getNameWithoutNamespace();
    }

    public static function getNameWithoutNamespace(): string
    {
        $name = parent::getName() ?: str_replace('\\', '-', static::class);
        // Don't allow `:` or `/` because it would affect routing and CLI namespacing
        if (str_contains($name, ':') || str_contains($name, '/')) {
            throw new LogicException('commandName must not contain `:` or `/`. Got ' . $name);
        }
        return $name;
    }
}
