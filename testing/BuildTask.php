<?php

/**
 * Interface for a generic build task. Does not support dependencies. This will simply
 * run a chunk of code when called.
 * 
 * To disable the task (in the case of potentially destructive updates or deletes), declare
 * the $Disabled property on the subclass.
 * 
 * @todo move from sapphire/testing to sapphire/dev or sapphire/development?
 */
abstract class BuildTask {
	
	abstract function run($request);
	
	public function isDisabled() {
		return (property_exists($this, 'Disabled')) ? true : false;
	}
	
}

?>