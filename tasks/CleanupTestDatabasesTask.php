<?php
/**
 * Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)
 * Task is restricted to users with administrator rights or running through CLI.
 *
 * @package framework
 * @subpackage tasks
 */
class CleanuPTestDatabasesTask extends BuildTask {
	protected $title = 'Deletes all temporary test databases';

	protected $description = 'Cleans up leftover databases from aborted test executions (starting with ss_tmpdb)';

	public function init() {
		parent::init();

		if(!Permission::check('ADMIN') && !Director::is_cli()) {
			return Security::permissionFailure($this);
		}
	}

	public function run() {
		SapphireTest::delete_all_temp_dbs();
	}

}
