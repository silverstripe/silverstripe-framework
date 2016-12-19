<?php

namespace SilverStripe\Admin;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\ArrayData;
use InvalidArgumentException;
use Translatable;

/**
 * Special request handler for admin/batchaction
 */
class CMSBatchActionHandler extends RequestHandler
{

    /** @config */
    private static $batch_actions = array();

    private static $url_handlers = array(
        '$BatchAction/applicablepages' => 'handleApplicablePages',
        '$BatchAction/confirmation' => 'handleConfirmation',
        '$BatchAction' => 'handleBatchAction',
    );

    private static $allowed_actions = array(
        'handleBatchAction',
        'handleApplicablePages',
        'handleConfirmation',
    );

    /**
     * @var Controller
     */
    protected $parentController;

    /**
     * @var String
     */
    protected $urlSegment;

    /**
     * @var String $recordClass The classname that should be affected
     * by any batch changes. Needs to be set in the actual {@link CMSBatchAction}
     * implementations as well.
     */
    protected $recordClass = SiteTree::class;

    /**
     * @param Controller $parentController
     * @param string $urlSegment
     * @param string $recordClass
     */
    public function __construct($parentController, $urlSegment, $recordClass = null)
    {
        $this->parentController = $parentController;
        $this->urlSegment = $urlSegment;
        if ($recordClass) {
            $this->recordClass = $recordClass;
        }

        parent::__construct();
    }

    /**
     * Register a new batch action.  Each batch action needs to be represented by a subclass
     * of {@link CMSBatchAction}.
     *
     * @param string $urlSegment The URL Segment of the batch action - the URL used to process this
     * action will be admin/pages/batchactions/(urlSegment)
     * @param string $batchActionClass The name of the CMSBatchAction subclass to register
     * @param string $recordClass
     */
    public static function register($urlSegment, $batchActionClass, $recordClass = SiteTree::class)
    {
        if (!is_subclass_of($batchActionClass, CMSBatchAction::class)) {
            throw new InvalidArgumentException(
                "CMSBatchActionHandler::register() - Bad class '$batchActionClass'"
            );
        }

        Config::inst()->update(
            CMSBatchActionHandler::class,
            'batch_actions',
            array(
                $urlSegment => array(
                    'class' => $batchActionClass,
                    'recordClass' => $recordClass
                )
            )
        );
    }

    public function Link()
    {
        return Controller::join_links($this->parentController->Link(), $this->urlSegment);
    }

    /**
     * Invoke a batch action
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleBatchAction($request)
    {
        // This method can't be called without ajax.
        if (!$request->isAjax()) {
            return $this->parentController->redirectBack();
        }

        // Protect against CSRF on destructive action
        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->httpError(400);
        }

        // Find the action handler
        $action = $request->param('BatchAction');
        $actionHandler = $this->actionByName($action);

        // Sanitise ID list and query the database for apges
        $csvIDs = $request->requestVar('csvIDs');
        $ids = $this->cleanIDs($csvIDs);

        // Filter ids by those which are applicable to this action
        // Enforces front end filter in LeftAndMain.BatchActions.js:refreshSelected
        $ids = $actionHandler->applicablePages($ids);

        // Query ids and pass to action to process
        $pages = $this->getPages($ids);
        return $actionHandler->run($pages);
    }

    /**
     * Respond with the list of applicable pages for a given filter
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleApplicablePages($request)
    {
        // Find the action handler
        $action = $request->param('BatchAction');
        $actionHandler = $this->actionByName($action);

        // Sanitise ID list and query the database for apges
        $csvIDs = $request->requestVar('csvIDs');
        $ids = $this->cleanIDs($csvIDs);

        // Filter by applicable pages
        if ($ids) {
            $applicableIDs = $actionHandler->applicablePages($ids);
        } else {
            $applicableIDs = array();
        }

        $response = new HTTPResponse(json_encode($applicableIDs));
        $response->addHeader("Content-type", "application/json");
        return $response;
    }

    /**
     * Check if this action has a confirmation step
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleConfirmation($request)
    {
        // Find the action handler
        $action = $request->param('BatchAction');
        $actionHandler = $this->actionByName($action);

        // Sanitise ID list and query the database for apges
        $csvIDs = $request->requestVar('csvIDs');
        $ids = $this->cleanIDs($csvIDs);

        // Check dialog
        if ($actionHandler->hasMethod('confirmationDialog')) {
            $response = new HTTPResponse(json_encode($actionHandler->confirmationDialog($ids)));
        } else {
            $response = new HTTPResponse(json_encode(array('alert' => false)));
        }

        $response->addHeader("Content-type", "application/json");
        return $response;
    }

    /**
     * Get an action for a given name
     *
     * @param string $name Name of the action
     * @return CMSBatchAction An instance of the given batch action
     * @throws InvalidArgumentException if invalid action name is passed.
     */
    protected function actionByName($name)
    {
        // Find the action handler
        $actions = $this->batchActions();
        if (!isset($actions[$name]['class'])) {
            throw new InvalidArgumentException("Invalid batch action: {$name}");
        }
        return $this->buildAction($actions[$name]['class']);
    }

