<?php
/**
 * Rewrites plain internal HTML links into shortcode form, using existing link tracking information.
 *
 * @package sapphire
 * @subpackage tasks
 */
class MigrateSiteTreeLinkingTask extends BuildTask {
	
	protected $title = 'Migrate SiteTree Linking Task';
	
	protected $description = 'Rewrites plain internal HTML links into shortcode form, using existing link tracking information.';
	
	public function run($request) {
		$pages = 0;
		$links = 0;
		
		$linkedPages = DataObject::get(
			'SiteTree',
			null,
			null,
			'INNER JOIN "SiteTree_LinkTracking" ON "SiteTree_LinkTracking"."SiteTreeID" = "SiteTree"."ID"'
		);
		
		if($linkedPages) foreach($linkedPages as $page) {
			$tracking = DB::query(sprintf('SELECT "ChildID", "FieldName" FROM "SiteTree_LinkTracking" WHERE "SiteTreeID" = %d', $page->ID))->map();

			foreach($tracking as $childID => $fieldName) {
				$linked = DataObject::get_by_id('SiteTree', $childID);
				
				// TOOD: Replace in all HTMLText fields
				$page->Content = preg_replace (
					"/href *= *([\"']?){$linked->URLSegment}\/?/i",
					"href=$1[sitetree_link id={$linked->ID}]",
					$page->Content,
					-1,
					$replaced
				);
				
				if($replaced) {
					$links += $replaced;
				}
			}
			
			$page->write();
			$pages++;
		}
		
		echo "Rewrote $links link(s) on $pages page(s) to use shortcodes.\n";
	}
	
}