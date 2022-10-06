<?php

namespace SilverStripe\Control\Email;

/**
 * @deprecated 4.12.0 Will be replaced with symfony/mailer
 */
interface Mailer
{

    /**
     * @param Email $email
     * @return bool
     */
    public function send($email);
}
