<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Entity\User;
use App\Repository\IdeaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/idea')]
class IdeaController extends AbstractController
{
    #[Route('', name: 'app_idea_index', methods: ['GET'])]
    public function index(IdeaRepository $ideaRepository, Request $request): Response
    {
        $ideas = $ideaRepository->findAll();

        $category = $request->query->get('category');
        if ($category) {
            $ideas = array_filter($ideas, fn($idea) => $idea->getCategory() === $category);
        }

        $allIdeas = $ideaRepository->findAll();
        $categories = array_unique(array_map(fn($idea) => $idea->getCategory(), $allIdeas));
        sort($categories);

        return $this->render('idea/index.html.twig', [
            'ideas' => $ideas,
            'categories' => $categories,
            'current_category' => $category,
        ]);
    }

    #[Route('/new', name: 'app_idea_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, IdeaRepository $ideaRepository): Response
    {
        if ($request->isMethod('POST')) {
            /** @var User $user */
            $user = $this->getUser();

            $idea = new Idea();
            $idea->setTitle($request->request->get('title'));
            $idea->setDescription($request->request->get('description'));
            $idea->setCategory($request->request->get('category'));
            $idea->setAuthorId($user->getId());
            $idea->setAuthorName($user->getName());

            $ideaRepository->save($idea);

            $this->addFlash('success', 'Votre idée a été publiée avec succès !');
            return $this->redirectToRoute('app_idea_show', ['id' => $idea->getId()]);
        }

        return $this->render('idea/new.html.twig');
    }

    #[Route('/{id}', name: 'app_idea_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, IdeaRepository $ideaRepository): Response
    {
        $idea = $ideaRepository->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('Cette idée n\'existe pas.');
        }

        return $this->render('idea/show.html.twig', [
            'idea' => $idea,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_idea_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request, IdeaRepository $ideaRepository): Response
    {
        $idea = $ideaRepository->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('Cette idée n\'existe pas.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($idea->getAuthorId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres idées.');
            return $this->redirectToRoute('app_idea_show', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $idea->setTitle($request->request->get('title'));
            $idea->setDescription($request->request->get('description'));
            $idea->setCategory($request->request->get('category'));

            $ideaRepository->save($idea);

            $this->addFlash('success', 'Votre idée a été modifiée avec succès !');
            return $this->redirectToRoute('app_idea_show', ['id' => $idea->getId()]);
        }

        return $this->render('idea/edit.html.twig', [
            'idea' => $idea,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_idea_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, Request $request, IdeaRepository $ideaRepository): Response
    {
        $idea = $ideaRepository->find($id);

        if (!$idea) {
            throw $this->createNotFoundException('Cette idée n\'existe pas.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($idea->getAuthorId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres idées.');
            return $this->redirectToRoute('app_idea_show', ['id' => $id]);
        }


        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_idea_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_idea_show', ['id' => $id]);
        }

        $ideaRepository->remove($idea);

        $this->addFlash('success', 'Votre idée a été supprimée avec succès.');
        return $this->redirectToRoute('app_idea_index');
    }
}
