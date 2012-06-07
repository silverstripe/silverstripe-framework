<?php

class GridFieldLevelup implements GridField_HTMLProvider{
	/**
	 * @var integer - the record id of the level up to
	 */
	protected $levelID = null;

	/**
	 *
	 * @param integer $levelID - the record id of the level up to
	 */
	public function __construct($levelID = null) {
		if($levelID && is_numeric($levelID)) {
			$this->levelID = $levelID;
		}
	}
	
	public function getHTMLFragments($gridField) {
		$modelClass = $gridField->getModelClass();
		if(isset($_GET['ParentID']) && $_GET['ParentID']){
			
			$modelObj = DataObject::get_by_id($modelClass, $_GET['ParentID']);
			
			if(is_callable(array($modelObj, 'getParent'))){
				$levelup = $modelObj->getParent();
				if(!$levelup){
					$parentID = 0;
				}else{
					$parentID = $levelup->ID;
				}
			}
			//$controller = $gridField->getForm()->Controller();
			$forTemplate = new ArrayData(array(
				'UpLink' => sprintf(
					'<a class="cms-panel-link list-parent-link" href="?ParentID=%d&view=list" data-pjax-target="ListViewForm,Breadcrumbs">%s</a>',
					$parentID,
					_t('GridField.LEVELUP', 'Level up' )
				),
			));

			return array(
				'before' => $forTemplate->renderWith('GridFieldLevelup'),
				//'header' => $forTemplate->renderWith('GridFieldLevelup_Row'),
			);
		}
	}
} 
?>