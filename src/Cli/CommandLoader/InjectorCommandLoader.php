<?php

namespace SilverStripe\Cli\CommandLoader;

use LogicException;
use SilverStripe\Cli\Command\PolyCommandCliWrapper;
use SilverStripe\Cli\Sake;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Command loader that loads commands from the injector if they were registered with Sake.
 */
class InjectorCommandLoader implements CommandLoaderInterface
{
    private array $commands = [];
    private array $commandAliases = [];

    public function get(string $name): Command
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }
        return $this->commands[$name] ?? $this->commandAliases[$name];
    }

    public function has(string $name): bool
    {
        $this->initCommands();
        return array_key_exists($name, $this->commands) || array_key_exists($name, $this->commandAliases);
    }

    public function getNames(): array
    {
        $this->initCommands();
        return array_keys($this->commands);
    }

    private function initCommands(): void
    {
        if (empty($this->commands)) {
            $commandClasses = Sake::config()->get('commands');
            foreach ($commandClasses as $class) {
                if ($class === null) {
                    // Allow unsetting commands via yaml
                    continue;
                }
                $command = Injector::inst()->create($class);
                // Wrap poly commands (if they're allowed to be run)
                if ($command instanceof PolyCommand) {
                    if (!$command::canRunInCli()) {
                        continue;
                    }
                    $command = PolyCommandCliWrapper::create($command);
                }
                /** @var Command $command */
                if (!$command->getName()) {
                    throw new LogicException(sprintf(
                        'The command defined in "%s" cannot have an empty name.',
                        get_debug_type($command)
                    ));
                }
                $this->commands[$command->getName()] = $command;
                foreach ($command->getAliases() as $alias) {
                    $this->commandAliases[$alias] = $command;
                }
            }
        }
    }
}
