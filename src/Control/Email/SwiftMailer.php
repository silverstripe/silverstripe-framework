<?php

namespace SilverStripe\Control\Email;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use Swift_Mailer;
use Swift_Message;

/**
 * Mailer objects are responsible for actually sending emails.
 * The default Mailer class will use PHP's mail() function.
 */
class SwiftMailer implements Mailer
{

    use Configurable;
    use Injectable;

    /**
     * @var array
     * @config
     */
    private static $swift_plugins = array(
        SwiftPlugin::class,
    );

    /**
     * @var Swift_Mailer
     */
    private $swift;

    /**
     * @param Email $message
     * @return bool Whether the sending was "successful" or not
     */
    public function send($message)
    {
        $swiftMessage = $message->getSwiftMessage();
        $failedRecipients = array();
        $result = $this->sendSwift($swiftMessage, $failedRecipients);
        $message->setFailedRecipients($failedRecipients);

        return $result != 0;
    }

    /**
     * @param Swift_Message $message
     * @param array $failedRecipients
     * @return int
     */
    protected function sendSwift($message, &$failedRecipients = null)
    {
        return $this->getSwiftMailer()->send($message, $failedRecipients);
    }

    /**
     * @return Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }

    /**
     * @param Swift_Mailer $swift
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
}
