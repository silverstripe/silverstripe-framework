<?php

/**
 * @package forms
 * @subpackage core
 */

/**
 * Writes the POST array to a file as a last-ditch effort to preserve entered data.
 * @package forms
 * @subpackage core
 */
class PostBackup extends Object {
	
	static function writeToFile($data, $controller, $form) {
		
		// the static variable defines whether or not to backup a posted form
		if(!$form->stat('backup_post_data'))
			return;
		
		// Append to the file
		if(!file_exists(BACKUP_DIR))
			mkdir(BACKUP_DIR, Filesystem::$folder_create_mask, true);
		
		$backupFile = fopen(BACKUP_DIR . '/' . $form->class, 'a');
		
		$date = date('Y-m-d G:i:s');
		
		$postData = var_export($data, true);
		
		$backup = <<<BAK
***BEGIN ENTRY***
Date and time:		{$date}
URL:				http://{$_SERVER['HTTP_HOST']}:{$_SERVER['SERVER_PORT']}{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}	
Client IP:			{$_SERVER['REMOTE_ADDR']}
Controller:			{$controller->class}

$postData
***END ENTRY***
BAK;

		fwrite($backupFile, $backup);
		fclose($backupFile);
	}
	
}
?>
