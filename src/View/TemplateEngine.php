<?php

namespace SilverStripe\View;

use SilverStripe\View\Exception\MissingTemplateException;

/**
 * Interface for template rendering engines such as twig or ss templates.
 */
interface TemplateEngine
{
    /**
     * Instantiate a TemplateEngine
     *
     * @param string|array $templateCandidates A template or pool of candidate templates to choose from.
     * The template engine will check the currently set themes from SSViewer for template files it can handle
     * from the candidates.
     */
    public function __construct(string|array $templateCandidates = []);

    /**
     * Set the template which will be used in the call to render()
     *
     * @param string|array $templateCandidates A template or pool of candidate templates to choose from.
     * The template engine will check the currently set themes from SSViewer for template files it can handle
     * from the candidates.
     */
    public function setTemplate(string|array $templateCandidates): static;

    /**
     * Check if there is a template amongst the template candidates that this rendering engine can use.
     */
    public function hasTemplate(string|array $templateCandidates): bool;

    /**
     * Render the template string.
     *
     * Doesn't include normalisation such as inserting js/css from Requirements API - that's handled by SSViewer.
     *
     * @param ViewLayerData $model The model to get data from when rendering the template.
     * @param array $overlay Associative array of fields (e.g. args into an include template) to inject into the
     * template as properties. These override properties and methods with the same name from $data and from global
     * template providers.
     */
    public function renderString(string $template, ViewLayerData $model, array $overlay = [], bool $cache = true): string;

    /**
     * Render the template which was selected during instantiation or which was set via setTemplate().
     *
     * Doesn't include normalisation such as inserting js/css from Requirements API - that's handled by SSViewer.
     *
     * @param ViewLayerData $model The model to get data from when rendering the template.
     * @param array $overlay Associative array of fields (e.g. args into an include template) to inject into the
     * template as properties. These override properties and methods with the same name from $data and from global
     * template providers.
     *
     * @throws MissingTemplateException if no template file has been set, or there was no valid template file found from the
     * template candidates
     */
    public function render(ViewLayerData $model, array $overlay = []): string;
}
