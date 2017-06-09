<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;

/**
 * An AuthenticationHandler is responsible for providing an identity (in the form of a Member object) for
 * a given HTTPRequest.
 *
 * It should return the authenticated Member if successful. If a Member cannot be found from the current
 * request it should *not* attempt to redirect the visitor to a log-in from or 3rd party handler, as that
 * is the responsibiltiy of other systems.
 */
interface AuthenticationHandler extends IdentityStore
{
    /**
     * Given the current request, authenticate the request for non-session authorization (outside the CMS).
     *
     * The Member returned from this method will be provided to the Manager for use in the OperationResolver context
     * in place of the current CMS member.
     *
     * Authenticators can be given a priority. In this case, the authenticator with the highest priority will be
     * returned first. If not provided, it will default to a low number.
     *
     * An example for configuring the BasicAuthAuthenticator:
     *
     * <code>
     * SilverStripe\Security\Security:
     *   authentication_handlers:
     *     - SilverStripe\Security\BasicAuthentionHandler
     * </code>
     *
     * @param  HTTPRequest $request The current HTTP request
     * @return Member|null          The authenticated Member, or null if this auth mechanism isn't used.
     * @throws ValidationException  If authentication data exists but does not match a member.
     */
    public function authenticateRequest(HTTPRequest $request);
}
