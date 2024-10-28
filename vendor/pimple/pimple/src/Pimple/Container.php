<?php


namespace Pimple;

use Pimple\Exception\ExpectedInvokableException;
use Pimple\Exception\FrozenServiceException;
use Pimple\Exception\InvalidServiceIdentifierException;
use Pimple\Exception\UnknownIdentifierException;

class Container implements \ArrayAccess
{
    private $values = [];
    private $factories;
    private $protected;
    private $frozen = [];
    private $raw = [];
    private $keys = [];

    public function __construct(array $values = [])
    {
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($id, $value)
    {
        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        $this->values[$id] = $value;
        $this->keys[$id] = true;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($id)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (
            isset($this->raw[$id])
            || !\is_object($this->values[$id])
            || isset($this->protected[$this->values[$id]])
            || !\method_exists($this->values[$id], '__invoke')
        ) {
            return $this->values[$id];
        }

        if (isset($this->factories[$this->values[$id]])) {
            return $this->values[$id]($this);
        }

        $raw = $this->values[$id];
        $val = $this->values[$id] = $raw($this);
        $this->raw[$id] = $raw;

        $this->frozen[$id] = true;

        return $val;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($id)
    {
        return isset($this->keys[$id]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($id)
    {
        if (isset($this->keys[$id])) {
            if (\is_object($this->values[$id])) {
                unset($this->factories[$this->values[$id]], $this->protected[$this->values[$id]]);
            }

            unset($this->values[$id], $this->frozen[$id], $this->raw[$id], $this->keys[$id]);
        }
    }

    public function factory($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Service definition is not a Closure or invokable object.');
        }

        $this->factories->attach($callable);

        return $callable;
    }

    public function protect($callable)
    {
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Callable is not a Closure or invokable object.');
        }

        $this->protected->attach($callable);

        return $callable;
    }

    public function raw($id)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return $this->values[$id];
    }

    public function extend($id, $callable)
    {
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        if (!\is_object($this->values[$id]) || !\method_exists($this->values[$id], '__invoke')) {
            throw new InvalidServiceIdentifierException($id);
        }

        if (isset($this->protected[$this->values[$id]])) {
            @\trigger_error(\sprintf('How Pimple behaves when extending protected closures will be fixed in Pimple 4. Are you sure "%s" should be protected?', $id), E_USER_DEPRECATED);
        }

        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        return $this[$id] = $extended;
    }

    public function keys()
    {
        return \array_keys($this->values);
    }

    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }
}
