<?php
/**
 * @package sapphire
 * @subpackage tasks
 */
class UpgradeSiteTreePermissionSchemaTask extends BuildTask {
	static $allowed_actions = array(
		'*' => 'ADMIN'
	);
	
	protected $title = 'Upgrade SiteTree Permissions Schema';
	
	protected $description = "Move data from legacy columns to new schema introduced in SilverStripe 2.1.<br />
		SiteTree->Viewers to SiteTree->CanViewType<br />
		SiteTree->Editors to SiteTree->CanEditType<br />
		SiteTree->ViewersGroup to SiteTree->ViewerGroups (has_one to many_many)<br />
		SiteTree->Editorsroup to SiteTree->EditorGroups (has_one to many_many)<br />
		See http://open.silverstripe.com/ticket/2847
	";
	
	function run($request) {
		// transfer values for changed column name
		foreach(array('SiteTree','SiteTree_Live','SiteTree_versions') as $table) {
			DB::query("UPDATE \"{$table}\" SET \"CanViewType\" = 'Viewers';");
			DB::query("UPDATE \"{$table}\" SET \"CanEditType\" = 'Editors';");
		}
		//Debug::message('Moved SiteTree->Viewers to SiteTree->CanViewType');
		//Debug::message('Moved SiteTree->Editors to SiteTree->CanEditType');
		
		// convert has_many to many_many
		$pageIDs = DB::query("SELECT ID FROM SiteTree")->column('ID');
		foreach($pageIDs as $pageID) {
			$page = DataObject::get_by_id('SiteTree', $pageID);
			if($page->ViewersGroup && DataObject::get_by_id("Group", $page->ViewersGroup)) $page->ViewerGroups()->add($page->ViewersGroup);
			if($page->EditorsGroup && DataObject::get_by_id("Group", $page->EditorsGroup)) $page->EditorGroups()->add($page->EditorsGroup);
			
			$page->destroy();
			unset($page);
		}
		//Debug::message('SiteTree->ViewersGroup to SiteTree->ViewerGroups (has_one to many_many)');
		//Debug::message('SiteTree->EditorsGroup to SiteTree->EditorGroups (has_one to many_many)');
		
		// rename legacy columns
		foreach(array('SiteTree','SiteTree_Live','SiteTree_versions') as $table) {
			foreach(array('Viewers','Editors','ViewersGroup','EditorsGroup') as $field) {
				 DB::getConn()->dontRequireField($table, $field);
			}	
		}
	}
}
?>