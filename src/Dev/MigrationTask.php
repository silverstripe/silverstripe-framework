<?php

namespace SilverStripe\Dev;

/**
 * A migration task is a build task that is reversible.
 *
 * <b>Creating Migration Tasks</b>
 *
 * To create your own migration task, you need to define your own subclass of MigrationTask
 * and implement the following methods
 *
 * <i>app/src/MyMigrationTask.php</i>
 *
 * <code>
 * class MyMigrationTask extends MigrationTask {
 *
 *  private static $segment = 'MyMigrationTask'; // segment in the dev/tasks/ namespace for URL access
 *  protected $title = "My Database Migrations"; // title of the script
 *  protected $description = "My Description"; // description of what it does
 *
 *  public function run($request) {
 *      if ($request->getVar('Direction') == 'down') {
 *          $this->down();
 *      } else {
 *          $this->up();
 *      }
 *  }
 *
 *  public function up() {
 *      // do something when going from old -> new
 *  }
 *
 *  public function down() {
 *      // do something when going from new -> old
 *  }
 * }
 * </code>
 *
 * <b>Running Migration Tasks</b>
 * You can find all tasks under the dev/tasks/ namespace.
 * To run the above script you would need to run the following and note - Either the site has to be
 * in [devmode](debugging) or you need to add ?isDev=1 to the URL.
 *
 * <code>
 * // url to visit if in dev mode.
 * https://www.yoursite.com/dev/tasks/MyMigrationTask
 *
 * // url to visit if you are in live mode but need to run this
 * https://www.yoursite.com/dev/tasks/MyMigrationTask?isDev=1
 * </code>
 */
abstract class MigrationTask extends BuildTask
{

    private static $segment = 'MigrationTask';

    protected $title = "Database Migrations";

    protected $description = "Provide atomic database changes (subclass this and implement yourself)";

    public function run($request)
    {
        if ($request->param('Direction') == 'down') {
            $this->down();
        } else {
            $this->up();
        }
    }

    public function up()
    {
    }

    public function down()
    {
    }
}
