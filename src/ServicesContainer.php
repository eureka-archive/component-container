<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Container;

/**
 * Container class.
 *
 * @author Romain Cottard
 */
class ServicesContainer extends Container
{
    /** @var string $servicesClassName */
    private $servicesClassName = '';

    /**
     * ServicesContainer constructor.
     *
     * @param $serviceClassName
     */
    public function __construct($serviceClassName)
    {
        $this->servicesClassName = $serviceClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $serviceMethod = parent::get($id);

        $className  = "\\{$this->servicesClassName}";
        $methodName = "get${serviceMethod}";

        return $className::$methodName();
    }

    /**
     * @param  array $services
     * @param  string $cacheClass
     * @param  string $cacheFile
     * @param  string $cachePath
     * @param  string $environment
     * @return void
     * @throws \Eureka\Component\Container\Exception\ContainerException
     */
    public static function checkCache($services, $cacheClass, $cacheFile, $cachePath, $environment)
    {
        $file = $cachePath . DIRECTORY_SEPARATOR . $cacheFile;

        if (!file_exists($file) || $environment !== 'prod') {
            $generator = new Generator();
            $generator->build($services);
            $generator->dumpCache($cacheClass, $cacheFile, $cachePath);
        }

        require_once($file);
    }

    /**
     * @param  string $name
     * @return string
     */
    public static function formatServiceName($name)
    {
        return str_replace(' ', '', ucwords(str_replace(['.', '_', '-'], ' ', $name)));
    }
}

