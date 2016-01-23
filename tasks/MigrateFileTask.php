<?php

/**
 * Migrates all 3.x file dataobjects to use the new DBFile field.
 *
 * @package framework
 * @subpackage filesystem
 */
class MigrateFileTask extends BuildTask {

	protected $title = 'Migrate File dataobjects from 3.x';

	protected $description
		= 'Imports all files referenced by File dataobjects into the new Asset Persistence Layer introduced in 4.0';

	public function run($request) {
		$migrated = FileMigrationHelper::singleton()->run();
		if($migrated) {
			DB::alteration_message("{$migrated} File DataObjects upgraded", "changed");
		}
	}

}
