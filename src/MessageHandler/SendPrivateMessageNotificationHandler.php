<?php

namespace App\MessageHandler;

use App\Entity\Message;
use App\Message\SendPrivateMessageNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendPrivateMessageNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
    ) {
    }

    public function __invoke(SendPrivateMessageNotification $event): void
    {
        /** @var Message|null $message */
        $message = $this->entityManager->getRepository(Message::class)->find($event->getMessageId());
        if ($message === null) {
            return;
        }

        $recipient = $message->getRecipient();
        $sender = $message->getSender();
        if ($recipient === null || $sender === null || $recipient->getEmail() === null) {
            return;
        }

        $mail = (new Email())
            ->from('no-reply@symfoconnect.local')
            ->to((string) $recipient->getEmail())
            ->subject('Nouveau message prive sur SymfoConnect')
            ->text(sprintf(
                "%s vous a envoye un message:\n\n%s\n\nConnectez-vous a SymfoConnect pour repondre.",
                $sender->getUsername() ?? 'Un utilisateur',
                $message->getContent() ?? ''
            ));

        $this->mailer->send($mail);
    }
}
