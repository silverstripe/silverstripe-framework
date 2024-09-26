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
use SilverStripe\Model\List\ArrayList;
use SilverStripe\PolyExecution\HtmlOutputFormatter;
use SilverStripe\PolyExecution\HttpRequestInput;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelData;

class TaskRunner extends Controller implements PermissionProvider
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

    private static $init_permissions = [
        'ADMIN',
        'ALL_DEV_ADMIN',
        'BUILDTASK_CAN_RUN',
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

        if (!$this->canInit()) {
            Security::permissionFailure($this);
        }
    }

    public function index()
    {
        $baseUrl = Director::absoluteBaseURL();
        $tasks = $this->getTasks();
        $list = ArrayList::create();
        foreach ($tasks as $task) {
            if (!$task['class']::canRunInBrowser()) {
                continue;
            }
            $list->push(ArrayData::create([
                'TaskLink' => Controller::join_links($baseUrl, 'dev/tasks/', $task['segment']),
                'Title' => $task['title'],
                'Description' => $task['description'],
                'Parameters' => $task['parameters'],
                'Help' => $task['help'],
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

        return ModelData::create()->renderWith(static::class, $data);
    }

    /**
     * Runs a BuildTask
     * @param HTTPRequest $request
     */
    public function runTask($request)
    {
        $name = $request->param('TaskName');
        $tasks = $this->getTasks();

        $message = function ($content) {
            printf('<p>%s</p>', $content);
        };

        foreach ($tasks as $task) {
            if ($task['segment'] == $name) {
                /** @var BuildTask $inst */
                $inst = Injector::inst()->create($task['class']);

                if (!$this->taskEnabled($task['class']) || !$task['class']::canRunInBrowser()) {
                    $message('The task is disabled or you do not have sufficient permission to run it');
                    return;
                }

                $input = HttpRequestInput::create($request, $inst->getOptions());
                // DO NOT use a buffer here to capture the output - we explicitly want the output to be streamed
                // to the client as its available, so that if there's an error the client gets all of the output
                // available until the error occurs.
                $output = PolyOutput::create(PolyOutput::FORMAT_HTML, $input->getVerbosity(), true);
                $inst->run($input, $output);
                return;
            }
        }

        $message(sprintf('The build task "%s" could not be found, is disabled or you do not have sufficient permission to run it', Convert::raw2xml($name)));
    }

    /**
     * Get an associative array of task names to classes for all enabled BuildTasks
     */
    public function getTaskList(): array
    {
        $taskList = [];
        $taskClasses = ClassInfo::subclassesFor(BuildTask::class, false);
        foreach ($taskClasses as $taskClass) {
            if ($this->taskEnabled($taskClass)) {
                $taskList[$taskClass::getName()] = $taskClass;
            }
        }
        return $taskList;
    }

    /**
     * Get the class names of all build tasks for use in HTTP requests
     */
    protected function getTasks(): array
    {
        $availableTasks = [];
        $formatter = HtmlOutputFormatter::create();

        /** @var BuildTask $class */
        foreach ($this->getTaskList() as $class) {
            if (!$class::canRunInBrowser()) {
                continue;
            }

            $singleton = BuildTask::singleton($class);
            $description = DBField::create_field('HTMLText', $formatter->format($class::getDescription()));
            $help = DBField::create_field('HTMLText', nl2br($formatter->format($class::getHelp())), false);

            $availableTasks[] = [
                'class' => $class,
                'title' => $singleton->getTitle(),
                'segment' => $class::getNameWithoutNamespace(),
                'description' => $description,
                'parameters' => $singleton->getOptionsForTemplate(),
                'help' => $help,
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
        }

        /** @var BuildTask $task */
        $task = Injector::inst()->get($class);
        if (!$task->isEnabled()) {
            return false;
        }

        if ($task->hasMethod('canView') && !$task->canView()) {
            return false;
        }

        return $this->canViewAllTasks();
    }

    protected function canViewAllTasks(): bool
    {
        return (
            Director::isDev()
            // We need to ensure that unit tests can simulate permission failures when navigating to "dev/tasks"
            || (Director::is_cli() && DevelopmentAdmin::config()->get('allow_all_cli'))
            || Permission::check(static::config()->get('init_permissions'))
        );
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

    public function canInit(): bool
    {
        if ($this->canViewAllTasks()) {
            return true;
        }
        return count($this->getTaskList()) > 0;
    }

    public function providePermissions(): array
    {
        return [
            'BUILDTASK_CAN_RUN' => [
                'name' => _t(__CLASS__ . '.BUILDTASK_CAN_RUN_DESCRIPTION', 'Can view and execute all /dev/tasks'),
                'help' => _t(__CLASS__ . '.BUILDTASK_CAN_RUN_HELP', 'Can view and execute all Build Tasks (/dev/tasks). This may still be overriden by individual task view permissions'),
                'category' => DevelopmentAdmin::permissionsCategory(),
                'sort' => 70
            ],
        ];
    }
}
