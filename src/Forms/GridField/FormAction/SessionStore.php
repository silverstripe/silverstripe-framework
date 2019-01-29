<?php
namespace SilverStripe\Forms\GridField\FormAction;

use SilverStripe\Control\HTTPRequest;

/**
 * Stores GridField action state in the session in exactly the same way it has in the past
 */
class SessionStore extends AbstractRequestAwareStore implements StateStore
{
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
        $this->getRequest()->getSession()->set($id, $state);

        // This adapter does not require any additional attributes...
        return [];
    }

    /**
     * Load state for a given ID
     *
     * @param string $id
     * @return array
     */
    public function load($id)
    {
        return (array) $this->getRequest()->getSession()->get($id);
    }
}
