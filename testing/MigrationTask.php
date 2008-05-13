<?php
/**
 * A migration task is a build task that is reversible.
 * 
 * Up and Down methods must be implemented.
 *
 */
abstract class MigrationTask extends BuildTask {
	
	function run($request) {
		if ($request->param('Direction') == 'down') {
			$this->down();
		} else {
			$this->up();
		}
	}
	
	abstract function up();
	
	abstract function down();
	
}

?>