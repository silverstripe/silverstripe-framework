<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Creates a unique key for managing GridField states in user Session, for both storage and retrieval.
 * Only stores states and generates a session key if a state is requested to be stored
 * (i.e. the state is changed from the default).
 * If a session state key is present in the request, it will always be used instead of generating a new one.
 */
class SessionGridFieldStateManager implements GridFieldStateManagerInterface, GridFieldStateStoreInterface
{
    protected static $state_ids = [];

    protected function getStateID(GridField $gridField, $create = false): ?string
    {
        $requestVar = $this->getStateRequestVar();
        $sessionStateID = $gridField->getForm()->getRequestHandler()->getRequest()->requestVar($requestVar);
        if (!$sessionStateID) {
            $sessionStateID = Controller::curr()->getRequest()->requestVar($requestVar);
        }
        if ($sessionStateID) {
            return $sessionStateID;
        }
        $stateKey = $this->getStateKey($gridField);
        if (isset(self::$state_ids[$stateKey])) {
            $sessionStateID = self::$state_ids[$stateKey];
        } elseif ($create) {
            $sessionStateID = substr(md5(time()), 0, 8);
            // we don't want session state id to be strictly numeric, since this is used as a session key,
            // and session keys in php has to be usable as variable names
            if (is_numeric($sessionStateID)) {
                $sessionStateID .= 'a';
            }
            self::$state_ids[$stateKey] = $sessionStateID;
        }
        return $sessionStateID;
    }

    public function storeState(GridField $gridField, $value = null)
    {
        $sessionStateID = $this->getStateID($gridField, true);
        $sessionState = Controller::curr()->getRequest()->getSession()->get($sessionStateID);
        if (!$sessionState) {
            $sessionState = [];
        }
        $stateKey = $this->getStateKey($gridField);
        $sessionState[$stateKey] = $value ?? $gridField->getState(false)->Value();
        Controller::curr()->getRequest()->getSession()->set($sessionStateID, $sessionState);
    }

    public function getStateRequestVar(): string
    {
        return 'gridSessionState';
    }
    
    /**
     * @param GridField $gridField
     * @return string
     */
    public function getStateKey(GridField $gridField): string
    {
        $record = $gridField->getForm()->getRecord();
        return $gridField->getName() . '-' . ($record ? $record->ID : 0);
    }

    /**
     * @param GridField $gridField
     * @param string $url
     * @return string
     */
    public function addStateToURL(GridField $gridField, string $url): string
    {
        $sessionStateID = $this->getStateID($gridField);
        if ($sessionStateID) {
            return Controller::join_links($url, '?' . $this->getStateRequestVar() . '=' . $sessionStateID);
        }
        return $url;
    }

    /**
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return string|null
     */
    public function getStateFromRequest(GridField $gridField, HTTPRequest $request): ?string
    {
        $gridSessionStateID = $request->requestVar($this->getStateRequestVar());
        if ($gridSessionStateID) {
            $sessionState = $request->getSession()->get($gridSessionStateID);
            $stateKey = $this->getStateKey($gridField);
            if ($sessionState && isset($sessionState[$stateKey])) {
                return $sessionState[$stateKey];
            }
        }
        return null;
    }
}
