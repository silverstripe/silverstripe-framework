<?php

namespace SilverStripe\Dev;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Command\DevCommand;
use SilverStripe\PolyExecution\HtmlOutputFormatter;
use SilverStripe\PolyExecution\HttpRequestInput;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Model\ModelData;

/**
 * Base class for development tools.
 *
 * Configured via the `commands` and `controllers` configuration properties
 */
class DevelopmentAdmin extends Controller implements PermissionProvider
{
    private static array $url_handlers = [
        '' => 'index',
        '$Action' => 'runRegisteredAction',
    ];

    private static array $allowed_actions = [
        'index',
        'runRegisteredAction',
    ];

    /**
     * Commands for dev admin views.
     *
     * Register any DevCommand classes that you want to be under the `/dev/*` HTTP
     * route and also accessible by CLI.
     *
     * e.g [
     *     'command-one' => 'App\Dev\CommandOne',
     * ]
     */
    private static array $commands = [];

    /**
     * Controllers for dev admin views.
     *
     * This is for HTTP-only controllers routed under `/dev/*` which
     * cannot be managed via CLI (e.g. an interactive GraphQL IDE).
     * For most purposes, register a PolyCommand under $commands instead.
     *
     * e.g [
     *     'urlsegment' => [
     *         'class' => 'App\Dev\MyHttpOnlyController',
     *         'description' => 'See a list of build tasks to run',
     *     ],
     * ]
     */
    private static array $controllers = [];

    /**
     * Assume that CLI equals admin permissions
     * If set to false, normal permission model will apply even in CLI mode
     * Applies to all development admin tasks (E.g. TaskRunner, DbBuild)
     */
    private static bool $allow_all_cli = true;

    /**
     * Deny all non-cli requests (browser based ones) to dev admin
     */
    private static bool $deny_non_cli = false;

    protected function init()
    {
        parent::init();

        if (static::config()->get('deny_non_cli') && !Director::is_cli()) {
            return $this->httpError(404);
        }

        if (!$this->canViewAll() && empty($this->getLinks())) {
            Security::permissionFailure($this);
            return;
        }

        // Default to "draft" stage, which is important
        // for tasks like dev/build which call DataObject->requireDefaultRecords(),
        // but also for other administrative tasks which have assumptions about the default stage.
        if (class_exists(Versioned::class)) {
            Versioned::set_stage(Versioned::DRAFT);
        }
    }

    /**
     * Renders the main /dev menu in the browser
     */
    public function index()
    {
        $renderer = DebugView::create();
        $base = Director::baseURL();
        $formatter = HtmlOutputFormatter::create();

        $list = [];

        foreach ($this->getLinks() as $path => $info) {
            $class = $info['class'];
            $description = $info['description'] ?? '';
            $parameters = null;
            $help = null;
            if (is_a($class, DevCommand::class, true)) {
                $parameters = $class::singleton()->getOptionsForTemplate();
                $description = DBField::create_field('HTMLText', $formatter->format($class::getDescription()));
                $help = DBField::create_field('HTMLText', nl2br($formatter->format($class::getHelp())), false);
            }
            $data = [
                'Description' => $description,
                'Link' => "{$base}$path",
                'Path' => $path,
                'Parameters' => $parameters,
                'Help' => $help,
            ];
            $list[] = $data;
        }

        $data = [
            'ArrayLinks' => $list,
            'Header' => $renderer->renderHeader(),
            'Footer' => $renderer->renderFooter(),
            'Info' => $renderer->renderInfo("SilverStripe Development Tools", Director::absoluteBaseURL()),
        ];

        return ModelData::create()->renderWith(static::class, $data);
    }

    /**
     * Run the command, or hand execution to the controller.
     * Note this method is for execution from the web only. CLI takes a different path.
     */
    public function runRegisteredAction(HTTPRequest $request)
    {
        $returnUrl = $this->getBackURL();
        $fullPath = $request->getURL();
        $routes = $this->getRegisteredRoutes();
        $class = null;

        // If full path directly matches, use that class.
        if (isset($routes[$fullPath])) {
            $class = $routes[$fullPath]['class'];
            if (is_a($class, DevCommand::class, true)) {
                // Tell the request we've matched the full URL
                $request->shift($request->remaining());
            }
        }

        // The full path doesn't directly match any registered command or controller.
        // Look for a controller that can handle the request. We reject commands at this stage.
        // The full path will be for an action on the controller and may include nested actions,
        // so we need to check all urlsegment sections within the request URL.
        if (!$class) {
            $parts = explode('/', $fullPath);
            array_pop($parts);
            while (count($parts) > 0) {
                $newPath = implode('/', $parts);
                // Don't check dev itself - that's the controller we're currently in.
                if ($newPath === 'dev') {
                    break;
                }
                // Check for a controller that matches this partial path.
                $class = $routes[$newPath]['class'] ?? null;
                if ($class !== null && is_a($class, RequestHandler::class, true)) {
                    break;
                }
                array_pop($parts);
            }
        }

        if (!$class) {
            $msg = 'Error: no controller registered in ' . static::class . ' for: ' . $request->param('Action');
            $this->httpError(404, $msg);
        }

        // Hand execution to the controller
        if (is_a($class, RequestHandler::class, true)) {
            return $class::create();
        }

        /** @var DevCommand $command */
        $command = $class::create();
        $input = HttpRequestInput::create($request, $command->getOptions());
        // DO NOT use a buffer here to capture the output - we explicitly want the output to be streamed
        // to the client as its available, so that if there's an error the client gets all of the output
        // available until the error occurs.
        $output = PolyOutput::create(PolyOutput::FORMAT_HTML, $input->getVerbosity(), true);
        $renderer = DebugView::create();

        // Output header etc
        $headerOutput = [
            $renderer->renderHeader(),
            $renderer->renderInfo(
                $command->getTitle(),
                Director::absoluteBaseURL()
            ),
            '<div class="options">',
        ];
        $output->writeForHtml($headerOutput);

        // Run command
        $command->run($input, $output);

        // Output footer etc
        $output->writeForHtml([
            '</div>',
            $renderer->renderFooter(),
        ]);

        // Return to whence we came (e.g. if we had been redirected to dev/build)
        if ($returnUrl) {
            return $this->redirect($returnUrl);
        }
    }

