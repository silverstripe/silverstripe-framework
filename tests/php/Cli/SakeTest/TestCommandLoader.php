<?php

namespace SilverStripe\Cli\Tests\SakeTest;

use SilverStripe\Dev\TestOnly;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class TestCommandLoader implements CommandLoaderInterface, TestOnly
{
    private string $commandName = 'loader:test-command';

    public function get(string $name): Command
    {
        if ($name !== $this->commandName) {
            throw new CommandNotFoundException("Wrong command fetched. Expected '$this->commandName' - got '$name'");
        }
        return new TestLoaderCommand();
    }

    public function has(string $name): bool
    {
        return $name === $this->commandName;
    }

    public function getNames(): array
    {
        return [$this->commandName];
    }
}
