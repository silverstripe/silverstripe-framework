<?php

namespace SilverStripe\Forms\HTMLEditor;

/**
 * An HTMLEditorConfig that allows using a textarea DOM element to directly edit HTML.
 * No client-side sanitisation of content is performed - it's all done server-side.
 */
class TextAreaConfig extends HTMLEditorConfig
{
    protected static string $configType = 'textarea';

    protected static string $schemaComponent = 'TextField';

    private ?HTMLEditorRuleSet $ruleset = null;

    private array $options = [];

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
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function getAttributes(): array
    {
        return array_merge(
            parent::getAttributes(),
            [
                'rows' => $this->getRows(),
            ]
        );
    }

    public function init(): void
    {
        // no-op
    }

    public function getElementRuleSet(): HTMLEditorRuleSet
    {
        return $this->ruleset ?? new HTMLEditorRuleSet();
    }

    public function setElementRuleSet(HTMLEditorRuleSet $ruleset): static
    {
        $this->ruleset = $ruleset;
        return $this;
    }
}
