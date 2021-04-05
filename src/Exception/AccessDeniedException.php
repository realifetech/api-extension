<?php

namespace RL\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessDeniedException extends ApiException
{
    protected $code = 403;
}
