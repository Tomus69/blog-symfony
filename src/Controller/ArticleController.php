<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\User;
use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {

        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    #[Route('/my', name: 'my_article', methods: ['GET'])]
    public function my(): Response
    {
     
        return $this->render('article/my.html.twig', [
     
        ]);
    }

    #[Route('/new', name: 'article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $img = $form->get('img')->getData();

            if ($img) {
                $originalFilename = pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$img->guessExtension();

                try {
                    $img->move(
                        $this->getParameter('img_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }
                $article->setImg($newFilename);
            }

            $article->setUsers($this->getUser());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'article_show', methods: ['GET', 'POST'])]
    public function show(Article $article, Request $request): Response
    {
        $article->setUsers($this->getUser());
        $articleId = $article->getId();

        $comments = $this->getDoctrine()->getRepository(Comment::class)->findBy(['article' => $article],['created_at' => 'DESC']);
        $likes = $this->getDoctrine()->getRepository(Like::class)->findBy(['articles' => $article]);

        $isLiked = false;
        // dd($article->getLikes()->getValues());
        foreach($article->getLikes()->getValues() as $like){
            if($like->getUsers()->getId() === $this->getUser()->getId())
            {
                $isLiked = true;
                break;
            }
        }
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUser($this->getUser());
            $comment->setArticle($article);
            $comment->setCreatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('article_show', array('id' => $articleId));
        }

        return $this->render('article/show.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
            'comments' => $comments,
            'isLiked' => $isLiked,
            'likes' => $likes
        ]);
    }

    #[Route('/{id}/edit', name: 'article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $img = $form->get('img')->getData();

            if ($img) {
                $originalFilename = pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$img->guessExtension();

                try {
                    $img->move(
                        $this->getParameter('img_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {

                }
                $article->setImg($newFilename);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index');
    }

    #[Route('/like/{id}/{like}', name: 'article_like')]
    public function like(Article $article, Request $request): Response
    {

        // Vérifier dans la requete query quelle est la valeur de data/like
        // si c'est égal true tu add le like
        // sinno tu le remove

        $article->setUsers($this->getUser());
        $articleId = $article->getId();
        $em = $this->getDoctrine()->getManager();
       
        
        if($request->get('like') === 'true')
        {    
            $like = new Like();
            $like->setUsers($this->getUser()); 
            $article->addLike($like)->setNbLike($article->getNbLike() + 1);
            $em->persist($like);
            $em->flush();
        } else {
            $like = $em->getRepository(Like::class)->findOneBy([
                'articles' => $article,
                'users' => $this->getUser()
                ]);
            $article->removeLike($like)->setNbLike($article->getNbLike() - 1);
            $em->remove($like);
            $em->flush();
        }
 
        return $this->redirectToRoute('article_show', array('id' => $articleId));
    }

}
