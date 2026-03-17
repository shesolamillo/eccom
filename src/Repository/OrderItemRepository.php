<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function save(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByOrder(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->leftJoin('oi.orderRef', 'o')
            ->andWhere('o.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getProductSalesStatistics(int $productId): array
    {
        return $this->createQueryBuilder('oi')
            ->select(
                'SUM(oi.quantity) as totalSold',
                'SUM(oi.totalPrice) as totalRevenue',
                'COUNT(DISTINCT oi.orderRef) as orderCount'
            )
            ->leftJoin('oi.orderRef', 'o')
            ->andWhere('oi.product = :productId')
            ->andWhere('o.status = :completed')
            ->setParameter('productId', $productId)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getSingleResult();
    }
}