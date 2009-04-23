<?php
/**
 * @package sapphire
 * @subpackage tasks
 */
class MigrateTranslatableTask extends BuildTask {
	protected $title = "Migrate Translatable Task";
	
	protected $description = "Migrates site translations from SilverStripe 2.1/2.2 to new database structure.";
	
	function init() {
		if(!Director::is_cli() && !Director::isDev() && !Permission::check("ADMIN")) Security::permissionFailure();
		parent::init();
	}
	
	function run($request) {
		$ids = array();
		
		//$_REQUEST['showqueries'] = 1;
		
		foreach(array('Stage', 'Live') as $stage) {
			echo "<h2>Migrating stage $stage</h2>";
			echo "<ul>";
			
			$suffix = ($stage == 'Live') ? '_Live' : '';
		
			// First get all entries in SiteTree_lang
			// This should be all translated pages
			$trans = DB::query('SELECT * FROM SiteTree_lang' . $suffix);
		
			// Iterate over each translated pages
			foreach($trans as $oldtrans) {
				echo "<li>Migrating $oldtrans[Lang] translation of " . Convert::raw2xml($oldtrans['Title']) . '</li>';
			
				// Get the untranslated page
				$original = Versioned::get_one_by_stage($oldtrans['ClassName'], $stage, '`SiteTree`.ID = ' .  $oldtrans['OriginalLangID']);
				
				// Clone the original, and set it up as a translation
				$newtrans = $original->duplicate(false);
				$newtrans->OriginalID = $original->ID;
				$newtrans->Lang = $oldtrans['Lang'];
				if($stage == 'Live' && array_key_exists($original->ID, $ids)) {
					$newtrans->ID = $ids[$original->ID];
				}
			
				// Look at each class in the ancestry, and see if there is a _lang table for it
				foreach(ClassInfo::ancestry($oldtrans['ClassName']) as $classname) {
					$oldtransitem = false;
				
					// If the class is SiteTree, we already have the DB record, else check for the table and get the record
					if($classname == 'SiteTree') {
						$oldtransitem = $oldtrans;
					} elseif(in_array(strtolower($classname) . '_lang', DB::tableList())) {
						$oldtransitem = DB::query('SELECT * FROM ' . $classname . '_lang' . $suffix . ' WHERE OriginalLangID = ' . $original->ID . ' AND Lang = \'' . $oldtrans['Lang'] . '\'')->first();
					}
				
					// Copy each translated field into the new translation
					if($oldtransitem) foreach($oldtransitem as $key => $value) {
						if(!in_array($key, array('ID', 'OriginalLangID', 'ClassName', 'Lang'))) {
							$newtrans->$key = $value;
						}
					}
			
				}
				
				
				// Write the new translation to the database
				$sitelang = Translatable::current_lang();
				Translatable::set_reading_lang($newtrans->Lang); 
				$newtrans->writeToStage($stage, true);
				Translatable::set_reading_lang($sitelang);
				
				if($stage == 'Stage') {
					$ids[$original->ID] = $newtrans->ID;
				}
			}
			
			echo '</ul>';
		}
		
		echo '<strong>Done!</strong>';
	}
}

?>
