<?php

namespace Adshares\CmsBundle\Repository;

use Adshares\CmsBundle\Entity\Content;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
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
    private LogEntryRepository $logEntryRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
        $manager = $registry->getManagerForClass(LogEntry::class);
        $this->logEntryRepository = new LogEntryRepository(
            $manager,
            $manager->getClassMetadata(LogEntry::class)
        );
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

    public function findOne(string $name, string $locale): Content|null
    {
        return $this->findOneBy(['name' => $name, 'locale' => $locale]);
    }

    public function findOneWithVersion(string $name, string $locale, int $version): Content|null
    {
        if (null !== ($content = $this->findOne($name, $locale))) {
            foreach ($this->logEntryRepository->getLogEntries($content) as $log) {
                if ($version === $log->getVersion()) {
                    $content->setValue($log->getData()['value'] ?? 'Error');
                }
            }
        }
        return $content;
    }
    /**
     * @return Content[]
     */
    public function findByLocale(string $locale): array
    {
        return $this->findBy(['locale' => $locale]);
    }

    /**
     * @return Content[]
     */
    public function findByNames(array $names, string $locale): array
    {
        return $this->findBy(['name' => $names, 'locale' => $locale]);
    }

    /**
     * @return LogEntry[][]
     */
    public function getHistory(array $names, string $locale): array
    {
        $history = [];
        if (!empty($names)) {
            foreach ($this->findByNames($names, $locale) as $content) {
                $changes = [];
                foreach ($this->logEntryRepository->getLogEntries($content) as $log) {
                    $changes[] = $log;
                }
                $history[$content->getName()] = $changes;
            }
        }
        return $history;
    }
}
