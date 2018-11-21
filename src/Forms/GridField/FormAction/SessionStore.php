<?php
namespace SilverStripe\Forms\GridField\FormAction;

use SilverStripe\Control\HTTPRequest;

/**
 * Stores GridField action state in the session in exactly the same way it has in the past
 */
class SessionStore implements StateStore
{
    /**
     * @var HTTPRequest
     */
    protected $request;

    /**
     * @param HTTPRequest $request
     */
    public function __construct(HTTPRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Save the given state against the given ID returning an associative array to be added as attributes on the form
     * action
     *
     * @param string $id
     * @param array $state
     * @return array
     */
    public function save($id, array $state)
    {
        $this->request->getSession()->set($id, $state);

        // This adapter does not require any additional attributes...
        return [];
    }

    /**
     * Load state for a given ID
     *
     * @param string $id
     * @return mixed
     */
    public function load($id)
    {
        return $this->request->getSession()->get($id);
    }
}
