<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return list<Message>
     */
    public function findConversation(User $user, User $other): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('s', 'r')
            ->innerJoin('m.sender', 's')
            ->innerJoin('m.recipient', 'r')
            ->andWhere('(m.sender = :me AND m.recipient = :other) OR (m.sender = :other AND m.recipient = :me)')
            ->setParameter('me', $user)
            ->setParameter('other', $other)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{user: User, lastMessage: Message, unreadCount: int}>
     */
    public function findConversationSummaries(User $user): array
    {
        $messages = $this->createQueryBuilder('m')
            ->addSelect('s', 'r')
            ->innerJoin('m.sender', 's')
            ->innerJoin('m.recipient', 'r')
            ->andWhere('m.sender = :me OR m.recipient = :me')
            ->setParameter('me', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $summaries = [];
        foreach ($messages as $message) {
            $other = $message->getSender()?->getId() === $user->getId() ? $message->getRecipient() : $message->getSender();
            if ($other === null) {
                continue;
            }

            $otherId = $other->getId();
            if ($otherId === null) {
                continue;
            }

            if (!isset($summaries[$otherId])) {
                $summaries[$otherId] = [
                    'user' => $other,
                    'lastMessage' => $message,
                    'unreadCount' => 0,
                ];
            }

            if ($message->getRecipient()?->getId() === $user->getId() && !$message->isRead()) {
                $summaries[$otherId]['unreadCount']++;
            }
        }

        return array_values($summaries);
    }

    public function markConversationAsRead(User $currentUser, User $other): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->where('m.sender = :other')
            ->andWhere('m.recipient = :me')
            ->andWhere('m.isRead = :unread')
            ->setParameter('read', true)
            ->setParameter('unread', false)
            ->setParameter('other', $other)
            ->setParameter('me', $currentUser)
            ->getQuery()
            ->execute();
    }
}
