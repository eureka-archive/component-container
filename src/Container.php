<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Container;

use Eureka\Component\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Container class.
 *
 * @author Romain Cottard
 */
class Container implements ContainerInterface
{
    /** @var static $instance */
    private static $instance = null;

    /** @var \SplObjectStorage List of instances saved */
    private $instances = [];

    /**
     * Singleton getter.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Container constructor.
     */
    private function __construct()
    {
        $this->instances = [];
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException('No instance for given key! (key: ' . $id . ')');
        }

        return $this->instances[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return isset($this->instances[$id]);
    }

    /**
     * Attach new instance of any class to this container.
     * If already exists, does not attach the new instance.
     *
     * @param  string $id Key name to retrieve the instance
     * @param  object $instance Instance to attach
     * @return self
     * @throws \LogicException
     */
    public function attach($id, $instance)
    {
        if ($this->has($id)) {
            return $this;
        }

        $this->instances[$id] = $instance;

        return $this;
    }

    /**
     * Detach Instance from container.
     * Implicit destruct the instance.
     *
     * @param  string $id Key name to retrieve the instance
     * @return self
     */
    public function detach($id)
    {
        if ($this->has($id)) {
            unset($this->instances[$id]);
        }

        return $this;
    }

    /**
     * Initialize container from data array.
     *
     * @param  array $array
     * @return self
     */
    public function initFromArray(array $array)
    {
        foreach ($array as $name => $conf) {
            $this->attach($name, new $conf['class']());
        }

        return $this;
    }
}
