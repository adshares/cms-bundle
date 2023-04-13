<?php

namespace Adshares\CmsBundle\Controller;

use Adshares\CmsBundle\Cms\FileUploader;
use Adshares\CmsBundle\Entity\Article;
use Adshares\CmsBundle\Entity\ArticleTag;
use Adshares\CmsBundle\Entity\ArticleType as ArticleTypeEnum;
use Adshares\CmsBundle\Entity\User;
use Adshares\CmsBundle\Form\Type\ArticleType;
use Adshares\CmsBundle\Repository\ArticleRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class ArticleController extends ViewController
{
    const ITEMS_PER_PAGE = 10;

    public function index(Request $request, ArticleRepository $repository): Response
    {
        $query = $request->get('query', '');
        $type = ArticleTypeEnum::tryFrom($request->get('type', null));
        $tag = ArticleTag::tryFrom($request->get('tag', null));
        $page = max(1, (int)$request->get('page', 1));

        $articles = $repository->findByQuery(
            $query,
            [$type],
            [$tag],
            [],
            false,
            self::ITEMS_PER_PAGE,
            ($page - 1) * self::ITEMS_PER_PAGE
        );

        return $this->render('@AdsharesCms/article/articles.html.twig', [
            'searchQuery' => $query,
            'articles' => $articles,
            'currentPage' => $page,
            'pages' => max(1, ceil($articles->count() / self::ITEMS_PER_PAGE)),
            'types' => ArticleTypeEnum::cases(),
            'selectedType' => $type,
        ]);
    }

    public function show(int $id, Request $request, ArticleRepository $repository): Response
    {
        if (null === $article = $repository->find($id)) {
            throw new NotFoundHttpException(sprintf('Cannot find article "%s"', $id));
        }

        return $this->render('@AdsharesCms/article/article.html.twig', [
            'article' => $article,
        ]);
    }

    public function form(
        ?int $id,
        Request $request,
        ArticleRepository $repository,
        FileUploader $fileUploader,
        UserInterface $user
    ): Response {
        $editMode = false;
        if (null !== $id) {
            if (null === $article = $repository->find($id)) {
                throw new NotFoundHttpException(sprintf('Cannot find article "%s"', $id));
            }
            $editMode = true;
        } else {
            $article = new Article();
            $article->setStartAt(new DateTimeImmutable());
            if ($user instanceof User) {
                $article->setAuthor($user);
            }
        }

        $form = $this->createForm(ArticleType::class, $article, [
            'edit_mode' => $editMode
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->add($article, true);

            $file = $form['image']->getData();
            if (null !== $file) {
                $article->setImage($fileUploader->uploadArticle($file));
                $repository->add($article, true);
            }

            return $this->redirectToRoute('cms_article', ['id' => $article->getId(), 'name' => $article->getName()]);
        }

        return $this->renderForm('@AdsharesCms/article/article-editor.html.twig', [
            'form' => $form,
        ]);
    }

    public function delete(int $id, ArticleRepository $repository): Response
    {
        if (null === $article = $repository->find($id)) {
            throw new NotFoundHttpException(sprintf('Cannot find article "%s"', $id));
        }
        $repository->remove($article, true);
        return new Response('', 204);
    }
}
