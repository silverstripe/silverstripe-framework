<?php

namespace SilverStripe\Control\Email;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Object;

/**
 * Mailer objects are responsible for actually sending emails.
 * The default Mailer class will use PHP's mail() function.
 */
class Mailer extends Object
{

    /**
     * @var array
     * @config
     */
    private static $swift_plugins = array(
        SwiftPlugin::class,
    );

    /**
     * @var string
     * @config
     */
    private static $swift_transport = 'Swift_MailTransport';

    /**
     * @var \Swift_Mailer
     */
    private $swift;

    /**
     * @var \Swift_Transport
     */
    private $transport;

    /**
     * @var array Any recipients that didn't get the email
     */
    private $failedRecipients = array();

    /**
     * @return mixed
     */
    public static function get_inst()
    {
        return Injector::inst()->get(static::class);
    }

    /**
     * @param Email $message
     * @return int
     */
    public static function send_message($message)
    {
        return static::get_inst()->send($message);
    }

    /**
     * @param Email $message
     * @return int
     */
    public function send($message)
    {
        $swiftMessage = $message->getSwiftMessage();
        return $this->sendSwift($swiftMessage);
    }

    /**
     * @param \Swift_Message $message
     * @return int
     */
    public function sendSwift($message)
    {
        return $this->getSwiftMailer()->send($message, $this->failedRecipients);
    }

    /**
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        if (!$this->transport) {
            $transportClass = $this->config()->swift_transport;
            $this->setTransport(new $transportClass());
        }
        return $this->transport;
    }

    /**
     * @param \Swift_Transport $transport
     * @return $this
     */
    public function setTransport($transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        if (!$this->swift) {
            $this->setSwiftMailer(new \Swift_Mailer($this->getTransport()));
        }

        return $this->swift;
    }

    /**
     * @param \Swift_Mailer $swift
     * @return $this
     */
    public function setSwiftMailer($swift)
    {
        // register any required plugins
        foreach ($this->config()->get('swift_plugins') as $plugin) {
            $swift->registerPlugin(Injector::inst()->create($plugin));
        }
        $this->swift = $swift;

        return $this;
    }

    /**
     * @return array
     */
    public function getFailedRecipients()
    {
        return $this->failedRecipients;
    }
}
