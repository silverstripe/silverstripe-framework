<?php
/**
 * @package framework
 * @subpackage tasks
 */
class i18nTextCollectorTask extends BuildTask {
	
	protected $title = "i18n Textcollector Task";
	
	protected $description = "
		Traverses through files in order to collect the 'entity master tables'
		stored in each module.

		Parameters:
		- locale: Sets default locale
		- writer: Custom writer class (defaults to i18nTextCollector_Writer_RailsYaml)
		- module: One or more modules to limit collection (comma-separated)
		- merge: Merge new strings with existing ones already defined in language files (default: FALSE)
	";
	
	public function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) {
			return Security::permissionFailure($this);
		}
	}
	
	/**
	 * This is the main method to build the master string tables with the original strings.
	 * It will search for existent modules that use the i18n feature, parse the _t() calls
	 * and write the resultant files in the lang folder of each module.
	 * 
	 * @uses DataObject->collectI18nStatics()
	 * 
	 * @param SS_HTTPRequest $request
	 */	
	public function run($request) {
		increase_time_limit_to();
		$collector = i18nTextCollector::create($request->getVar('locale'));
		
		$merge = $this->getIsMerge($request);
		
		// Custom writer
		$writerName = $request->getVar('writer');
		if($writerName) {
			$writer = Injector::inst()->get($writerName);
			$collector->setWriter($writer);
		}
		
		// Get restrictions
		$restrictModules = ($request->getVar('module'))
			? explode(',', $request->getVar('module'))
			: null;
		
		$collector->run($restrictModules, $merge);
		
		Debug::message(__CLASS__ . " completed!", false);
	}
	
	/**
	 * Check if we should merge
	 * 
	 * @param SS_HTTPRequest $request
	 */
	protected function getIsMerge($request) {
		$merge = $request->getVar('merge');
		
		// Default to false if not given
		if(!isset($merge)) {
			Deprecation::notice(
				"4.0",
				"merge will be enabled by default in 4.0. Please use merge=false if you do not want to merge."
			);
			return false;
		}

		// merge=0 or merge=false will disable merge
		return !in_array($merge, array('0', 'false'));
	}
}
