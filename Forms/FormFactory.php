<?php

namespace SilverStripe\Forms;

use SilverStripe\Control\Controller;

/**
 * A service which can generate a form
 */
interface FormFactory {

	/**
	 * Default form name.
	 */
	const DEFAULT_NAME = 'Form';

	/**
	 * Generates the form
	 *
	 * @param Controller $controller Parent controller
	 * @param string $name
	 * @param array $context List of properties which may influence form scaffolding.
	 * E.g. 'Record' if building a form for a record.
	 * Custom factories may support more advanced parameters.
	 * @return Form
	 */
	public function getForm(Controller $controller, $name = self::DEFAULT_NAME, $context = []);

	/**
	 * Return list of mandatory context keys
	 *
	 * @return mixed
	 */
	public function getRequiredContext();
}
