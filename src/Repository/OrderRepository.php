<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOrdersBetweenDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getOrderStatistics(): array
    {
        return $this->createQueryBuilder('o')
            ->select(
                'COUNT(o.id) as totalOrders',
                'SUM(o.totalAmount) as totalRevenue',
                'SUM(CASE WHEN o.status = :pending THEN 1 ELSE 0 END) as pendingCount',
                'SUM(CASE WHEN o.status = :processing THEN 1 ELSE 0 END) as processingCount',
                'SUM(CASE WHEN o.status = :completed THEN 1 ELSE 0 END) as completedCount',
                'SUM(CASE WHEN o.status = :cancelled THEN 1 ELSE 0 END) as cancelledCount'
            )
            ->setParameter('pending', Order::STATUS_PENDING)
            ->setParameter('processing', Order::STATUS_PROCESSING)
            ->setParameter('completed', Order::STATUS_COMPLETED)
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleResult();
    }

    public function getDailyRevenue(int $days = 30): array
    {
        $startDate = (new \DateTime())->modify("-$days days");

        return $this->createQueryBuilder('o')
            ->select(
                "DATE_FORMAT(o.createdAt, '%Y-%m-%d') as orderDate",
                "COUNT(o.id) as orderCount",
                "SUM(o.totalAmount) as dailyRevenue"
            )
            ->andWhere('o.createdAt >= :startDate')
            ->andWhere('o.status = :completed')
            ->setParameter('startDate', $startDate)
            ->setParameter('completed', Order::STATUS_COMPLETED)
            ->groupBy("DATE_FORMAT(o.createdAt, '%Y-%m-%d')")
            ->orderBy('orderDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyRevenue(int $months = 12): array
    {
        $startDate = (new \DateTime())->modify("-$months months");

        return $this->createQueryBuilder('o')
            ->select(
                "DATE_FORMAT(o.createdAt, '%Y-%m') as orderMonth",
                "COUNT(o.id) as orderCount",
                "SUM(o.totalAmount) as monthlyRevenue"
            )
            ->andWhere('o.createdAt >= :startDate')
            ->andWhere('o.status = :completed')
            ->setParameter('startDate', $startDate)
            ->setParameter('completed', Order::STATUS_COMPLETED)
            ->groupBy("DATE_FORMAT(o.createdAt, '%Y-%m')")
            ->orderBy('orderMonth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTodaysOrders(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->andWhere('o.createdAt >= :today')
            ->andWhere('o.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTodaysRevenue(): float
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as revenue')
            ->andWhere('o.createdAt >= :today')
            ->andWhere('o.createdAt < :tomorrow')
            ->andWhere('o.status = :completed')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('completed', Order::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}