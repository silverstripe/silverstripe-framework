<?php
namespace SilverStripe\Forms\GridField\FormAction;

/**
 * Stores GridField action state on an attribute on the action and then analyses request parameters to load it back
 */
class AttributeStore extends AbstractRequestAwareStore
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
        // Just save the state in the attributes of the action
        return [
            'data-action-state' => json_encode($state),
        ];
    }

    /**
     * Load state for a given ID
     *
     * @param string $id
     * @return array
     */
    public function load($id)
    {
        // Check the request
        return (array) json_decode((string) $this->getRequest()->requestVar('ActionState'), true);
    }
}
