<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
    #[Route('/profil/{username}', name: 'app_profile_show', methods: ['GET'])]
    public function show(string $username, UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $user = $userRepository->findOneByUsername($username);

        if ($user === null) {
            throw $this->createNotFoundException(sprintf('Utilisateur "%s" introuvable.', $username));
        }

        return $this->render('profile/show.html.twig', [
            'profileUser' => $user,
            'posts' => $postRepository->findByAuthor($user),
            'isFollowing' => $this->getUser() instanceof User ? $this->getUser()->isFollowing($user) : false,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil/{username}/follow', name: 'app_profile_follow', methods: ['POST'])]
    public function follow(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $target = $userRepository->findOneByUsername($username);
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$this->isCsrfTokenValid('follow_'.$username, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($target === null) {
            throw $this->createNotFoundException();
        }

        if ($target->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous suivre vous-meme.');

            return $this->redirectToRoute('app_profile_show', ['username' => $username]);
        }

        if (!$currentUser->isFollowing($target)) {
            $currentUser->follow($target);

            $notification = (new Notification())
                ->setRecipient($target)
                ->setType('follow')
                ->setContent(sprintf('%s a commence a vous suivre.', $currentUser->getUsername() ?? 'Un utilisateur'));
            $entityManager->persist($notification);

            $this->addFlash('success', 'Utilisateur suivi.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_profile_show', ['username' => $username]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/profil/{username}/unfollow', name: 'app_profile_unfollow', methods: ['POST'])]
    public function unfollow(string $username, UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $target = $userRepository->findOneByUsername($username);
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$this->isCsrfTokenValid('unfollow_'.$username, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($target === null) {
            throw $this->createNotFoundException();
        }

        if ($target->getId() !== $currentUser->getId() && $currentUser->isFollowing($target)) {
            $currentUser->unfollow($target);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur retire de vos follows.');
        }

        return $this->redirectToRoute('app_profile_show', ['username' => $username]);
    }
}
