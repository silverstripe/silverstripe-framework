<?php

namespace SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest;

use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorRuleSet;

class NullEditorConfig extends HTMLEditorConfig
{
    private ?HTMLEditorRuleSet $ruleset = null;

    private array $options = [];

    public function getElementRuleSet(): HTMLEditorRuleSet
    {
        return $this->ruleset;
    }

    public function setElementRuleSet(HTMLEditorRuleSet $ruleset): static
    {
        $this->ruleset = $ruleset;
        return $this;
    }

    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    public function setOption(string $key, mixed $value): static
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function init(): void {}
}
