<?php
namespace SilverStripe\Forms\GridField\FormAction;

interface StateStore
{
    /**
     * Save the given state against the given ID returning an associative array to be added as attributes on the form
     * action
     *
     * @param string $id
     * @param array $state
     * @return array
     */
    public function save($id, array $state);

    /**
     * Load state for a given ID
     *
     * @param string $id
     * @return array
     */
    public function load($id);
}
