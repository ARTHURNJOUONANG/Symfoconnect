<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-demo-user',
    description: 'Creates a demo user for local testing.',
)]
class CreateDemoUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email', 'demo@symfoconnect.local')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Username', 'demo')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password', 'demo1234')
            ->addOption('bio', null, InputOption::VALUE_OPTIONAL, 'Bio', 'Utilisateur de demonstration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getOption('email');
        $username = (string) $input->getOption('username');
        $password = (string) $input->getOption('password');
        $bio = $input->getOption('bio');

        if ($password === '') {
            $io->error('Le mot de passe ne peut pas etre vide.');

            return Command::FAILURE;
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if ($existing !== null) {
            $io->warning(sprintf('Un utilisateur avec l\'email "%s" existe deja.', $email));

            return Command::SUCCESS;
        }

        $user = new User();
        $user
            ->setEmail($email)
            ->setUsername($username)
            ->setBio(is_string($bio) ? $bio : null);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur "%s" cree avec succes.', $username));

        return Command::SUCCESS;
    }
}
