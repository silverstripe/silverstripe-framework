<?php
/**
 * Classes that must be run yearly extend this class
 *
 * Please note: Subclasses of this task aren't extecuted automatically,
 * they need to be triggered by an external automation tool like unix cron.
 * See {@link ScheduledTask} for details.
 *
 * @deprecated 3.1
 *
 * @package framework
 * @subpackage cron
 */
class YearlyTask extends ScheduledTask {

}
