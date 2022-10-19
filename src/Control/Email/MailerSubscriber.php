<?php

namespace SilverStripe\Control\Email;

use InvalidArgumentException;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;

/**
 * This subscriber is registered in BaseKernel->bootEmail()
 *
 * See https://symfony.com/doc/current/mailer.html#mailer-events for further info
 */
class MailerSubscriber implements EventSubscriberInterface
{
    use Injectable;
    use Extensible;

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        $email = $event->getMessage();
        if (!($email instanceof Email)) {
            throw new InvalidArgumentException('Message is not a ' . Email::class);
        }
        $this->applyConfig($email);
        $this->updateUrls($email);
        $this->extend('updateOnMessage', $email, $event);
    }

    private function applyConfig(Email $email): void
    {
        $sendAllTo = Email::getSendAllEmailsTo();
        if (!empty($sendAllTo)) {
            $this->setTo($email, $sendAllTo);
        }

        $ccAllTo = Email::getCCAllEmailsTo();
        if (!empty($ccAllTo)) {
            $email->addCc(...$ccAllTo);
        }

        $bccAllTo = Email::getBCCAllEmailsTo();
        if (!empty($bccAllTo)) {
            $email->addBcc(...$bccAllTo);
        }

        $sendAllFrom = Email::getSendAllEmailsFrom();
        if (!empty($sendAllFrom)) {
            $this->setFrom($email, $sendAllFrom);
        }
    }

    private function setTo(Email $email, array $sendAllTo): void
    {
        $headers = $email->getHeaders();
        // store the old data as X-Original-* Headers for debugging
        if (!empty($email->getTo())) {
            $headers->addMailboxListHeader('X-Original-To', $email->getTo());
        }
        if (!empty($email->getCc())) {
            $headers->addMailboxListHeader('X-Original-Cc', $email->getCc());
        }
        if (!empty($email->getBcc())) {
            $headers->addMailboxListHeader('X-Original-Bcc', $email->getBcc());
        }
        // set default recipient and remove all other recipients
        $email->to(...$sendAllTo);
        $email->cc(...[]);
        $email->bcc(...[]);
    }

    private function setFrom(Email $email, array $sendAllFrom): void
    {
        $headers = $email->getHeaders();
        if (!empty($email->getFrom())) {
            $headers->addMailboxListHeader('X-Original-From', $email->getFrom());
        }
        $email->from(...$sendAllFrom);
    }

    private function updateUrls(Email $email): void
    {
        if ($email->getHtmlBody()) {
            $email->html(HTTP::absoluteURLs($email->getHtmlBody()));
        }
        if ($email->getTextBody()) {
            $email->text(HTTP::absoluteURLs($email->getTextBody()));
        }
    }
}
