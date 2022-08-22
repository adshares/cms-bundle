<?php

namespace Adshares\CmsBundle\Repository;

use Adshares\CmsBundle\Entity\Article;
use Adshares\CmsBundle\Entity\ArticleTag;
use Adshares\CmsBundle\Entity\ArticleType;
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

    /**
     * @return Article[]
     */
    public function findByType(ArticleType $type, ?ArticleTag $tag = null): array
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.type = :type')
            ->setParameter('type', $type);

        if (null !== $tag) {
            $query->andWhere('JSON_CONTAINS(a.tags, :tag, \'$\') = 1')
                ->setParameter('tag', sprintf('"%s"', $tag->value));
        }
        return $query
            ->orderBy('a.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByQuery(string $query, ?int $limit = null, ?int $offset = null): Paginator
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.title LIKE :query')
            ->orWhere('a.content LIKE :query')
            ->setParameter('query', sprintf('%%%s%%', $query))
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query, false);
    }
}