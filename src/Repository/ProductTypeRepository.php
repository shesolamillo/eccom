<?php

namespace App\Repository;

use App\Entity\ProductType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductType::class);
    }

    public function save(ProductType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('pt')
            ->andWhere('pt.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('pt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithProductCount(): array
    {
        return $this->createQueryBuilder('pt')
            ->select('pt.id', 'pt.name', 'COUNT(p.id) as productCount')
            ->leftJoin('pt.products', 'p')
            ->andWhere('pt.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('pt.id')
            ->orderBy('pt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}