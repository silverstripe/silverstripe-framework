<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;

class SessionMiddleware implements HTTPMiddleware
{

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            // Start session and execute
            $session = new Session();
            $request->setSession($session);
            $session->start();

            // Generate output
            $response = $delegate($request);

        // Save session data, even if there was an exception.
        // Note that save() will start/resume the session if required.
        } finally {
            $request->getSession()->save($request);
        }

        return $response;
    }
}
