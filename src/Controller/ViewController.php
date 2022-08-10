<?php

namespace Adshares\CmsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ViewController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = $this->twig->render($view, $parameters);
        if (null === $response) {
            $response = new Response();
        }
        $response->setContent($content);
        return $response;
    }
}