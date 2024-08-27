<?php

namespace SilverStripe\Cli\CommandLoader;

use SilverStripe\Cli\Command\PolyCommandCliWrapper;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\PolyExecution\PolyCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Get commands for PolyCommand classes
 */
abstract class PolyCommandLoader implements CommandLoaderInterface
{
    use Injectable;

    private array $commands = [];
    private array $commandAliases = [];

    public function get(string $name): Command
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }
        $info = $this->commands[$name] ?? $this->commandAliases[$name];
        /** @var PolyCommand $commandClass */
        $commandClass = $info['class'];
        $polyCommand = $commandClass::create();
        return PolyCommandCliWrapper::create($polyCommand, $info['alias']);
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

    /**
     * Get the array of PolyCommand objects this loader is responsible for.
     * Do not filter canRunInCli().
     *
     * @return array<string, PolyCommand> Associative array of commands.
     * The key is an alias, or if no alias exists, the name of the command.
     */
    abstract protected function getCommands(): array;

    /**
     * Limit to only the commands that are allowed to be run in CLI.
     */
    private function initCommands(): void
    {
        if (empty($this->commands)) {
            $commands = $this->getCommands();
            /** @var PolyCommand $class */
            foreach ($commands as $alias => $class) {
                if (!$class::canRunInCli()) {
                    continue;
                }
                $commandName = $class::getName();
                $hasAlias = $alias !== $commandName;
                $this->commands[$commandName] = [
                    'class' => $class,
                    'alias' => $hasAlias ? $alias : null,
                ];
                if ($hasAlias) {
                    $this->commandAliases[$alias] = [
                        'class' => $class,
                        'alias' => $alias,
                    ];
                }
            }
        }
    }
}
