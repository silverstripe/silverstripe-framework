<?php
/**
 * Identify "orphaned" pages which point to a parent
 * that no longer exists in a specific stage.
 * Shows the pages to an administrator, who can then
 * decide which pages to remove by ticking a checkbox
 * and manually executing the removal.
 * 
 * Caution: Pages also count as orphans if they don't
 * have parents in this stage, even if the parent has a representation
 * in the other stage:
 * - A live child is orphaned if its parent was deleted from live, but still exists on stage
 * - A stage child is orphaned if its parent was deleted from stage, but still exists on live
 *
 * See {@link RemoveOrphanedPagesTaskTest} for an example sitetree
 * before and after orphan removal.
 *
 * @author Ingo Schommer (<firstname>@silverstripe.com), SilverStripe Ltd.
 * 
 * @package sapphire
 * @subpackage tasks
 */
//class RemoveOrphanedPagesTask extends BuildTask {
class RemoveOrphanedPagesTask extends Controller {
	
	static $allowed_actions = array(
		'index' => 'ADMIN',
		'Form' => 'ADMIN',
		'run' => 'ADMIN',
		'handleAction' => 'ADMIN',
	);
	
	protected $title = 'Removed orphaned pages without existing parents from both stage and live';
	
	protected $description = "
<p>
Identify 'orphaned' pages which point to a parent
that no longer exists in a specific stage.
</p>
<p>
Caution: Pages also count as orphans if they don't
have parents in this stage, even if the parent has a representation
in the other stage:<br />
- A live child is orphaned if its parent was deleted from live, but still exists on stage<br />
- A stage child is orphaned if its parent was deleted from stage, but still exists on live
</p>
	";
	
	protected $orphanedSearchClass = 'SiteTree';
	
	function Link() {
		return $this->class;
	}
	
	function index() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::customCSS('#OrphanIDs .middleColumn {width: auto;}');
		Requirements::customCSS('#OrphanIDs label {display: inline;}');
		
