<?php

/**
 * This file was copied in from swiftmailer/swiftmailer v5.4.12 after it was removed from switftmailer v6
 * It has been slightly modified to meet phpcs standards
 */

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE file (MIT)
 * https://github.com/swiftmailer/swiftmailer/blob/181b89f18a90f8925ef805f950d47a7190e9b950/LICENSE
 */

/**
 * This interface intercepts calls to the mail() function.
 *
 * @author     Chris Corbyn
 */
// @codingStandardsIgnoreStart
// ignore missing namespace
interface Swift_Transport_MailInvoker
// @codingStandardsIgnoreEnd
{
    /**
     * Send mail via the mail() function.
     *
     * This method takes the same arguments as PHP mail().
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $headers
     * @param string $extraParams
     *
     * @return bool
     */
    public function mail($to, $subject, $body, $headers = null, $extraParams = null);
}
