<?php

namespace SilverStripe\Control\Middleware\URLSpecialsMiddleware;

use SilverStripe\Control\HTTPRequest;

/**
 * Implements switching user session into Test and Dev environment types
 */
trait SessionEnvTypeSwitcher
{
    /**
     * Checks whether the request has GET flags to control
     * environment type and amends the user session accordingly
     *
     * @param HTTPRequest $request
     *
     * @return bool true if changed the user session state, false otherwise
     */
    public function setSessionEnvType(HTTPRequest $request)
    {
        $session = $request->getSession();

        if (array_key_exists('isTest', $request->getVars())) {
            if (($isTest = $request->getVar('isTest')) !== null) {
                if ($isTest === $session->get('isTest')) {
                    return false;
                }
            }

            $session->clear('isDev');
            $session->set('isTest', $isTest);

            return true;
        } elseif (array_key_exists('isDev', $request->getVars())) {
            if (($isDev = $request->getVar('isDev')) !== null) {
                if ($isDev === $session->get('isDev')) {
                    return false;
                }
            }

            $session->clear('isTest');
            $session->set('isDev', $isDev);

            return true;
        }

        return false;
    }
}
