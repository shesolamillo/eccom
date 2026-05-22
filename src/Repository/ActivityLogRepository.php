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

        $logs = $this->createQueryBuilder('al')
            ->andWhere('al.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('al.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($logs as $log) {
            $day = $log->getCreatedAt()->format('Y-m-d');

            if (!isset($result[$day])) {
                $result[$day] = [
                    'activityDate' => $day,
                    'activityCount' => 0,
                    'createCount' => 0,
                    'updateCount' => 0,
                    'deleteCount' => 0,
                ];
            }

            $result[$day]['activityCount']++;
            switch ($log->getAction()) {
                case ActivityLog::ACTION_CREATE:
                    $result[$day]['createCount']++;
                    break;
                case ActivityLog::ACTION_UPDATE:
                    $result[$day]['updateCount']++;
                    break;
                case ActivityLog::ACTION_DELETE:
                    $result[$day]['deleteCount']++;
                    break;
            }
        }

        return array_values($result);
    }
}