<?php

namespace RL\Repository;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use RL\Entity\ApiKey;

class ApiKeyRepository extends EntityRepository
{
    /**
     * @param string $token
     * @return ApiKey|null
     * @throws NonUniqueResultException
     */
    public function findByToken(string $token): ?ApiKey
    {
        return $this->createQueryBuilder('api_key')
            ->where('api_key.token = :token')
            ->setParameter('token', $token)
            ->andWhere('api_key.status = \'ACTIVE\'')
            ->andWhere('api_key.expireAt >= :now')
            ->setParameter('now', Carbon::now())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
