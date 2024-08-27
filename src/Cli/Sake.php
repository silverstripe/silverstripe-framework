<?php

namespace SilverStripe\Cli;

use SilverStripe\Cli\Command\NavigateCommand;
use SilverStripe\Cli\Command\TasksCommand;
use SilverStripe\Cli\CommandLoader\ArrayCommandLoader;
use SilverStripe\Cli\CommandLoader\DevCommandLoader;
use SilverStripe\Cli\CommandLoader\DevTaskLoader;
use SilverStripe\Cli\CommandLoader\InjectorCommandLoader;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\Deprecation;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * CLI application for running commands against a Silverstripe CMS project
 * Boots up a full kernel, using the same configuration and database the web server uses.
 */
class Sake extends Application
{
    use Configurable;

    /**
     * Commands that can be run. These commands will be instantiated via the Injector.
     * Does not include commands in the dev/ namespace (see command_loaders).
     *
     * @var array<Command>
     */
    private static array $commands = [
        'navigate' => NavigateCommand::class,
    ];

    /**
     * Command loaders for dynamically adding commands to sake.
     * These loaders will be instantiated via the Injector.
     *
     * @var array<CommandLoaderInterface>
     */
    private static array $command_loaders = [
        'dev-commands' => DevCommandLoader::class,
        'dev-tasks' => DevTaskLoader::class,
        'injected' => InjectorCommandLoader::class,
    ];

    /**
     * Maximum number of tasks to display in the main command list.
     *
     * If there are more tasks than this, they will be hidden from the main command list - running `sake tasks` will show them.
     * Set to 0 to always show tasks in the main list.
     */
    private static int $max_tasks_to_display = 20;

    /**
     * Set this to true to hide the "completion" command.
     * Useful if you never intend to set up shell completion, or if you've already done so.
     */
    private static bool $hide_completion_command = false;

    private ?Kernel $kernel;

    private bool $ignoreTaskLimit = false;

    public function __construct(?Kernel $kernel = null)
    {
        $this->kernel = $kernel;
        parent::__construct('Silverstripe Sake');
    }

    public function getVersion(): string
    {
        return VersionProvider::singleton()->getVersion();
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $input = $input ?? new LegacyParamArgvInput();
        $flush = $input->hasParameterOption('--flush', true) || $input->getFirstArgument() === 'flush';
        $bootDatabase = !$input->hasParameterOption('--no-database', true);

        $managingKernel = !$this->kernel;
        if ($managingKernel) {
            // Instantiate the kernel if we weren't given a pre-loaded one
            $this->kernel = new CoreKernel(BASE_PATH);
        }
        try {
            // Boot if not already booted
            if (!$this->kernel->getBooted()) {
                if ($this->kernel instanceof CoreKernel) {
                    $this->kernel->setBootDatabase($bootDatabase);
                }
                $this->kernel->boot($flush);
            }
            // Allow developers to hook into symfony/console events
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = Injector::inst()->get(EventDispatcherInterface::class . '.sake');
            $this->setDispatcher($dispatcher);
            // Add commands and finally execute
            $this->addCommandLoadersFromConfig();
            return parent::run($input, $output);
        } finally {
            // If we instantiated the kernel, we're also responsible for shutting it down.
            if ($managingKernel) {
                $this->kernel->shutdown();
            }
        }
    }

    public function all(?string $namespace = null): array
    {
        $commands = parent::all($namespace);
        // If number of tasks is greater than the limit, hide them from the main comands list.
        $maxTasks = Sake::config()->get('max_tasks_to_display');
        if (!$this->ignoreTaskLimit && $maxTasks > 0 && $namespace === null) {
            $tasks = [];
            // Find all commands in the tasks: namespace
            foreach (array_keys($commands) as $name) {
                if (str_starts_with($name, 'tasks:') || str_starts_with($name, 'dev/tasks/')) {
                    $tasks[] = $name;
                }
            }
            if (count($tasks) > $maxTasks) {
                // Hide the commands
                foreach ($tasks as $name) {
                    unset($commands[$name]);
                }
            }
        }
        return $commands;
    }