    /**
     * Get a map of all registered DevCommands.
     * The key is the route used for browser execution.
     */
    public function getCommands(): array
    {
        $commands = [];
        foreach (Config::inst()->get(static::class, 'commands') as $name => $class) {
            // Allow unsetting a command via YAML
            if ($class === null) {
                continue;
            }
            // Check that the class exists and is a DevCommand
            if (!ClassInfo::exists($class)) {
                throw new LogicException("Class '$class' doesn't exist");
            }
            if (!is_a($class, DevCommand::class, true)) {
                throw new LogicException("Class '$class' must be a subclass of " . DevCommand::class);
            }

            // Add to list of commands
            $commands['dev/' . $name] = $class;
        }
        return $commands;
    }

    /**
     * Get a map of routes that can be run via this controller in an HTTP request.
     * The key is the URI path, and the value is an associative array of information about the route.
     */
    public function getRegisteredRoutes(): array
    {
        $canViewAll = $this->canViewAll();
        $items = [];

        foreach ($this->getCommands() as $urlSegment => $commandClass) {
            // Note we've already checked if command classes exist and are DevCommand
            // Check command can run in current context
            if (!$canViewAll && !$commandClass::canRunInBrowser()) {
                continue;
            }

            $items[$urlSegment] = ['class' => $commandClass];
        }

        foreach (static::config()->get('controllers') as $urlSegment => $info) {
            // Allow unsetting a controller via YAML
            if ($info === null) {
                continue;
            }
            $controllerClass = $info['class'];
            // Check that the class exists and is a RequestHandler
            if (!ClassInfo::exists($controllerClass)) {
                throw new LogicException("Class '$controllerClass' doesn't exist");
            }
            if (!is_a($controllerClass, RequestHandler::class, true)) {
                throw new LogicException("Class '$controllerClass' must be a subclass of " . RequestHandler::class);
            }

            if (!$canViewAll) {
                // Check access to controller
                $controllerSingleton = Injector::inst()->get($controllerClass);
                if (!$controllerSingleton->hasMethod('canInit') || !$controllerSingleton->canInit()) {
                    continue;
                }
            }

            $items['dev/' . $urlSegment] = $info;
        }

        return $items;
    }

    /**
     * Get a map of links to be displayed in the /dev route.
     * The key is the URI path, and the value is an associative array of information about the route.
     */
    public function getLinks(): array
    {
        $links = $this->getRegisteredRoutes();
        foreach ($links as $i => $info) {
            // Allow a controller without a link, e.g. DevConfirmationController
            if ($info['skipLink'] ?? false) {
                unset($links[$i]);
            }
        }
        return $links;
    }

    public function errors()
    {
        $this->redirect("Debug_");
    }

    public function providePermissions(): array
    {
        return [
            'ALL_DEV_ADMIN' => [
                'name' => _t(__CLASS__ . '.ALL_DEV_ADMIN_DESCRIPTION', 'Can view and execute all /dev endpoints'),
                'help' => _t(__CLASS__ . '.ALL_DEV_ADMIN_HELP', 'Can view and execute all /dev endpoints'),
                'category' => static::permissionsCategory(),
                'sort' => 50
            ],
        ];
    }

    public static function permissionsCategory(): string
    {
        return  _t(__CLASS__ . '.PERMISSIONS_CATEGORY', 'Dev permissions');
    }

    protected function canViewAll(): bool
    {
        // If dev/build was requested, we must defer to DbBuild permission checks explicitly
        // because otherwise the permission checks may result in an error
        $url = rtrim($this->getRequest()->getURL(), '/');
        if ($url === 'dev/build') {
            return false;
        }
        // We allow access to this controller regardless of live-status or ADMIN permission only if on CLI.
        // Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
        return (
            Director::isDev()
            || (Director::is_cli() && static::config()->get('allow_all_cli'))
            // Its important that we don't run this check if dev/build was requested
            || Permission::check(['ADMIN', 'ALL_DEV_ADMIN'])
        );
    }
}
