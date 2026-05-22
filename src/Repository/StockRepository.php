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
        ->andWhere('s.isLowStock = :low')
        ->setParameter('low', true)
        ->getQuery()
        ->getResult();

    }

    public function findOutOfStock(): array
    {
       return $this->createQueryBuilder('s')
        ->andWhere('s.quantity = 0')
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

    public function findFiltered(?string $search, ?string $status, string $sort = 'updated_desc'): array
{
    $qb = $this->createQueryBuilder('s')
        ->leftJoin('s.product', 'p')
        ->addSelect('p');

    if ($search) {
        $qb->andWhere('p.name LIKE :search OR p.sku LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($status === 'out_of_stock') {
        $qb->andWhere('s.quantity = 0');
    } elseif ($status === 'low_stock') {
        $qb->andWhere('s.isLowStock = true AND s.quantity > 0');
    } elseif ($status === 'in_stock') {
        $qb->andWhere('s.isLowStock = false AND s.quantity > 0');
    }

    match($sort) {
        'quantity_asc'  => $qb->orderBy('s.quantity', 'ASC'),
        'quantity_desc' => $qb->orderBy('s.quantity', 'DESC'),
        'name_asc'      => $qb->orderBy('p.name', 'ASC'),
        'name_desc'     => $qb->orderBy('p.name', 'DESC'),
        default         => $qb->orderBy('s.updatedAt', 'DESC'),
    };

    return $qb->getQuery()->getResult();
}

}