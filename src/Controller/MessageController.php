<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Message\SendPrivateMessageNotification;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/messages')]
final class MessageController extends AbstractController
{
    #[Route('', name: 'app_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('message/index.html.twig', [
            'conversations' => $messageRepository->findConversationSummaries($user),
            'suggestedUsers' => $userRepository->findChatSuggestions($user),
        ]);
    }

    #[Route('/{username}', name: 'app_message_thread', methods: ['GET', 'POST'])]
    public function thread(
        string $username,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $other = $userRepository->findOneByUsername($username);

        if ($other === null) {
            throw $this->createNotFoundException(sprintf('Utilisateur "%s" introuvable.', $username));
        }
        if ($other->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas ouvrir une conversation avec vous-meme.');

            return $this->redirectToRoute('app_message_index');
        }

        $messageRepository->markConversationAsRead($currentUser, $other);

        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newMessage->setSender($currentUser);
            $newMessage->setRecipient($other);
            $newMessage->setIsRead(false);

            $entityManager->persist($newMessage);
            $entityManager->flush();

            if ($newMessage->getId() !== null) {
                $messageBus->dispatch(new SendPrivateMessageNotification($newMessage->getId()));
            }

            $this->addFlash('success', 'Message envoye.');

            return $this->redirectToRoute('app_message_thread', ['username' => $username]);
        }

        return $this->render('message/thread.html.twig', [
            'otherUser' => $other,
            'messages' => $messageRepository->findConversation($currentUser, $other),
            'messageForm' => $form,
        ]);
    }
}
