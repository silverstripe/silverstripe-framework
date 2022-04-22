<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Security\Confirmation;
use SilverStripe\Security\Security;

/**
 * Checks whether user manual confirmation is required for HTTPRequest
 * depending on the rules given.
 *
 * How it works:
 *  - Gives the request to every single rule
 *  - If no confirmation items are found by the rules, then move on to the next middleware
 *  - initialize the Confirmation\Storage with all the confirmation items found
 *  - Check whether the storage has them confirmed already and if yes, move on to the next middleware
 *  - Otherwise redirect to the confirmation URL
 */
class ConfirmationMiddleware implements HTTPMiddleware
{
    /**
     * The confirmation storage identifier
     *
     * @var string
     */
    protected $confirmationId = 'middleware';

    /**
     * Confirmation form URL
     * WARNING: excluding SS_BASE_URL
     *
     * @var string
     */
    protected $confirmationFormUrl = '/dev/confirm';

    /**
     * The list of rules to check requests against
     *
     * @var ConfirmationMiddleware\Rule[]
     */
    protected $rules;

    /**
     * The list of bypasses
     *
     * @var ConfirmationMiddleware\Bypass[]
     */
    protected $bypasses = [];

    /**
     * Where user should be redirected when refusing
     * the action on the confirmation form
     *
     * @var string
     */
    private $declineUrl;

    /**
     * Init the middleware with the rules
     *
     * @param ConfirmationMiddleware\Rule[] $rules Rules to check requests against
     */
    public function __construct(...$rules)
    {
        $this->rules = $rules;
        $this->declineUrl = Director::baseURL();
    }

    /**
     * The URL of the confirmation form ("Security/confirm/middleware" by default)
     *
     * @param HTTPRequest $request Active request
     * @param string $confirmationStorageId ID of the confirmation storage to be used
     *
     * @return string URL of the confirmation form
     */
    protected function getConfirmationUrl(HTTPRequest $request, $confirmationStorageId)
    {
        $url = $this->confirmationFormUrl;

        if (substr($url ?? '', 0, 1) === '/') {
            // add BASE_URL explicitly if not absolute
            $url = Controller::join_links(Director::baseURL(), $url);
        }

        return Controller::join_links(
            $url,
            urlencode($confirmationStorageId ?? '')
        );
    }

    /**
     * Returns the URL where the user to be redirected
     * when declining the action (on the confirmation form)
     *
     * @param HTTPRequest $request Active request
     *
     * @return string URL
     */
    protected function generateDeclineUrlForRequest(HTTPRequest $request)
    {
        return $this->declineUrl;
    }

    /**
     * Override the default decline url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setDeclineUrl($url)
    {
        $this->declineUrl = $url;
        return $this;
    }

    /**
     * Check whether the rules can be bypassed
     * without user confirmation
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function canBypass(HTTPRequest $request)
    {
        foreach ($this->bypasses as $bypass) {
            if ($bypass->checkRequestForBypass($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the confirmation items from the request and return
     *
     * @param HTTPRequest $request
     *
     * @return Confirmation\Item[] list of confirmation items
     */
    public function getConfirmationItems(HTTPRequest $request)
    {
        $confirmationItems = [];

        foreach ($this->rules as $rule) {
            if ($item = $rule->getRequestConfirmationItem($request)) {
                $confirmationItems[] = $item;
            }
        }

        return $confirmationItems;
    }

    /**
     * Initialize the confirmation session storage
     * with the confirmation items and return an HTTPResponse
     * redirecting to the according confirmation form.
     *
     * @param HTTPRequest $request
     * @param Confirmation\Storage $storage
     * @param Confirmation\Item[] $confirmationItems
     *
     * @return HTTPResponse
     */
    protected function buildConfirmationRedirect(HTTPRequest $request, Confirmation\Storage $storage, array $confirmationItems)
    {
        $storage->cleanup();

        foreach ($confirmationItems as $item) {
            $storage->putItem($item);
        }

        $storage->setSuccessRequest($request);
        $storage->setFailureUrl($this->generateDeclineUrlForRequest($request));

        $result = new HTTPResponse();
        $result->redirect($this->getConfirmationUrl($request, $this->confirmationId));

        return $result;
    }

    /**
     * Process the confirmation items and either perform the confirmedEffect
     * and pass the request to the next middleware, or return a redirect to
     * the confirmation form
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @param Confirmation\Item[] $items
     *
     * @return HTTPResponse
     */
    protected function processItems(HTTPRequest $request, callable $delegate, $items)
    {
        $storage = Injector::inst()->createWithArgs(Confirmation\Storage::class, [$request->getSession(), $this->confirmationId, false]);

        if (!count($storage->getItems() ?? [])) {
            return $this->buildConfirmationRedirect($request, $storage, $items);
        }

        $confirmed = false;
        if ($storage->getHttpMethod() === 'POST') {
            $postVars = $request->postVars();
            $csrfToken = $storage->getCsrfToken();

            $confirmed = $storage->confirm($postVars) && isset($postVars[$csrfToken]);
        } else {
            $confirmed = $storage->check($items);
        }

        if (!$confirmed) {
            return $this->buildConfirmationRedirect($request, $storage, $items);
        }

        if ($response = $this->confirmedEffect($request)) {
            return $response;
        }

        $storage->cleanup();
        return $delegate($request);
    }

    /**
     * The middleware own effects that should be performed on confirmation
     *
     * This method is getting called before the confirmation storage cleanup
     * so that any responses returned here don't trigger a new confirmtation
     * for the same request traits
     *
     * @param HTTPRequest $request
     *
     * @return null|HTTPResponse
     */
    protected function confirmedEffect(HTTPRequest $request)
    {
        return null;
    }

    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->canBypass($request)) {
            if ($response = $this->confirmedEffect($request)) {
                return $response;
            } else {
                return $delegate($request);
            }
        }

        if (!$items = $this->getConfirmationItems($request)) {
            return $delegate($request);
        }

        return $this->processItems($request, $delegate, $items);
    }

    /**
     * Override the confirmation storage ID
     *
     * @param string $id
     *
     * @return $this
     */
    public function setConfirmationStorageId($id)
    {
        $this->confirmationId = $id;
        return $this;
    }

    /**
     * Override the confirmation form url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setConfirmationFormUrl($url)
    {
        $this->confirmationFormUrl = $url;
        return $this;
    }

    /**
     * Set the list of bypasses for the confirmation
     *
     * @param ConfirmationMiddleware\Bypass[] $bypasses
     *
     * @return $this
     */
    public function setBypasses($bypasses)
    {
        $this->bypasses = $bypasses;
        return $this;
    }
}
