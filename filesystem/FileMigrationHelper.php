<?php

use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\Model\DB;


/**
 * Service to help migrate File dataobjects to the new APL.
 *
 * This service does not alter these records in such a way that prevents downgrading back to 3.x
 *
 * @package framework
 * @subpackage filesystem
 */
class FileMigrationHelper extends Object {

	/**
	 * Perform migration
	 *
	 * @param string $base Absolute base path (parent of assets folder). Will default to BASE_PATH
	 * @return int Number of files successfully migrated
	 */
	public function run($base = null) {
		if(empty($base)) {
			$base = BASE_PATH;
		}
		// Check if the File dataobject has a "Filename" field.
		// If not, cannot migrate
		if(!DB::get_schema()->hasField('File', 'Filename')) {
			return 0;
		}

		// Set max time and memory limit
		increase_time_limit_to();
		increase_memory_limit_to();

		// Loop over all files
		$count = 0;
		$originalState = Versioned::get_reading_mode();
		Versioned::set_stage(Versioned::DRAFT);
		$filenameMap = $this->getFilenameArray();
		foreach($this->getFileQuery() as $file) {
			// Get the name of the file to import
			$filename = $filenameMap[$file->ID];
			$success = $this->migrateFile($base, $file, $filename);
			if($success) {
				$count++;
			}
		}
		Versioned::set_reading_mode($originalState);
		return $count;
	}

	/**
	 * Migrate a single file
	 *
	 * @param string $base Absolute base path (parent of assets folder)
	 * @param File $file
	 * @param string $legacyFilename
	 * @return bool True if this file is imported successfully
	 */
	protected function migrateFile($base, File $file, $legacyFilename) {
		// Make sure this legacy file actually exists
		$path = $base . '/' . $legacyFilename;
		if(!file_exists($path)) {
			return false;
		}


		// Copy local file into this filesystem
		$filename = $file->generateFilename();
		$result = $file->setFromLocalFile(
			$path, $filename, null, null,
			array('conflict' => AssetStore::CONFLICT_OVERWRITE)
		);

		// Move file if the APL changes filename value
		if($result['Filename'] !== $filename) {
			$file->setFilename($result['Filename']);
		}

		// Save and publish
		$file->write();
		$file->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
		return true;
	}

	/**
	 * Get list of File dataobjects to import
	 *
	 * @return DataList
	 */
	protected function getFileQuery() {
		// Select all records which have a Filename value, but not FileFilename.
		return File::get()
			->exclude('ClassName', 'Folder')
			->filter('FileFilename', array('', null))
			->where('"File"."Filename" IS NOT NULL AND "File"."Filename" != \'\''); // Non-orm field
	}

	/**
	 * Get map of File IDs to legacy filenames
	 *
	 * @return array
	 */
	protected function getFilenameArray() {
		// Convert original query, ensuring the legacy "Filename" is included in the result
		return $this
			->getFileQuery()
			->dataQuery()
			->selectFromTable('File', array('ID', 'Filename'))
			->execute()
			->map(); // map ID to Filename
	}
}
