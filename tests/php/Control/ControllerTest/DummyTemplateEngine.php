<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateEngine;
use SilverStripe\View\ViewLayerData;

/**
 * A dummy template renderer that doesn't actually render any templates.
 */
class DummyTemplateEngine implements TemplateEngine, TestOnly
{
    private string $output = 'This is my controller';

    public function __construct(string|array $templateCandidates = [])
    {
        // no-op
    }

    public function setTemplate(string|array $templateCandidates): static
    {
        return $this;
    }

    public function hasTemplate(string|array $templateCandidates): bool
    {
        return true;
    }

    public function renderString(string $template, ViewLayerData $model, array $overlay = [], bool $cache = true): string
    {
        return $this->output;
    }

    public function render(ViewLayerData $model, array $overlay = []): string
    {
        return $this->output;
    }
}
