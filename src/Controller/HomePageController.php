<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();
        if ($token && $token->isAuthenticated() && $token->getUser()) {
            flash()->addError('Already logged in');
            return $this->redirectToRoute('app_song_index');
        }
        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
        ]);
    }
}