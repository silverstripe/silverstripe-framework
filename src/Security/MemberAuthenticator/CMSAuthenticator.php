<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Security\Authenticator as BaseAuthenticator;
use SilverStripe\Security\Member;

class CMSAuthenticator extends Authenticator
{

    public function supportedServices()
    {
        return BaseAuthenticator::CMS_LOGIN;
    }

    /**
     * @param array $data
     * @param $message
     * @param bool $success
     * @return Member
     */
    protected function authenticateMember($data, &$message, &$success, $member = null)
    {
        // Attempt to identify by temporary ID
        if (!empty($data['tempid'])) {
            // Find user by tempid, in case they are re-validating an existing session
            $member = Member::member_from_tempid($data['tempid']);
            if ($member) {
                $data['email'] = $member->Email;
            }
        }

        return parent::authenticateMember($data, $message, $success, $member);
    }

    public function getLoginHandler($link)
    {
        return CMSLoginHandler::create($link, $this);
    }
}
