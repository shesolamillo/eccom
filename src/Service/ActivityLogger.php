<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    public function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($this->security->getUser());
        $log->setAction($action);
        $log->setEntity($entity);
        $log->setEntityId($entityId);
        $log->setDescription($description);
        
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}