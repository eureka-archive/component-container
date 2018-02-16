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
            throw new Exception\NotFoundException('No instance for given key! (key: ' . $id . ')');
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
     * @return $this
     * @throws \Psr\Container\NotFoundExceptionInterface
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
     * @return $this
     * @throws \Psr\Container\NotFoundExceptionInterface
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
     * @return $this
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function initFromArray(array $array)
    {
        foreach ($array as $name => $conf) {
            $this->attach($name, new $conf['class']());
        }

        return $this;
    }

    /**
     * Create new container & init it from an array.
     * @param  array $array
     * @return Container
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function makeFromArray(array $array, $repass = 5)
    {
        $retry     = [];
        $container = new self();

        for ($pass = 0; $pass < $repass; $pass++) {
            if ($pass === 0) {
                $services = $array;
            } else {
                $services = $retry;
                $retry    = [];
            }

            foreach ($services as $name => $service) {

                if (!isset($service['params']) || !is_array($service['params'])) {
                    $container->attach($name, new $service['class']());
                    continue;
                }

                //~ When have incomplete replace, service replacement will be retried.
                $params = $service['params'];
                if (!self::replace($container, $params)) {
                    $retry[$name] = $service;
                    continue;
                }

                $container->attach($name, new $service['class'](...$params));
            }
        }

        if (!empty($retry)) {
            throw new \Exception('Existing non-replaced services after ' . $repass . ' re-pass!');
        }

        return $container;
    }

    /**
     * @param  array|string|int|bool $params
     * @return bool
     */
    private static function replace(Container $container, &$params)
    {
        $isComplete = true;

        foreach ($params as $index => &$param) {
            //~ Handle array
            if (is_array($param)) {
                $isComplete = ($isComplete && self::replace($container, $param));
                continue;
            }

            if (!is_string($param) || 0 !== strpos($param, '@')) {
                continue;
            }

            //~ Handle services param
            $subservice = substr($param, 1);
            if ($container->has($subservice)) {
                $param = $container->get($subservice);
            } else {
                $isComplete = false;
            }
        }

        return $isComplete;
    }
}

