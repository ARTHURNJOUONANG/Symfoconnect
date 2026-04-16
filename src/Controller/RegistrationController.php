<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_feed');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $user->getEmail();
            $username = (string) $user->getUsername();
            $user->setUsername(trim($username));

            if ($userRepository->emailExistsInsensitive($email)) {
                $form->get('email')->addError(new FormError('Cet email est deja utilise.'));
            }

            if ($userRepository->usernameExistsInsensitive((string) $user->getUsername())) {
                $form->get('username')->addError(new FormError('Ce username est deja utilise.'));
            }

            if ($form->isValid()) {
                $plainPassword = (string) $form->get('plainPassword')->getData();
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->setRoles(['ROLE_USER']);

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                } catch (UniqueConstraintViolationException) {
                    $form->addError(new FormError('Un compte avec cet email ou ce username existe deja.'));

                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }

                $this->addFlash('success', 'Compte cree avec succes. Bienvenue sur SymfoConnect.');

                return $userAuthenticator->authenticateUser($user, $loginFormAuthenticator, $request);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
