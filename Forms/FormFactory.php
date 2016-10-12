<?php

namespace SilverStripe\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;

/**
 * A service which can generate a form for a given record and controller
 */
interface FormFactory {

	/**
	 * @return DataObject
	 */
	public function getRecord();

	/**
	 * @return Controller
	 */
	public function getController();

	/**
	 * Generates the form
	 *
	 * @param string $name
	 * @return Form
	 */
	public function getForm($name = 'Form');
}
