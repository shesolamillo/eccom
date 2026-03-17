<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategoryAndType(?int $categoryId = null, ?int $typeId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true);

        if ($categoryId) {
            $qb->andWhere('p.clothesCategory = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($typeId) {
            $qb->andWhere('p.productType = :typeId')
                ->setParameter('typeId', $typeId);
        }

        return $qb->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithStock(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.stock', 's')
            ->addSelect('s')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLowStockProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.stock', 's')
            ->addSelect('s')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->andWhere('s.isLowStock = :lowStock')
            ->setParameter('lowStock', true)
            ->orderBy('s.quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTopSelling(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id', 'p.name', 'p.price', 'SUM(oi.quantity) as totalSold')
            ->leftJoin('p.orderItems', 'oi')
            ->leftJoin('oi.orderRef', 'o')
            ->andWhere('o.status = :completed')
            ->setParameter('completed', 'completed')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('available', true)
            ->groupBy('p.id')
            ->orderBy('totalSold', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}