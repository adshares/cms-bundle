<?php

namespace Adshares\CmsBundle\Repository;

use Adshares\CmsBundle\Entity\Content;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Throwable;

/**
 * @extends ServiceEntityRepository<Content>
 *
 * @method Content|null find($id, $lockMode = null, $lockVersion = null)
 * @method Content|null findOneBy(array $criteria, array $orderBy = null)
 * @method Content[]    findAll()
 * @method Content[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    public function add(Content $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function addAll(array $entities): void
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($entities as $entity) {
                $this->add($entity);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->getConnection()->commit();
        } catch (Throwable $exception) {
            $this->getEntityManager()->getConnection()->rollBack();
            throw $exception;
        }
    }

    public function remove(Content $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Content[] Returns an array of Content objects
     */
    public function findByLocale(string $locale): array
    {
        return $this->findBy(['locale' => $locale]);
    }
}
