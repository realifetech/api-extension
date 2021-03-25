<?php

namespace RL\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class AppConfigRepository
 */
class AppConfigRepository extends EntityRepository
{
    /**
     * @param int $app
     * @param string $key
     * @param int|string|boolean|null $default
     * @return string|int|null
     */
    public function getValueByAppAndKey(int $app, string $key, $default = null)
    {
        if ($appConfig = $this->getOneByAppAndKey($app, $key)) {
            return $appConfig->getValue();
        }

        return $default;
    }

    /**
     * @param int $app
     * @param string $key
     * @return object
     */
    public function getOneByAppAndKey(int $app, string $key): object
    {
        return $this->findOneBy(['app' => $app, 'key' => $key]);
    }
}
