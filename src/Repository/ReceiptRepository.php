<?php

namespace App\Repository;

use App\Entity\Receipt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Receipt::class);
    }

    public function save(Receipt $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Receipt $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByOrder(int $orderId): ?Receipt
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.orderRef', 'o')
            ->addSelect('o')
            ->andWhere('o.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentReceipts(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.orderRef', 'o')
            ->addSelect('o')
            ->leftJoin('r.printedBy', 'u')
            ->addSelect('u')
            ->orderBy('r.issuedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getReceiptStatistics(): array
    {
        return $this->createQueryBuilder('r')
            ->select(
                'COUNT(r.id) as totalReceipts',
                'SUM(r.totalAmount) as totalAmount',
                'AVG(r.totalAmount) as averageAmount',
                'MIN(r.totalAmount) as minAmount',
                'MAX(r.totalAmount) as maxAmount'
            )
            ->getQuery()
            ->getSingleResult();
    }
}