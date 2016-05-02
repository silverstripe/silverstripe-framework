<?php

/**
 * Interface for a generic build task. Does not support dependencies. This will simply
 * run a chunk of code when called.
 *
 * To disable the task (in the case of potentially destructive updates or deletes), declare
 * the $Disabled property on the subclass.
 *
 * @package framework
 * @subpackage dev
 */
abstract class BuildTask extends Object {

	/**
	 * @var bool $enabled If set to FALSE, keep it from showing in the list
	 * and from being executable through URL or CLI.
	 */
	protected $enabled = true;

	/**
	 * @var string $title Shown in the overview on the {@link TaskRunner}
	 * HTML or CLI interface. Should be short and concise, no HTML allowed.
	 */
	protected $title;

	/**
	 * @var string $description Describe the implications the task has,
	 * and the changes it makes. Accepts HTML formatting.
	 */
	protected $description = 'No description available';

	/**
 	 * Implement this method in the task subclass to
	 * execute via the TaskRunner
	 */
	abstract public function run($request);

	public function isEnabled() {
		return $this->enabled;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return ($this->title) ? $this->title : $this->class;
	}

	/**
	 * @return string HTML formatted description
	 */
	public function getDescription() {
		return $this->description;
	}

}


