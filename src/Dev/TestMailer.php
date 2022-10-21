<?php

namespace SilverStripe\Dev;

use Exception;
use InvalidArgumentException;
use SilverStripe\Control\Email\Email;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope as MessagerEnvelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;

class TestMailer implements MailerInterface
{
    private array $emailsSent = [];

    private TransportInterface $transport;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        TransportInterface $transport,
        EventDispatcherInterface $dispatcher
    ) {
        $this->transport = $transport;
        $this->dispatcher = $dispatcher;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if (!is_a($message, Email::class)) {
            throw new InvalidArgumentException('$message must be a ' . Email::class);
        }
        /** @var Email $email */
        $email = $message;
        $this->dispatchEvent($email, $envelope);
        $this->emailsSent[] = $this->createData($email);
    }

    protected function createData(Email $email): array
    {
        return [
            'Type' => $email->getHtmlBody() ? 'html' : 'plain',
            'To' => $this->convertAddressesToString($email->getTo()),
            'From' => $this->convertAddressesToString($email->getFrom()),
            'Subject' => $email->getSubject(),
            'Content' => $email->getHtmlBody() ?: $email->getTextBody(),
            'Headers' => $email->getHeaders(),
            'PlainContent' => $email->getTextBody(),
            'HtmlContent' => $email->getHtmlBody(),
            'AttachedFiles' => array_map(fn(DataPart $attachment) => [
                'contents' => $attachment->getBody(),
                'filename' => $attachment->getFilename(),
                'mimetype' => $attachment->getContentType()
            ], $email->getAttachments()),
        ];
    }

    /**
     * Search for an email that was sent.
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     */
    public function findEmail(
        string $to,
        ?string $from = null,
        ?string $subject = null,
        ?string $content = null
    ): ?array {
        $compare = [
            'To' => $to,
            'From' => $from,
            'Subject' => $subject,
            'Content' => $content,
        ];

        foreach ($this->emailsSent as $email) {
            $matched = true;

            // Loop all our Email fields
            foreach ($compare as $field => $value) {
                $emailValue = $email[$field];
                if ($value) {
                    if (in_array($field, ['To', 'From'])) {
                        $emailValue = $this->normaliseSpaces($emailValue);
                        $value = $this->normaliseSpaces($value);
                    }
                    if ($value[0] === '/') {
                        $matched = preg_match($value ?? '', $emailValue ?? '');
                    } else {
                        $matched = ($value === $emailValue);
                    }
                    if (!$matched) {
                        break;
                    }
                }
            }

            if ($matched) {
                return $email;
            }
        }
        return null;
    }

    /**
     * Clear the log of emails sent
     */
    public function clearEmails(): void
    {
        $this->emailsSent = [];
    }

    private function convertAddressesToString(array $addresses): string
    {
        return implode(',', array_map(fn(Address $address) => $address->getAddress(), $addresses));
    }

    private function dispatchEvent(Email $email, Envelope $envelope = null): void
    {
        $sender = $email->getSender()[0] ?? $email->getFrom()[0] ?? new Address('test.sender@example.com');
        $recipients = empty($email->getTo()) ? [new Address('test.recipient@example.com')] : $email->getTo();
        $envelope ??= new Envelope($sender, $recipients);
        $event = new MessageEvent($email, $envelope, $this->transport);
        $this->dispatcher->dispatch($event);
    }

    private function normaliseSpaces(string $value): string
    {
        return str_replace([', ', '; '], [',', ';'], $value ?? '');
    }
}
