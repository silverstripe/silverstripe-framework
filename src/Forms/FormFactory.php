<?php

namespace SilverStripe\Forms;

use SilverStripe\Control\RequestHandler;

/**
 * A service which can generate a form
 */
interface FormFactory
{

    /**
     * Default form name.
     */
    const DEFAULT_NAME = 'Form';

    /**
     * Generates the form
     *
     * @param RequestHandler $controller Parent controller
     * @param string $name
     * @param array $context List of properties which may influence form scaffolding.
     * E.g. 'Record' if building a form for a record.
     * Custom factories may support more advanced parameters.
     * @return Form
     */
    public function getForm(RequestHandler $controller = null, $name = FormFactory::DEFAULT_NAME, $context = []);

    /**
     * Return list of mandatory context keys
     *
     * @return array
     */
    public function getRequiredContext();
}
