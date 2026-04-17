<?php

namespace App\Command;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-conversations',
    description: 'Create sample private conversations for demo.',
)]
class SeedConversationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Messages per conversation', '6')
            ->addOption('create-users', null, InputOption::VALUE_NONE, 'Create demo users when needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = max(2, (int) $input->getOption('count'));
        $createUsers = (bool) $input->getOption('create-users');

        $users = $this->userRepository->findBy([], ['createdAt' => 'ASC']);
        if (count($users) < 2 && $createUsers) {
            $this->createDemoUsers();
            $users = $this->userRepository->findBy([], ['createdAt' => 'ASC']);
        }

        if (count($users) < 2) {
            $io->error('Il faut au moins 2 utilisateurs. Utilise --create-users ou cree des comptes.');

            return Command::FAILURE;
        }

        /** @var User $mainUser */
        $mainUser = $users[0];
        $others = array_slice($users, 1, 3);

        $samples = [
            'Salut, tu es dispo ?',
            'Oui, je suis la.',
            'Je teste la messagerie privee.',
            'Top, je vois bien la conversation.',
            'Tu as recu mon message ?',
            'Oui, nickel !',
            'On valide le chat.',
            'Parfait, a plus !',
        ];

        $created = 0;
        foreach ($others as $other) {
            if (!$other instanceof User) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $fromMain = $i % 2 === 0;
                $sender = $fromMain ? $mainUser : $other;
                $recipient = $fromMain ? $other : $mainUser;

                $message = (new Message())
                    ->setSender($sender)
                    ->setRecipient($recipient)
                    ->setContent($samples[$i % count($samples)])
                    ->setIsRead(!$fromMain)
                    ->setCreatedAt(new \DateTimeImmutable(sprintf('-%d minutes', ($count - $i) * 6)));

                $this->entityManager->persist($message);
                $created++;
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d messages de demonstration ont ete crees.', $created));
        $io->writeln('Ouvre /messages pour voir les conversations.');

        return Command::SUCCESS;
    }

    private function createDemoUsers(): void
    {
        $demoUsers = [
            ['email' => 'alice.chat@symfoconnect.local', 'username' => 'alice_chat'],
            ['email' => 'bob.chat@symfoconnect.local', 'username' => 'bob_chat'],
            ['email' => 'chris.chat@symfoconnect.local', 'username' => 'chris_chat'],
        ];

        foreach ($demoUsers as $demoUser) {
            $exists = $this->userRepository->findOneBy(['email' => $demoUser['email']]);
            if ($exists !== null) {
                continue;
            }

            $user = (new User())
                ->setEmail($demoUser['email'])
                ->setUsername($demoUser['username'])
                ->setPassword(password_hash('demo1234', PASSWORD_BCRYPT))
                ->setRoles(['ROLE_USER']);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }
}
