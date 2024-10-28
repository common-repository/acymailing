<?php


namespace Pimple\Exception;

use Psr\Container\NotFoundExceptionInterface;

class UnknownIdentifierException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(\sprintf('Identifier "%s" is not defined.', $id));
    }
}
