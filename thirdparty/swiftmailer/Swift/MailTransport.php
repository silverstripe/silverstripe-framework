<?php

/**
 * This file was copied in from swiftmailer/swiftmailer v5.4.12 after it was removed from switftmailer v6
 * It has been slightly modified to meet phpcs standards and initialise Swift_DependencyContainer
 */

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE file (MIT)
 * https://github.com/swiftmailer/swiftmailer/blob/181b89f18a90f8925ef805f950d47a7190e9b950/LICENSE
 */

/**
 * Sends Messages using the mail() function.
 *
 * @author Chris Corbyn
 *
 * at deprecated since 5.4.5 (to be removed in 6.0)
 */
// @codingStandardsIgnoreStart
// ignore missing namespace
class Swift_MailTransport extends Swift_Transport_MailTransport
// @codingStandardsIgnoreEnd
{
    /**
     * Create a new MailTransport, optionally specifying $extraParams.
     *
     * @param string $extraParams
     */
    public function __construct($extraParams = '-f%s')
    {
        call_user_func_array(
            [$this, 'Swift_Transport_MailTransport::__construct'],
            $this->getDependencies() ?? []
        );

        $this->setExtraParams($extraParams);
    }

    /**
     * Create a new MailTransport instance.
     *
     * @param string $extraParams To be passed to mail()
     *
     * @return self
     */
    public static function newInstance($extraParams = '-f%s')
    {
        return new self($extraParams);
    }

    /**
     * Add in deps for MailTransport which was removed as part of SwiftMailer v6
     * @see transport_deps.php
     *
     * @return array
     */
    private function getDependencies(): array
    {
        $deps = Swift_DependencyContainer::getInstance()->createDependenciesFor('transport.mail');
        if (empty($deps)) {
            Swift_DependencyContainer::getInstance()
                ->register('transport.mail')
                ->asNewInstanceOf('Swift_Transport_MailTransport')
                ->withDependencies(['transport.mailinvoker', 'transport.eventdispatcher'])
                ->register('transport.mailinvoker')
                ->asSharedInstanceOf('Swift_Transport_SimpleMailInvoker');
            $deps = Swift_DependencyContainer::getInstance()->createDependenciesFor('transport.mail');
        }
        return $deps;
    }
}
