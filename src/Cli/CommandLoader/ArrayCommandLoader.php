<?php

namespace SilverStripe\Cli\CommandLoader;

use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Command loader that holds more command loaders
 */
class ArrayCommandLoader implements CommandLoaderInterface
{
    use Injectable;

    /**
     * @var array<CommandLoaderInterface>
     */
    private array $loaders = [];

    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    public function get(string $name): Command
    {
        foreach ($this->loaders as $loader) {
            if ($loader->has($name)) {
                return $loader->get($name);
            }
        }
        throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
    }

    public function has(string $name): bool
    {
        foreach ($this->loaders as $loader) {
            if ($loader->has($name)) {
                return true;
            }
        }
        return false;
    }

    public function getNames(): array
    {
        $names = [];
        foreach ($this->loaders as $loader) {
            $names = array_merge($names, $loader->getNames());
        }
        return array_unique($names);
    }
}