    /**
     * Check whether tasks should currently be hidden from the main command list
     */
    public function shouldHideTasks(): bool
    {
        $maxLimit = Sake::config()->get('max_tasks_to_display');
        return $maxLimit > 0 && count($this->all('tasks')) > $maxLimit;
    }

    /**
     * Set whether the task limit should be ignored.
     * Used by the tasks command and completion to allow listing tasks when there's too many of them
     * to list in the main command list.
     */
    public function setIgnoreTaskLimit(bool $ignore): void
    {
        $this->ignoreTaskLimit = $ignore;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        // Make sure tasks can always be shown in completion even if there's too many of them to list
        // in the main command list.
        $this->setIgnoreTaskLimit(true);

        // Remove legacy dev/* aliases from completion suggestions, but only
        // if the user isn't explicitly looking for them (i.e. hasn't typed anything yet)
        if (CompletionInput::TYPE_ARGUMENT_VALUE === $input->getCompletionType()
            && $input->getCompletionName() === 'command'
            && $input->getCompletionValue() === ''
        ) {
            foreach ($this->all() as $name => $command) {
                // skip hidden commands
                // skip aliased commands as they get added below
                if ($command->isHidden() || $command->getName() !== $name) {
                    continue;
                }
                $suggestions->suggestValue(new Suggestion($command->getName(), $command->getDescription()));
                foreach ($command->getAliases() as $name) {
                    // Skip legacy dev aliases
                    if (str_starts_with($name, 'dev/')) {
                        continue;
                    }
                    $suggestions->suggestValue(new Suggestion($name, $command->getDescription()));
                }
            }

            return;
        } else {
            // For everything else, use the superclass
            parent::complete($input, $suggestions);
        }
        $this->setIgnoreTaskLimit(false);
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $name = $command->getName() ?? '';
        $nameUsedAs = $input->getFirstArgument() ?? '';
        if (str_starts_with($nameUsedAs, 'dev/')) {
            Deprecation::notice(
                '6.0.0',
                "Using the command with the name '$nameUsedAs' is deprecated. Use '$name' instead",
                Deprecation::SCOPE_GLOBAL
            );
        }
        return parent::doRunCommand($command, $input, $output);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOptions([
            new InputOption('no-database', null, InputOption::VALUE_NONE, 'Run the command without connecting to the database'),
            new InputOption('flush', 'f', InputOption::VALUE_NONE, 'Flush the cache before running the command'),
        ]);
        return $definition;
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();

        // Hide commands that are just cluttering up the list
        $toHide = [
            // List is the default command, and you have to have used it to see it anyway.
            ListCommand::class,
            // The --help flag is more common and is already displayed.
            HelpCommand::class,
        ];
        // Completion is just clutter if you've already used it or aren't going to.
        if (Sake::config()->get('hide_completion_command')) {
            $toHide[] = DumpCompletionCommand::class;
        }
        foreach ($commands as $command) {
            if (in_array(get_class($command), $toHide)) {
                $command->setHidden(true);
            }
        }

        $commands[] = $this->createFlushCommand();
        $commands[] = new TasksCommand();

        return $commands;
    }

    private function addCommandLoadersFromConfig(): void
    {
        $loaderClasses = Sake::config()->get('command_loaders');
        $loaders = [];
        foreach ($loaderClasses as $class) {
            if ($class === null) {
                // Allow unsetting loaders via yaml
                continue;
            }
            $loaders[] = Injector::inst()->create($class);
        }
        $this->setCommandLoader(ArrayCommandLoader::create($loaders));
    }

    /**
     * Creates a dummy "flush" command for when you just want to flush without running another command.
     */
    private function createFlushCommand(): Command
    {
        $command = new Command('flush');
        $command->setDescription('Flush the cache (or use the <info>--flush</info> flag with any command)');
        $command->setCode(function (InputInterface $input, OutputInterface $ouput) {
            // Actual flushing happens in `run()` when booting the kernel, so there's nothing to do here.
            $ouput->writeln('Cache flushed.');
            return Command::SUCCESS;
        });
        return $command;
    }
}
