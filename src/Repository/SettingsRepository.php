<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    public function save(Settings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Settings $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByKey(string $key): ?Settings
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getValue(string $key, $default = null)
    {
        $setting = $this->findOneByKey($key);
        if (!$setting) {
            return $default;
        }
        return $setting->getTypedValue() ?? $default;
    }

    public function setValue(string $key, $value): void
    {
        $setting = $this->findOneByKey($key);
        
        if (!$setting) {
            $setting = new Settings();
            $setting->setSettingKey($key);
        }
        
        $setting->setTypedValue($value);
        $this->save($setting, true);
    }

    public function findAllPublic(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isPublic = :public')
            ->setParameter('public', true)
            ->orderBy('s.settingKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByGroup(string $prefix): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.settingKey LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('s.settingKey', 'ASC')
            ->getQuery()
            ->getResult();
    }
}