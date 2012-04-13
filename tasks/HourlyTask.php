<?php
/**
 * Classes that must be run hourly extend this class
 * 
 * Please note: Subclasses of this task aren't extecuted automatically,
 * they need to be triggered by an external automation tool like unix cron.
 * See {@link ScheduledTask} for details.
 * 
 * @package framework
 * @subpackage cron
 */
class HourlyTask extends ScheduledTask {
	
}
