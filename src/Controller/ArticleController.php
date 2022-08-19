<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Entity\Article;
use Adshares\CmsBundle\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends ViewController
{
    const ITEMS_PER_PAGE = 10;

    public function index(Request $request, ArticleRepository $repository): Response
    {
        $query = $request->get('query', '');
        $page = max(1, (int)$request->get('page', 1));

        $articles = $repository->findByQuery($query, self::ITEMS_PER_PAGE, ($page - 1) * self::ITEMS_PER_PAGE);

        return $this->render('cms/articles.html.twig', [
            'query' => $query,
            'articles' => $articles,
            'currentPage' => $page,
            'pages' => ceil($articles->count() / self::ITEMS_PER_PAGE),
        ]);
    }

    public function show(string $id, Request $request, ArticleRepository $repository): Response
    {
        if (null === $article = $repository->find($id)) {
            throw new NotFoundHttpException(sprintf('Cannot find article "%s"', $id));
        }

        return $this->render('cms/article.html.twig', [
            'article' => $article,
        ]);
    }

    public function form(Request $request, ArticleRepository $repository): Response
    {
        $article = new Article();

        $form = $this->createForm(TaskType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();

            // ... perform some action, such as saving the task to the database

            return $this->redirectToRoute('task_success');
        }

        return $this->renderForm('cms/article.html.twig', [
            'form' => $form,
        ]);
    }
}