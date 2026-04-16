<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FeedController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/feed', name: 'app_feed', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $followedUsers = $user->getFollows()->toArray();

        $posts = $followedUsers === [] ? [] : $postRepository->findFeedPostsForUser($user);

        return $this->render('feed/index.html.twig', [
            'posts' => $posts,
            'hasFollows' => $followedUsers !== [],
        ]);
    }
}
