<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Container;

use Psr\Container\ContainerInterface;

/**
 * Container class.
 *
 * @author Romain Cottard
 */
class Container implements ContainerInterface
{
    /** @var string[] $collection List of services */
    private $collection = [];

    /**
     * {@inheritdoc}
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new Exception\NotFoundException('No service for given key! (key: ' . $id . ')');
        }

        return $this->collection[$id];
    }

    /**
     * {@inheritdoc}
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    public function has($id)
    {
        return isset($this->collection[$id]);
    }

    /**
     * Attach new instance of any class to this container.
     * If already exists, does not attach the new instance.
     *
     * @param  string $id Key name to retrieve the instance
     * @param  mixed
     * @return $this
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    public function attach($id, $service)
    {
        if ($this->has($id)) {
            return $this;
        }

        $this->collection[$id] = $service;

        return $this;
    }

    /**
     * Detach Instance from container.
     * Implicit destruct the instance.
     *
     * @param  string $id Key name to retrieve the instance
     * @return $this
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    public function detach($id)
    {
        if ($this->has($id)) {
            unset($this->collection[$id]);
        }

        return $this;
    }
}

