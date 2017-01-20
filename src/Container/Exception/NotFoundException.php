<?php

/**
 * Copyright (c) 2010-2017 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Container\Exception;

use Eureka\Component\Psr\Container\Exception\NotFoundExceptionInterface;

/**
 * Not Found Exception class
 *
 * @author Romain Cottard
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
