<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function save(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLowStock(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')
            ->addSelect('p')
            ->andWhere('s.isLowStock = :lowStock')
            ->setParameter('lowStock', true)
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('s.quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOutOfStock(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')
            ->addSelect('p')
            ->andWhere('s.quantity = :zero')
            ->setParameter('zero', 0)
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findStockByProduct(int $productId): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')
            ->addSelect('p')
            ->andWhere('p.id = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getStockSummary(): array
    {
        return $this->createQueryBuilder('s')
            ->select(
                'SUM(s.quantity) as totalQuantity',
                'COUNT(s.id) as totalProducts',
                'SUM(CASE WHEN s.isLowStock = true THEN 1 ELSE 0 END) as lowStockCount',
                'SUM(CASE WHEN s.quantity = 0 THEN 1 ELSE 0 END) as outOfStockCount'
            )
            ->leftJoin('s.product', 'p')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->getQuery()
            ->getSingleResult();
    }
}