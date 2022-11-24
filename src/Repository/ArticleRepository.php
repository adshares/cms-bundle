<?php

namespace Adshares\CmsBundle\Repository;

use Adshares\CmsBundle\Entity\Article;
use Adshares\CmsBundle\Entity\ArticleTag;
use Adshares\CmsBundle\Entity\ArticleType;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function add(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function createTypeQueryBuilder(array $types = [], array $tags = [], array $authors = []): QueryBuilder
    {
        $query = $this->createQueryBuilder('a');

        $types = array_filter($types);
        if (!empty($types)) {
            $query->andWhere('a.type IN (:types)')
                ->setParameter('types', $types);
        }

        $tags = array_filter($tags);
        if (!empty($tags)) {
            $list = [];
            /** @var ArticleTag $tag */
            foreach ($tags as $key => $tag) {
                $list[] = 'JSON_CONTAINS(a.tags, :tag' . $key . ', \'$\') = 1';
                $query->setParameter('tag' . $key, sprintf('"%s"', $tag->value));
            }
            $query->andWhere(implode(' OR ', $list));
        }

        $authors = array_filter($authors);
        if (!empty($authors)) {
            $query->andWhere('a.author IN (:authors)')
                ->setParameter('authors', $authors);
        }

        return $query;
    }

    /**
     * @return Article[]
     */
    public function findByType(ArticleType $type, ?ArticleTag $tag = null, ?int $limit = null): array
    {
        return $this->createTypeQueryBuilder([$type], [$tag])
            ->setMaxResults($limit)
            ->orderBy('a.no', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article[]
     */
    public function findRecentByType(ArticleType $type, ?ArticleTag $tag = null, ?int $limit = null): array
    {
        return $this->createTypeQueryBuilder([$type], [$tag])
            ->andWhere('a.startAt >= :date')
            ->setParameter('date', new DateTimeImmutable('-3 days'))
            ->setMaxResults($limit)
            ->addOrderBy('a.startAt', 'DESC')
            ->addOrderBy('a.no', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByQuery(
        string $query,
        array $types = [],
        array $tags = [],
        array $authors = [],
        ?int $limit = null,
        ?int $offset = null
    ): Paginator {
        $query = $this->createTypeQueryBuilder($types, $tags, $authors)
            ->andWhere('a.title LIKE :query OR a.content LIKE :query')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->addOrderBy('a.startAt', 'DESC')
            ->addOrderBy('a.no', 'ASC')
            ->getQuery();

        return new Paginator($query, false);
    }

    public function findRelated(
        Article $article,
        ?int $limit = null,
        ?int $offset = null
    ): Paginator {
        $query = $this->createTypeQueryBuilder([$article->getType()], $article->getTags())
            ->andWhere('a.id != :id')
            ->setParameter('id', $article->getId())
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->addOrderBy('a.startAt', 'DESC')
            ->addOrderBy('a.no', 'ASC')
            ->getQuery();

        return new Paginator($query, false);
    }
}
