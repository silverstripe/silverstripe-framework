<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use ReflectionClass;

class TaskRunner extends Controller
{

    private static $url_handlers = array(
        '' => 'index',
        '$TaskName' => 'runTask'
    );

    private static $allowed_actions = array(
        'index',
        'runTask',
    );

    protected function init()
    {
        parent::init();

        $allowAllCLI = DevelopmentAdmin::config()->get('allow_all_cli');
        $canAccess = (
            Director::isDev()
            // We need to ensure that DevelopmentAdminTest can simulate permission failures when running
            // "dev/tasks" from CLI.
            || (Director::is_cli() && $allowAllCLI)
            || Permission::check("ADMIN")
        );
        if (!$canAccess) {
            Security::permissionFailure($this);
        }
    }

    public function index()
    {
        $tasks = $this->getTasks();

        // Web mode
        if (!Director::is_cli()) {
            $renderer = new DebugView();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("SilverStripe Development Tools: Tasks", Director::absoluteBaseURL());
            $base = Director::absoluteBaseURL();

            echo "<div class=\"options\">";
            echo "<ul>";
            foreach ($tasks as $task) {
                echo "<li><p>";
                echo "<a href=\"{$base}dev/tasks/" . $task['segment'] . "\">" . $task['title'] . "</a><br />";
                echo "<span class=\"description\">" . $task['description'] . "</span>";
                echo "</p></li>\n";
            }
            echo "</ul></div>";

            echo $renderer->renderFooter();
        // CLI mode
        } else {
            echo "SILVERSTRIPE DEVELOPMENT TOOLS: Tasks\n--------------------------\n\n";
            foreach ($tasks as $task) {
                echo " * $task[title]: sake dev/tasks/" . $task['segment'] . "\n";
            }
        }
    }

    /**
     * Runs a BuildTask
     * @param HTTPRequest $request
     */
    public function runTask($request)
    {
        $name = $request->param('TaskName');
        $tasks = $this->getTasks();

        $title = function ($content) {
            printf(Director::is_cli() ? "%s\n\n" : '<h1>%s</h1>', $content);
        };

        $message = function ($content) {
            printf(Director::is_cli() ? "%s\n" : '<p>%s</p>', $content);
        };

        foreach ($tasks as $task) {
            if ($task['segment'] == $name) {
                /** @var BuildTask $inst */
                $inst = Injector::inst()->create($task['class']);
                $title(sprintf('Running Task %s', $inst->getTitle()));

                if (!$inst->isEnabled()) {
                    $message('The task is disabled');
                    return;
                }

                $inst->run($request);
                return;
            }
        }

        $message(sprintf('The build task "%s" could not be found', Convert::raw2xml($name)));
    }

    /**
     * @return array Array of associative arrays for each task (Keys: 'class', 'title', 'description')
     */
    protected function getTasks()
    {
        $availableTasks = array();

        $taskClasses = ClassInfo::subclassesFor(BuildTask::class);
        // remove the base class
        array_shift($taskClasses);

        foreach ($taskClasses as $class) {
            if (!$this->taskEnabled($class)) {
                continue;
            }

            $singleton = BuildTask::singleton($class);

            $desc = (Director::is_cli())
                ? Convert::html2raw($singleton->getDescription())
                : $singleton->getDescription();

            $availableTasks[] = array(
                'class' => $class,
                'title' => $singleton->getTitle(),
                'segment' => $singleton->config()->segment ?: str_replace('\\', '-', $class),
                'description' => $desc,
            );
        }

        return $availableTasks;
    }

    /**
     * @param string $class
     * @return boolean
     */
    protected function taskEnabled($class)
    {
        $reflectionClass = new ReflectionClass($class);
        if ($reflectionClass->isAbstract()) {
            return false;
        } elseif (!singleton($class)->isEnabled()) {
            return false;
        }

        return true;
    }
}
