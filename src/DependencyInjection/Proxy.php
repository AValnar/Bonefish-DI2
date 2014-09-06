<?php
/**
 * Copyright (C) 2014  Alexander Schmidt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @author     Alexander Schmidt <mail@story75.com>
 * @copyright  Copyright (c) 2014, Alexander Schmidt
 * @version    1.0
 * @date       2014-07-20
 * @package    Bonefish\DependencyInjection
 */

namespace Bonefish\DependencyInjection;


class Proxy
{

    /**
     * @var string
     */
    protected $className;

    /**
     * @var \Nette\Reflection\Property
     */
    protected $property;

    /**
     * @var mixed
     */
    protected $parent;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param string $className
     * @param \Nette\Reflection\Property $property
     * @param mixed $parent
     * @param Container $container
     */
    public function __construct($className, $property, $parent, $container)
    {
        $this->className = $className;
        $this->property = $property;
        $this->parent = $parent;
        $this->container = $container;
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments = array())
    {
        $dependency = $this->container->get($this->className);
        $this->property->setAccessible(true);
        $this->property->setValue($this->parent, $dependency);

        return call_user_func_array(array($this->parent->{$this->property->getName()}, $name), $arguments);
    }

    public function __sleep()
    {
        return array('className');
    }

} 