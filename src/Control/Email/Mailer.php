<?php

namespace SilverStripe\Control\Email;

interface Mailer
{

    /**
     * @param Email $email
     * @return bool
     */
    public function send($email);

    /**
     * @return array
     */
    public function getFailedRecipients();
}
