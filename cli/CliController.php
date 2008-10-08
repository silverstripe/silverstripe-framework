<?php
/**
 * Base class invoked from CLI rather than the webserver (Cron jobs, handling email bounces)
 * @package sapphire
 * @subpackage cron
 */
abstract class CliController extends Controller {
    function init() {
      $this->disableBasicAuth();
      parent::init();
    }
  
    function index() {
		// Always re-compile the manifest (?flush=1)
       	ManifestBuilder::update_db_tables(DB::getConn()->tableList(), $_ALL_CLASSES);
		ManifestBuilder::write_manifest();

        foreach( ClassInfo::subclassesFor( $this->class ) as $subclass ) {
        	echo $subclass;
        
            $task = new $subclass();
            $task->process();
        }
    }
    
    function process() {}       
}  
?>
