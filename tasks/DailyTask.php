<?php
/**
 * Classes that must be run daily extend this class.
 *
 * Please note: Subclasses of this task aren't extecuted automatically,
 * they need to be triggered by an external automation tool like unix cron.
 * See {@link ScheduledTask} for details.
 *
 * @deprecated 3.1
 *
 * @todo Improve documentation
 * @package framework
 * @subpackage cron
 */
class DailyTask extends ScheduledTask {

}
