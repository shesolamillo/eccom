<?php

namespace App\Repository;

use App\Entity\ClothesCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClothesCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClothesCategory::class);
    }

    public function save(ClothesCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClothesCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('cc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithProductCount(): array
    {
        return $this->createQueryBuilder('cc')
            ->select('cc.id', 'cc.name', 'COUNT(p.id) as productCount')
            ->leftJoin('cc.products', 'p')
            ->andWhere('cc.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('cc.id')
            ->orderBy('cc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}