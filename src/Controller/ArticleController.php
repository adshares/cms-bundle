<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends ViewController
{
    public function index(Request $request, ArticleRepository $repository): Response
    {
        $query = $request->get('query');

        $articles = $repository->findAll();

        return $this->render('cms/articles.html.twig', [
            'query' => $query,
            'articles' => $articles,
        ]);
    }
}