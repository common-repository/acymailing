<?php


namespace Pimple\Exception;

use Psr\Container\ContainerExceptionInterface;

class FrozenServiceException extends \RuntimeException implements ContainerExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(\sprintf('Cannot override frozen service "%s".', $id));
    }
}
