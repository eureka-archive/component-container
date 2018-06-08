<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Container;

/**
 * Generator class.
 *
 * @author Romain Cottard
 */
class Generator
{
    /** @var string[] $methods */
    private $methods = [];

    /** @var array $services */
    private $services = [];

    /**
     * @param  array $services
     * @param  int $passes
     * @return void
     * @throws Exception\ContainerException
     */
    public function build($services, $passes = 5)
    {
        $retry = [];

        for ($pass = 0; $pass < $passes; $pass++) {
            if ($pass > 0) {
                $services = $retry;
                $retry    = [];
            }

            foreach ($services as $name => $service) {

                if (!$this->replace($service['params'])) {
                    $retry[$name] = $service;
                    continue;
                }

                $this->methods[$name] = $this->getMethod($name, $service);
            }
        }

        if (!empty($retry)) {
            throw new Exception\ContainerException('Existing non-replaced services after ' . $passes . ' passes!');
        }
    }

    /**
     * @param  string $className
     * @param  string $filename
     * @param  string $path
     * @return $this
     * @throws \Eureka\Component\Container\Exception\ContainerException
     */
    public function dumpCache($className, $filename, $path = '')
    {
        if (!is_dir($path) && !mkdir($path, 0644, true)) {
            throw new \RuntimeException('Cache directory cannot be created! ');
        }

        $filePathname = $path . DIRECTORY_SEPARATOR . $filename;

        if (!file_put_contents($filePathname, $this->getClass($className, $this->methods))) {
            throw new Exception\ContainerException('Cannot write cache file.');
        }

        return $this;
    }

    /**
     * @param  string $name
     * @param  array $service
     * @return string
     */
    private function getMethod($name, $service)
    {
        $methodName = ServicesContainer::formatServiceName($name);
        $this->services[$name] = "self::get${methodName}()";

        if (!isset($service['shared']) || $service['shared'] === true) {
            return $this->getMethodSingleInstance($name, $methodName, $service);
        } else {
            return $this->getMethodNewInstance($methodName, $service);
        }
    }

    /**
     * @param  string $name
     * @param  string $methodName
     * @param  array $service
     * @return string
     */
    private function getMethodSingleInstance($name, $methodName, $service)
    {
        $params = $this->getParams($service);

        return "
    /**
     * @return ${service['class']}
     */
    public static function get${methodName}()
    {
        if (isset(self::\$services['$name'])) {
            return self::\$services['$name'];
        }
        
        self::\$services['$name'] = new ${service['class']}($params);

        return self::\$services['$name'];
    }";
    }

    /**
     * @param  string $methodName
     * @param  array $service
     * @return string
     */
    private function getMethodNewInstance($methodName, $service)
    {
        $params = $this->getParams($service);

        return "
    /**
     * @return ${service['class']}
     */
    public static function get${methodName}()
    {
        return new ${service['class']}($params);
    }";
    }

    /**
     * @param  array $service
     * @return string
     */
    private function getParams($service)
    {
        $tab    = str_repeat(' ', 4);
        $indent = $tab . $tab;

        $stringParams = '';
        if (!isset($service['params']) || !is_array($service['params'])) {
            return $stringParams;
        }

        $stringParams .= '[';
        foreach ($service['params'] as $param) {
            $formattedParam = $this->formatParam($param, $indent . $tab, $tab);
            $stringParams .= "\n${indent}${tab}" . $formattedParam . ',';
        }
        $stringParams .=  "\n${indent}]";
        return '...' . $stringParams;
    }

    /**
     * @param  string $className
     * @param  string[] $methods
     * @return string
     */
    private function getClass($className, $methods)
    {
        return "<?php
        
/*
 * Copyright (c) Eureka
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Container auto-generated class
 *
 * @author Eureka
 */
class ${className}
{
    /** @var array \$services */
    private static \$services = [];
    
    " . implode("\n", $methods) . "\n}";
    }

    /**
     * @param  array|string|int|bool $params
     * @return bool
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    private function replace(&$params)
    {
        $isComplete = true;

        if (empty($params)) {
            return $isComplete;
        }

        foreach ($params as $index => &$param) {
            //~ Handle array
            if (is_array($param)) {
                $isComplete = ($isComplete && $this->replace($param));
                continue;
            }

            if (!is_string($param) || 0 !== strpos($param, '@')) {
                continue;
            }

            //~ Handle services param
            $subService = substr($param, 1);
            if (isset($this->services[$subService])) {
                $param = $this->services[$subService];
            } else {
                $isComplete = false;
            }
        }

        return $isComplete;
    }

    /**
     * @param  string $param
     * @param  string $indent
     * @param  string $tab
     * @return string
     */
    private function formatParam($param, $indent, $tab)
    {
        $return = $param;

        if (is_string($param) && substr($param, 0, 6) !== 'self::') {
            $return = var_export($param, true);
        } elseif (!is_string($param) && !is_array($param)) {
            $return = var_export($param, true);
        } elseif (is_array($param)) {
            $return = '[';
            $counter = 0;
            foreach ($param as $index => $subParam) {
                $key = $index === $counter++ ? '' : var_export($index, true) . ' => ';
                $return .= "\n" . $indent . $tab . $key . $this->formatParam($subParam, $indent . $tab, $tab) . ',';
            }
            $return .= "\n{$indent}]";
        }

        return $return;
    }
}