		return $this->renderWith('BlankPage');
	}
	
	function Form() {
		$fields = new FieldSet();
		$source = array();
		
		$fields->push(new HeaderField(
			'Header',
			_t('RemoveOrphanedPagesTask.HEADER', 'Remove all orphaned pages task')
		));
		$fields->push(new LiteralField(
			'Description',
			$this->description
		));
		
		$orphans = $this->getOrphanedPages($this->orphanedSearchClass);
		if($orphans) foreach($orphans as $orphan) {
			$latestVersion = Versioned::get_latest_version($this->orphanedSearchClass, $orphan->ID);
			$latestAuthor = DataObject::get_by_id('Member', $latestVersion->AuthorID);
			$stageRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Stage', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$orphan->ID
				)
			);
			$liveRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Live', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$orphan->ID
				)
			);
			$label = sprintf(
				'<a href="admin/show/%d">%s</a> <small>(#%d, Last Modified Date: %s, Last Modifier: %s, %s)</small>',
				$orphan->ID,
				$orphan->Title,
				$orphan->ID,
				DBField::create('Date', $orphan->LastEdited)->Nice(),
				($latestAuthor) ? $latestAuthor->Title : 'unknown',
				($liveRecord) ? 'is published' : 'not published'
			);
			$source[$orphan->ID] = $label;
		}
		
		if($orphans && $orphans->Count()) {
			$fields->push(new CheckboxSetField('OrphanIDs', false, $source));
			$fields->push(new LiteralField(
				'SelectAllLiteral',
				sprintf(
					'<p><a href="#" onclick="javascript:jQuery(\'#Form_Form_OrphanIDs :checkbox\').attr(\'checked\', \'checked\'); return false;">%s</a></p>',
					_t('RemoveOrphanedPagesTask.SELECTALL', 'select all')
				)
			));
			$fields->push(new OptionSetField(
				'OrphanOperation', 
				_t('RemoveOrphanedPagesTask.CHOOSEOPERATION', 'Choose operation:'),
				array(
					'rebase' => _t('RemoveOrphanedPagesTask.OPERATION_REBASE', 'Rebase selected to tree root and unpublish'),
					'remove' => _t('RemoveOrphanedPagesTask.OPERATION_REMOVE', 'Remove selected from all stages (WARNING: Will destroy all selected pages from both stage and live)'),
				),
				'rebase'
			));
			$fields->push(new LiteralField(
				'Warning',
				sprintf('<p class="message">%s</p>',
					_t(
						'RemoveOrphanedPagesTask.DELETEWARNING', 
						'Warning: These operations are not reversible. Please handle with care.'
					)
				)
			));
		} else {
			$fields->push(new LiteralField(
				'NotFoundLabel',
				sprintf(
					'<p class="message">%s</p>',
					_t('RemoveOrphanedPagesTask.NONEFOUND', 'No orphans found')
				)
			));
		}
		
		$form = new Form(
			$this,
			'Form',
			$fields,
			new FieldSet(
				new FormAction('doSubmit', _t('RemoveOrphanedPagesTask.BUTTONRUN', 'Run'))
			)
		);
		
		if(!$orphans || !$orphans->Count()) {
			$form->makeReadonly();
		}
		
		return $form;
	}
	
	function run($request) {
		// @todo Merge with BuildTask functionality
	}
	
	function doSubmit($data, $form) {
		if(!isset($data['OrphanIDs']) || !isset($data['OrphanOperation'])) return false;
		
		switch($data['OrphanOperation']) {
			case 'remove':
				$successIDs = $this->removeOrphans($data['OrphanIDs']);
				break;
			case 'rebase':
				$successIDs = $this->rebaseOrphans($data['OrphanIDs']);
				break;
			default:
				user_error(sprintf("Unknown operation: '%s'", $data['OrphanOperation']), E_USER_ERROR);
		}
		
		$content = '';
		if($successIDs) {
			$content .= "<ul>";
			foreach($successIDs as $id => $label) {
				$content .= sprintf('<li>%s</li>', $label);
			}
			$content .= "</ul>";
		} else {
			$content = _t('RemoveOrphanedPagesTask.NONEREMOVED', 'None removed');
		}
		
		return $this->customise(array(
			'Content' => $content,
			'Form' => ' '
		))->renderWith('BlankPage');
	}
	
	protected function removeOrphans($orphanIDs) {
		$removedOrphans = array();
		foreach($orphanIDs as $id) {
			$stageRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Stage', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$id
				)
			);
			if($stageRecord) {
				$removedOrphans[$stageRecord->ID] = sprintf('Removed %s (#%d) from Stage', $stageRecord->Title, $stageRecord->ID);
				$stageRecord->delete();
				$stageRecord->destroy();
				unset($stageRecord);
			}
			$liveRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Live', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$id
				)
			);
			if($liveRecord) {
				$removedOrphans[$liveRecord->ID] = sprintf('Removed %s (#%d) from Live', $liveRecord->Title, $liveRecord->ID);
				$liveRecord->doDeleteFromLive();
				$liveRecord->destroy();
				unset($liveRecord);
			}
		}
		
		return $removedOrphans;
	}
	
	protected function rebaseOrphans($orphanIDs) {
		$removedOrphans = array();
		foreach($orphanIDs as $id) {
			$stageRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Stage', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$id
				)
			);
			if($stageRecord) {
				$removedOrphans[$stageRecord->ID] = sprintf('Rebased %s (#%d)', $stageRecord->Title, $stageRecord->ID);
				$stageRecord->ParentID = 0;
				$stageRecord->write();
				$stageRecord->doUnpublish();
				$stageRecord->destroy();
				unset($stageRecord);
			}
			$liveRecord = Versioned::get_one_by_stage(
				$this->orphanedSearchClass, 
				'Live', 
				sprintf('`%s`.`ID` = %d', 
					ClassInfo::baseDataClass($this->orphanedSearchClass), 
					$id
				)
			);
			if($liveRecord) {
				$removedOrphans[$liveRecord->ID] = sprintf('Rebased %s (#%d)', $liveRecord->Title, $liveRecord->ID);
				$liveRecord->ParentID = 0;
				$liveRecord->write();
				$liveRecord->doUnpublish();
				$liveRecord->destroy();
				unset($liveRecord);
			}
		}
		
		return $removedOrphans;
	}
	
	/**
	 * Gets all orphans from "Stage" and "Live" stages.
	 * 
	 * @param string $class
	 * @param string $filter
	 * @param string $sort
	 * @param string $join
	 * @param int|array $limit
	 * @return DataObjectSet
	 */
	protected function getOrphanedPages($class = 'SiteTree', $filter = '', $sort = null, $join = null, $limit = null) {
		$filter .= ($filter) ? ' AND ' : '';
		$filter .= sprintf('`%s`.`ParentID` != 0 AND `Parents`.`ID` IS NULL', $class);
		
		$orphans = new DataObjectSet();
		foreach(array('Stage', 'Live') as $stage) {
			$joinByStage = $join;
			$table = $class;
			$table .= ($stage == 'Live') ? '_Live' : '';
			$joinByStage .= sprintf(
				'LEFT JOIN `%s` AS `Parents` ON `%s`.`ParentID` = `Parents`.`ID`',
				$table,
				$table
			);
			$stageOrphans = Versioned::get_by_stage(
				$class,
				$stage,
				$filter,
				$sort,
				$joinByStage,
				$limit
			);
			$orphans->merge($stageOrphans);
		}
		
		$orphans->removeDuplicates();
	
		return $orphans;
	}
}
?>