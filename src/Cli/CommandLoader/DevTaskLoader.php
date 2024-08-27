<?php

namespace SilverStripe\Cli\CommandLoader;

use SilverStripe\Dev\TaskRunner;

/**
 * Get commands for the dev:tasks namespace
 */
class DevTaskLoader extends PolyCommandLoader
{
    protected function getCommands(): array
    {
        $commands = [];
        foreach (TaskRunner::singleton()->getTaskList() as $name => $class) {
            $singleton = $class::singleton();
            // Don't add disabled tasks.
            // No need to check canRunInCli() - the superclass will take care of that.
            if ($singleton->isEnabled()) {
                $commands['dev/' . str_replace('tasks:', 'tasks/', $name)] = $class;
            }
        };
        return $commands;
    }
}
