<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Core\Injector\Injectable;

class ModuleSorter implements Sorter
{
    use Injectable;

    const PLACEHOLDER_OTHER_MODULES = 'other_modules';

    const PLACEHOLDER_PROJECT = '$project';

    protected $modules;

    protected $priorities;

    protected $project;

    protected $moduleNames;

    /**
     * ModuleSorter constructor.
     * @param Module[] $modules
     * @param array $priorities
     */
    public function __construct(array $modules = [], array $priorities = [])
    {
        $this->setModules($modules);
        $this->priorities = $priorities;
    }

    public function getSortedList()
    {
        if ($this->project) {
            $this->includeProject();
        }

        // Find all modules that don't have their order specified by the config system
        $unspecified = array_diff($this->moduleNames, $this->priorities);

        if (!empty($unspecified)) {
            $this->includeOtherModules($unspecified);
        }

        $sortedModulePaths = [];
        foreach ($this->priorities as $module) {
            if (isset($this->modules[$module])) {
                $sortedModulePaths[$module] = $this->modules[$module]->getPath();
            }
        }
        $sortedModulePaths = array_reverse($sortedModulePaths, true);

        return $sortedModulePaths;
    }

    public function setPriorities(array $priorities)
    {
        $this->priorities = $priorities;

        return $this;
    }

    public function setModules(array $modules)
    {
        $this->modules = $modules;
        $this->moduleNames = array_keys($modules);

        return $this;
    }

    public function setProject($project)
    {
        $this->project = $project;

        return $this;
    }

    protected function includeProject()
    {
        // Remove the "project" module from the list
        $this->moduleNames = array_filter($this->moduleNames, function ($name) {
            return $name !== $this->project;
        });

        // Replace $project with project value
        $this->priorities = array_map(function ($name) {
            return $name === static::PLACEHOLDER_PROJECT ? $this->project : $name;
        }, $this->priorities);

        // Put the project at end (highest priority)
        if (!in_array($this->project, $this->priorities)) {
            $this->priorities[] = $this->project;
        }
    }

    // If the placeholder "other_modules" exists in the order array, replace it by the unspecified modules
    protected function includeOtherModules(array $list)
    {
        $otherModulesIndex = array_search(static::PLACEHOLDER_OTHER_MODULES, $this->priorities);
        if ($otherModulesIndex !== false) {
            array_splice($this->priorities, $otherModulesIndex, 1, $list);
        } else {
            // Otherwise just jam them on the front
            array_splice($this->priorities, 0, 0, $list);
        }
    }

}