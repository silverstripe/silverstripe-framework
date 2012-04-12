<?php
/**
 * Abstract task representing scheudled tasks.
 * 
 * Scheduled tasks are tasks that are run at a certain time or set interval. For example, notify a page owner that
 * their page is about to expire. Scheduled tasks are implemented as singleton instances and a single 
 * instance is responsibly directly or  indirectly for executing all tasks that should be run at that time.
 * 
 * You can use the different subclasses {@link HourlyTask}, {@link DailyTask},
 * {@link WeeklyTask} to determine when a task should be run,
 * and use automation tools such as unix cron to trigger them.
 * 
 * <b>Usage</b>
 * 
 * Implement a daily task by extending DailyTask and implementing process().
 * 
 * <code>
 * class MyTask extends DailyTask {
 *     function process() {
 *       // implement your task here
 *     }
 *   }
 * </code>
 * 
 * You can also implement the index() method to overwrite which singleton classes are instantiated and processed. 
 * By default, all subclasses of the task are instantiated and used. For the DailyTask class, this means 
 * that an instance of each subclass of DailyTask will be created.
 * 
 * You can test your task from the command line by running the following command 
 * (replace <MyTask> is the classname of your task):
 * 
 * <code>framework/cli-script.php /<MyTask></code>
 * 
 * To perform all Daily tasks, run from the command line:
 * 
 * <code>cli-script.php /DailyTask</code>
 * 
 * <b>Example Cron Definition</b>
 * 
 * <code>
 * # Quarter-hourly task (every hour at 25 minutes past) (remove space between first * and /15)
 * * /15 * * * *  www-data /my/webroot/framework/cli-script.php /QuarterlyHourlyTask > /var/log/silverstripe_quarterhourlytask.log
 *
 * # HourlyTask (every hour at 25 minutes past)
 * 25 * * * *  www-data /my/webroot/framework/cli-script.php /HourlyTask > /var/log/silverstripe_hourlytask.log
 * 
 * # DailyTask (every day at 6:25am)
 * 25 6 * * *  www-data /my/webroot/framework/cli-script.php /DailyTask > /var/log/silverstripe_dailytask.log
 * 
 * # WeelkyTask (every Monday at 6:25am)
 * 25 6 1 * *  www-data /my/webroot/framework/cli-script.php /WeeklyTask > /var/log/silverstripe_weeklytask.log
 * </code>
 * 
 * @todo Improve documentation
 * @package framework
 * @subpackage cron
 */
abstract class ScheduledTask extends CliController {
	// this class exists as a logical extension
}
