<?php


namespace Pimple\Exception;

use Psr\Container\NotFoundExceptionInterface;

class InvalidServiceIdentifierException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(\sprintf('Identifier "%s" does not contain an object definition.', $id));
    }
}
