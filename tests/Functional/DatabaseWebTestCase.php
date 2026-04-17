<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected function resetDatabase(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ($metadata === []) {
            return;
        }

        $tool = new SchemaTool($entityManager);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    protected function createUser(string $email = 'test@example.com', string $username = 'tester', string $password = 'test1234'): User
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail($email)
            ->setUsername($username)
            ->setRoles(['ROLE_USER']);
        $user->setPassword($hasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
