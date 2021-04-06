<?php

namespace RL\Repository;

use Psr\Log\LoggerInterface;

class AnalyticsRepository
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(array $data)
    {
        $this->logger->info(@$data['tenant'], $data);
    }
}
