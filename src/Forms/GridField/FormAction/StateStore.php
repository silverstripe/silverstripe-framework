<?php
namespace SilverStripe\Forms\GridField\FormAction;

use SilverStripe\Control\HTTPRequest;

interface StateStore
{
    /**
     * @param HTTPRequest $request
     */
    public function __construct(HTTPRequest $request);

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
     * @return mixed
     */
    public function load($id);
}
