<?php

namespace SilverStripe\Cli\Command;

use SilverStripe\Cli\CommandLoader\DevTaskLoader;
use SilverStripe\Cli\Sake;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that runs `sake list tasks` under the hood to list all of the available tasks.
 * Useful when you have too many tasks to show in the main commands list.
 *
 * Note the description is blue so it stands out, to avoid developers missing it if they add a new
 * task and suddenly they don't see the tasks in their main commands list anymore.
 */
#[AsCommand(name: 'tasks', description: '<fg=blue>See a list of build tasks to run</>')]
class TasksCommand extends Command
{
    private Command $listCommand;

    public function __construct()
    {
        parent::__construct();
        $this->listCommand = new ListCommand();
        $this->setDefinition($this->listCommand->getDefinition());
    }

    public function setApplication(?Application $application): void
    {
        $this->listCommand->setApplication($application);
        parent::setApplication($application);
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->getCompletionType() === CompletionInput::TYPE_ARGUMENT_VALUE) {
            // Make this command transparent to completion, so we can `sake tasks<tab>` and see all tasks
            if ($input->getCompletionValue() === $this->getName()) {
                $taskLoader = DevTaskLoader::create();
                $suggestions->suggestValues($taskLoader->getNames());
            }
            // Don't allow completion for the namespace argument, because we will override their value anyway
            return;
        }
        // Still allow completion for options e.g. --format
        parent::complete($input, $suggestions);
    }

    public function isHidden(): bool
    {
        return !$this->getApplication()->shouldHideTasks();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Explicitly don't allow any namespace other than tasks
        $input->setArgument('namespace', 'tasks');
        // We have to call execute() here instead of run(), because run() would re-bind
        // the input which would throw away the namespace argument.
        $this->getApplication()?->setIgnoreTaskLimit(true);
        $exitCode = $this->listCommand->execute($input, $output);
        $this->getApplication()?->setIgnoreTaskLimit(false);
        return $exitCode;
    }

    protected function configure()
    {
        $sakeClass = Sake::class;
        $this->setHelp(<<<HELP
        If you want to display the tasks in the main commands list, update the <info>$sakeClass.max_tasks_to_display</info> configuration.
        <comment>
        $sakeClass:
          max_tasks_to_display: 50
        </>
        Set the value to 0 to always display tasks in the main command list regardless of how many there are.
        HELP);
    }
}
