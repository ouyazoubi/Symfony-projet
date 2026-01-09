<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\IdeaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(IdeaRepository $ideaRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $userIdeas = $ideaRepository->findBy(['authorId' => $user->getId()]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'ideas_count' => count($userIdeas),
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');

            if ($email !== $user->getEmail()) {
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre compte.');
                    return $this->redirectToRoute('app_profile_edit');
                }
            }

            $user->setName($name);
            $user->setEmail($email);

            if ($newPassword) {
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile_edit');
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $userRepository->save($user);

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        UserRepository $userRepository,
        IdeaRepository $ideaRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Vérification CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_account', $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $userIdeas = $ideaRepository->findBy(['authorId' => $user->getId()]);
        foreach ($userIdeas as $idea) {
            $ideaRepository->remove($idea);
        }

        $userRepository->remove($user);

        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('app_home');
    }
}
