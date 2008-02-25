<?php

/**
 * @package sapphire
 * @subpackage cron
 */

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
        foreach( ClassInfo::subclassesFor( $this->class ) as $subclass ) {
        	echo $subclass;
        
            $task = new $subclass();
            $task->process();
        }
    }
    
    function process() {}       
}  
?>
