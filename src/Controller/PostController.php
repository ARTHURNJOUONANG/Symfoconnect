<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PostController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/post/nouveau', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $author */
        $author = $this->getUser();
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($author);
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post publié avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('post/new.html.twig', [
            'postForm' => $form,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/post/{id}/like', name: 'app_post_like_toggle', methods: ['POST'])]
    public function toggleLike(Post $post, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('like_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($post->isLikedBy($user)) {
            $post->unlikeBy($user);
        } else {
            $post->likeBy($user);
        }

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_feed'));
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Post $post, EntityManagerInterface $entityManager, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('POST_DELETE', $post);

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post supprime.');

        return $this->redirectToRoute('app_feed');
    }
}
