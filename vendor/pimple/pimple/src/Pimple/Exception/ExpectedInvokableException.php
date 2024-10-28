<?php


namespace Pimple\Exception;

use Psr\Container\ContainerExceptionInterface;

class ExpectedInvokableException extends \InvalidArgumentException implements ContainerExceptionInterface
{
}
