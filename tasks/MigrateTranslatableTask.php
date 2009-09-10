<?php
/**
 * Migrates the old Translatable datamodel introduced in SilverStripe 2.1 to the new schema
 * introduced in SilverStripe 2.3.2.
 * Just works for {@link SiteTree} records and subclasses. If you have used the Translatable
 * extension on other {@link DataObject} subclasses before, this script won't migrate them automatically.
 * 
 * <h2>Limitations</h2>
 * 
 * - Information from the {@link Versioned} extension (e.g. in "SiteTree_versions" table)
 *   will be discarded for translated records.
 * - Custom translatable fields on your own {@link Page} class or subclasses thereof won't
 *   be migrated into the translation.
 * - 2.1-style subtags of a language (e.g. "en") will be automatically disambiguated to their full
 *   locale value (e.g. "en_US"), by the lookup defined in {@link i18n::get_locale_from_lang()}.
 * - Doesn't detect published translations when the script is run twice on the same data set
 * 
 * <h2>Usage</h2>
 * 
 * PLEASE BACK UP YOUR DATABASE BEFORE RUNNING THIS SCRIPT.
 * 
 * Warning: Please run dev/build on your 2.2 database to update the schema before running this task.
 * The dev/build command will rename tables like "SiteTree_lang" to "_obsolete_SiteTree_lang".
 *
 * <h3>Commandline</h3>
 * Requires "sake" tool (see http://doc.silverstripe.com/?id=sake)
 * <example>
 * sake dev/tasks/MigrateTranslatableTask
 * </example>
 *
 * <h3>Browser</h3>
 * <example>
 * http://mydomain.com/dev/tasks/MigrateTranslatableTask
 * </example>
 * 
 * @package sapphire
 * @subpackage tasks
 */
class MigrateTranslatableTask extends BuildTask {
	protected $title = "Migrate Translatable Task";
	
	protected $description = "Migrates site translations from SilverStripe 2.1/2.2 to new database structure.";
	
	function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
	}
	
	function run($request) {
		$ids = array();
		
		echo "#################################\n";
		echo "# Adding translation groups to existing records" . "\n";
		echo "#################################\n";
		
		$allSiteTreeIDs = DB::query('SELECT "ID" FROM "SiteTree"')->column();
		if($allSiteTreeIDs) foreach($allSiteTreeIDs as $id) {
			$original = DataObject::get_by_id('SiteTree', $id);
			$existingGroupID = $original->getTranslationGroup();
			if(!$existingGroupID) $original->addTranslationGroup($original->ID);
			$original->destroy();
			unset($original);
		}
		
		DataObject::flush_and_destroy_cache();
		
		echo sprintf("Created translation groups for %d records\n", count($allSiteTreeIDs));

		foreach(array('Stage', 'Live') as $stage) {
			echo "\n\n#################################\n";
			echo "# Migrating stage $stage" . "\n";
			echo "#################################\n";
			
			$suffix = ($stage == 'Live') ? '_Live' : '';
		
			// First get all entries in SiteTree_lang
			// This should be all translated pages
			$trans = DB::query(sprintf('SELECT * FROM "_obsolete_SiteTree_lang%s"', $suffix));
		
			// Iterate over each translated pages
			foreach($trans as $oldtrans) {
				$newLocale = i18n::get_locale_from_lang($oldtrans['Lang']);
				
				echo sprintf(
					"Migrating from %s to %s translation of '%s' (#%d)\n", 
					$oldtrans['Lang'],
					$newLocale, 
					Convert::raw2xml($oldtrans['Title']), 
					$oldtrans['OriginalLangID']
				);
			
				// Get the untranslated page
				
				$original = Versioned::get_one_by_stage(
					$oldtrans['ClassName'], 
					$stage, 
					'"SiteTree"."ID" = ' .  $oldtrans['OriginalLangID']
				);
				
				if(!$original) {
					echo sprintf("Couldn't find original for #%d", $oldtrans['OriginalLangID']);
					continue;
				}
				
				// write locale to $original
				$original->Locale = i18n::get_locale_from_lang(Translatable::default_lang());
				$original->writeToStage($stage);
				
				// Clone the original, and set it up as a translation
				$existingTrans = $original->getTranslation($newLocale, $stage);
				
				if($existingTrans) {
					echo sprintf("Found existing new-style translation for #%d. Already merged? Skipping.\n", $oldtrans['OriginalLangID']);
					continue;
				}
				
				// Doesn't work with stage/live split
				//$newtrans = $original->createTranslation($newLocale);
				
				$newtrans = $original->duplicate(false);
				$newtrans->OriginalID = $original->ID;
				// we have to "guess" a locale based on the language
				$newtrans->Locale = $newLocale;
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
						$oldtransitem = DB::query(sprintf(
							'SELECT * FROM "_obsolete_%s_lang%s" WHERE "OriginalLangID" = %d AND "Lang" = \'%s\'',
							$classname,
							$suffix,
							$original->ID,
							$oldtrans['Lang']
						))->first();
					}
				
					// Copy each translated field into the new translation
					if($oldtransitem) foreach($oldtransitem as $key => $value) {
						if(!in_array($key, array('ID', 'OriginalLangID'))) {
							$newtrans->$key = $value;
						}
					}
			
				}

				// Write the new translation to the database
				$sitelang = Translatable::get_current_locale();
				Translatable::set_current_locale($newtrans->Locale); 
				$newtrans->writeToStage($stage);
				Translatable::set_current_locale($sitelang);
				
				$newtrans->addTranslationGroup($original->getTranslationGroup(), true);

				
				if($stage == 'Stage') {
					$ids[$original->ID] = $newtrans->ID;
				}
			}
		}
		
		echo "\n\n#################################\n";
		echo "Done!\n";
	}
}

?>