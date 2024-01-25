<?php

namespace App\Controller;

use App\Entity\Songs;
use App\Form\SongsType;
use App\Repository\SongsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/songs')]
class SongsController extends AbstractController
{
    #[Route('/', name: 'app_songs_index', methods: ['GET'])]
    public function index(SongsRepository $songsRepository): Response
    {
        flash()->addInfo("Retriving all the songs");
        return $this->render('songs/index.html.twig', [
            'songs' => $songsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_songs_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $song = new Songs();
        $form = $this->createForm(SongsType::class, $song);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form-> get('image_path')->getData();

            if($imageFile){
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            
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
            return $this->redirectToRoute('app_songs_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('songs/new.html.twig', [
            'song' => $song,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_songs_show', methods: ['GET'])]
    public function show(Songs $song): Response
    {
        return $this->render('songs/show.html.twig', [
            'song' => $song,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_songs_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Songs $song, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SongsType::class, $song);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            flash()->addWarning("Song edited successfully");

            return $this->redirectToRoute('app_songs_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('songs/edit.html.twig', [
            'song' => $song,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_songs_delete', methods: ['POST'])]
    public function delete(Request $request, Songs $song, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$song->getId(), $request->request->get('_token'))) {
            $entityManager->remove($song);
            $entityManager->flush();
        }
        flash()->addError("Song deleted successfully");

        return $this->redirectToRoute('app_songs_index', [], Response::HTTP_SEE_OTHER);
    }
}