    /**
     * Return a SS_List of ArrayData objects containing the following pieces of info
     * about each batch action:
     *  - Link
     *  - Title
     *
     * @return ArrayList
     */
    public function batchActionList()
    {
        $actions = $this->batchActions();
        $actionList = new ArrayList();

        foreach ($actions as $urlSegment => $action) {
            $actionObj = $this->buildAction($action['class']);
            if ($actionObj->canView()) {
                $actionDef = new ArrayData(array(
                    "Link" => Controller::join_links($this->Link(), $urlSegment),
                    "Title" => $actionObj->getActionTitle(),
                ));
                $actionList->push($actionDef);
            }
        }

        return $actionList;
    }

    /**
     * Safely generate batch action object for a given classname
     *
     * @param string $class Class name to check
     * @return CMSBatchAction An instance of the given batch action
     * @throws InvalidArgumentException if invalid action class is passed.
     */
    protected function buildAction($class)
    {
        if (!is_subclass_of($class, CMSBatchAction::class)) {
            throw new InvalidArgumentException("{$class} is not a valid subclass of CMSBatchAction");
        }
        return CMSBatchAction::singleton($class);
    }

    /**
     * Sanitise ID list from string input
     *
     * @param string $csvIDs
     * @return array List of IDs as ints
     */
    protected function cleanIDs($csvIDs)
    {
        $ids = preg_split('/ *, */', trim($csvIDs));
        foreach ($ids as $k => $id) {
            $ids[$k] = (int)$id;
        }
        return array_filter($ids);
    }

    /**
     * Get all registered actions through the static defaults set by {@link register()}.
     * Filters for the currently set {@link recordClass}.
     *
     * @return array See {@link register()} for the returned format.
     */
    public function batchActions()
    {
        $actions = $this->config()->batch_actions;
        $recordClass = $this->recordClass;
        $actions = array_filter($actions, function ($action) use ($recordClass) {
            return $action['recordClass'] === $recordClass;
        });
        return $actions;
    }

    /**
     * Safely query and return all pages queried
     *
     * @param array $ids
     * @return SS_List
     */
    protected function getPages($ids)
    {
        // Check empty set
        if (empty($ids)) {
            return new ArrayList();
        }

        $recordClass = $this->recordClass;

        // Bypass translatable filter
        if (class_exists('Translatable') && $recordClass::has_extension('Translatable')) {
            Translatable::disable_locale_filter();
        }

        // Bypass versioned filter
        if ($recordClass::has_extension(Versioned::class)) {
            // Workaround for get_including_deleted not supporting byIDs filter very well
            // Ensure we select both stage / live records
            $pages = Versioned::get_including_deleted($recordClass, array(
                '"RecordID" IN ('.DB::placeholders($ids).')' => $ids
            ));
        } else {
            $pages = DataObject::get($recordClass)->byIDs($ids);
        }

        if (class_exists('Translatable') && $recordClass::has_extension('Translatable')) {
            Translatable::enable_locale_filter();
        }

        return $pages;
    }
}
