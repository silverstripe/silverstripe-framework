<?php

namespace SilverStripe\Dev;

use ReflectionClass;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class TaskRunner extends Controller
{

    use Configurable;

    private static $url_handlers = [
        '' => 'index',
        '$TaskName' => 'runTask'
    ];

    private static $allowed_actions = [
        'index',
        'runTask',
    ];

    /**
     * @var array
     */
    private static $css = [
        'silverstripe/framework:client/styles/task-runner.css',
    ];

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
        $baseUrl = Director::absoluteBaseURL();
        $tasks = $this->getTasks();

        if (Director::is_cli()) {
            // CLI mode
            $output = 'SILVERSTRIPE DEVELOPMENT TOOLS: Tasks' . PHP_EOL . '--------------------------' . PHP_EOL . PHP_EOL;

            foreach ($tasks as $task) {
                $output .= sprintf(' * %s: sake dev/tasks/%s%s', $task['title'], $task['segment'], PHP_EOL);
            }

            return $output;
        }

        $list = ArrayList::create();

        foreach ($tasks as $task) {
            $list->push(ArrayData::create([
                'TaskLink' => $baseUrl . 'dev/tasks/' . $task['segment'],
                'Title' => $task['title'],
                'Description' => $task['description'],
            ]));
        }

        $renderer = DebugView::create();
        $header = $renderer->renderHeader();
        $header = $this->addCssToHeader($header);

        $data = [
            'Tasks' => $list,
            'Header' => $header,
            'Footer' => $renderer->renderFooter(),
            'Info' => $renderer->renderInfo('SilverStripe Development Tools: Tasks', $baseUrl),
        ];

        return ViewableData::create()->renderWith(static::class, $data);
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
        $availableTasks = [];

        $taskClasses = ClassInfo::subclassesFor(BuildTask::class);
        // remove the base class
        array_shift($taskClasses);

        foreach ($taskClasses as $class) {
            if (!$this->taskEnabled($class)) {
                continue;
            }

            $singleton = BuildTask::singleton($class);
            $description = $singleton->getDescription();
            $description = trim($description ?? '');

            $desc = (Director::is_cli())
                ? Convert::html2raw($description)
                : $description;

            $availableTasks[] = [
                'class' => $class,
                'title' => $singleton->getTitle(),
                'segment' => $singleton->config()->segment ?: str_replace('\\', '-', $class ?? ''),
                'description' => $desc,
            ];
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

    /**
     * Inject task runner CSS into the heaader

     * @param string $header
     * @return string
     */
    protected function addCssToHeader($header)
    {
        $css = (array) $this->config()->get('css');

        if (!$css) {
            return $header;
        }

        foreach ($css as $include) {
            $path = ModuleResourceLoader::singleton()->resolveURL($include);

            // inject CSS into the heaader
            $element = sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $path);
            $header = str_replace('</head>', $element . '</head>', $header ?? '');
        }

        return $header;
    }
}
