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
		// Unless called from the command line, all CliControllers need ADMIN privileges
		if(!Director::is_cli() && !Permission::check("ADMIN")) return Security::permissionFailure();
    }
  
    function index() {
        foreach( ClassInfo::subclassesFor( $this->class ) as $subclass ) {
        	echo $subclass;
        
            $task = new $subclass();
            $task->process();
        }
    }
    
    function process() {}       
} 
?>