<?php

namespace App\Controller;

use App\Entity\Song;
use App\Form\SongType;
use App\Repository\CategorieRepository;
use App\Repository\SongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/song')]
class SongController extends AbstractController
{

    #[Route('/', name: 'app_song_index', methods: ['GET'])]
    public function index(SongRepository $songRepository, CategorieRepository $categoryRepository, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            $this->addFlash('error', 'You need to be logged in to access this resource');
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer toutes les catégories
        $categories = $categoryRepository->findAll();

        // Initialiser la variable $category à null
        $category = null;

        // Vérifier si une catégorie a été sélectionnée via l'URL
        $categoryId = $request->query->get('category');
        if ($categoryId) {
            // Récupérer la catégorie sélectionnée
            $category = $categoryRepository->find($categoryId);

            // Filtrer les chansons par catégorie
            $songs = $songRepository->findBy(['categorie' => $category]);
        } else {
            // Récupérer toutes les chansons si aucune catégorie n'est sélectionnée
            $songs = $songRepository->findAll();
        }

        return $this->render('song/index.html.twig', [
            'songs' => $songs,
            'categories' => $categories,
            'category' => $category,
        ]);
    }

    #[Route('/new', name: 'app_song_new', methods: ['GET', 'POST'])]
    public function new (Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            $this->addFlash('error', 'You need to be logged in to access this resource');
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_USER');

        $song = new Song();
        $form = $this->createForm(SongType::class, $song);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_path')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $song->setImagePath($newFilename);
            }

            $entityManager->persist($song);
            $entityManager->flush();
            flash()->addSuccess("Song added successfully");
            return $this->redirectToRoute('app_song_index', [], Response::HTTP_SEE_OTHER);
        }
        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            $this->addFlash('error', 'You need to be logged in to access this resource');
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('song/new.html.twig', [
            'song' => $song,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_song_show', methods: ['GET'])]
    public function show(Song $song): Response
    {
        return $this->render('song/show.html.twig', [
            'song' => $song,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_song_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Song $song, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        $form = $this->createForm(SongType::class, $song);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_song_index', [], Response::HTTP_SEE_OTHER);
        }
        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            $this->addFlash('error', 'You need to be logged in to access this resource');
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('song/edit.html.twig', [
            'song' => $song,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_song_delete', methods: ['POST'])]
    public function delete(Request $request, Song $song, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        if ($this->isCsrfTokenValid('delete' . $song->getId(), $request->request->get('_token'))) {
            $entityManager->remove($song);
            $entityManager->flush();
        }
        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            $this->addFlash('error', 'You need to be logged in to access this resource');
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->redirectToRoute('app_song_index', [], Response::HTTP_SEE_OTHER);
    }
}
