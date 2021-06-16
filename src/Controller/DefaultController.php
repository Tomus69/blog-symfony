<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping\OrderBy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    #[Route('/index', name: 'app_homepage')]
    public function index(ArticleRepository $articleRepository): Response
    {
    //    dd($articleRepository->findByLikes());

        return $this->render('default/index.html.twig', [
            'articles' => $articleRepository->findByLikes(),
        
        ]);
    }
}
