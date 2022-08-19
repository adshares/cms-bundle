<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends ViewController
{
    const ITEMS_PER_PAGE = 10;

    public function index(Request $request, ArticleRepository $repository): Response
    {
        $query = $request->get('query');
        $page = max(1, (int)$request->get('page', 1));

        $articles = $repository->findByQuery($query, self::ITEMS_PER_PAGE, ($page - 1) * self::ITEMS_PER_PAGE);

        return $this->render('cms/articles.html.twig', [
            'query' => $query,
            'articles' => $articles,
            'currentPage' => $page,
            'pages' => ceil($articles->count() / self::ITEMS_PER_PAGE),
        ]);
    }
}