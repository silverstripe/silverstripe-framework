<?php
/**
 * A migration task is a build task that is reversible.
 * 
 * Up and Down methods must be implemented.
 * 
 * @package sapphire
 * @subpackage dev
 */
class MigrationTask extends BuildTask {
	
	protected $title = "Database Migrations";
	
	protected $description = "Provide atomic database changes (not implemented yet)";
	
	function run($request) {
		if ($request->param('Direction') == 'down') {
			$this->down();
		} else {
			$this->up();
		}
	}
	
	function up() {}
	
	function down() {}
	
}

?>