<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function save(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findRecentActivity(int $limit = 50): array
    {
        return $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->andWhere('al.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByEntity(string $entity, ?int $entityId = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('al')
            ->leftJoin('al.user', 'u')
            ->addSelect('u')
            ->andWhere('al.entity = :entity')
            ->setParameter('entity', $entity)
            ->orderBy('al.createdAt', 'DESC');

        if ($entityId) {
            $qb->andWhere('al.entityId = :entityId')
                ->setParameter('entityId', $entityId);
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getActivitySummary(int $days = 30): array
    {
        $startDate = (new \DateTime())->modify("-$days days");

        return $this->createQueryBuilder('al')
            ->select(
                "DATE(al.createdAt) as activityDate",
                "COUNT(al.id) as activityCount",
                "SUM(CASE WHEN al.action = :create THEN 1 ELSE 0 END) as createCount",
                "SUM(CASE WHEN al.action = :update THEN 1 ELSE 0 END) as updateCount",
                "SUM(CASE WHEN al.action = :delete THEN 1 ELSE 0 END) as deleteCount"
            )
            ->andWhere('al.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('create', ActivityLog::ACTION_CREATE)
            ->setParameter('update', ActivityLog::ACTION_UPDATE)
            ->setParameter('delete', ActivityLog::ACTION_DELETE)
            ->groupBy('activityDate')
            ->orderBy('activityDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}