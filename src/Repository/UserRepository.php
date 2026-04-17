<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFirstCreatedUser(): ?User
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function emailExistsInsensitive(string $email): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('LOWER(u.email) = :email')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function usernameExistsInsensitive(string $username): bool
    {
        return (bool) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('LOWER(u.username) = :username')
            ->setParameter('username', mb_strtolower(trim($username)))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<User>
     */
    public function findChatSuggestions(User $currentUser, int $limit = 8): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u != :current')
            ->setParameter('current', $currentUser)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
