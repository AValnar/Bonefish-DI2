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


class Container
{

    /**
     * @var array
     */
    protected $objects = array();

    /**
     * @var array
     */
    protected $alias = array();

    /**
     * Add an object into the container
     *
     * @param string $className
     * @param mixed $obj
     * @throws \Exception
     */
    public function add($className, $obj)
    {
        if ($className == '\Bonefish\DependencyInjection\Container') {
            throw new \Exception('You can not add the Container!');
        }

        if (isset($this->objects[$className])) {
            throw new \Exception('Duplicate entry for key ' . $className);
        }

        $this->objects[$className] = $obj;
    }

    /**
     * Set an alternate name for a class
     *
     * @param string $className
     * @param string $alias
     */
    public function alias($className, $alias)
    {
        $this->alias[$alias] = $className;
    }

    /**
     * Get a singleton and create if needed
     *
     * @param string $className
     * @return mixed
     */

    public function get($className)
    {
        $className = $this->getAliasForClass($className);

        if (ltrim($className,'\\') == 'Bonefish\DependencyInjection\Container') {
            return $this;
        }

        if (!isset($this->objects[$className])) {
            $this->objects[$className] = $this->create($className);
        }

        return $this->objects[$className];
    }

    /**
     * Create a object with dependency injection via annotation
     *
     * @param string $className
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */

    public function create($className, $parameters = array())
    {
        if (ltrim($className,'\\') == 'Bonefish\DependencyInjection\Container') {
            return $this;
        }

        $className = $this->getAliasForClass($className);
        return $this->finalizeObject($className, true, $parameters);
    }

    /**
     * Get alias for a class if one exists
     *
     * @param $className
     * @return string
     */
    protected function getAliasForClass($className)
    {
        if (isset($this->alias[$className])) {
            $className = $this->alias[$className];
        }

        return $className;
    }

    /**
     * Perform lazy dependency injection on object and init object
     *
     * @param mixed $obj
     * @param bool $init
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function finalizeObject($obj, $init = false, $parameters = array())
    {

        $r = new \Nette\Reflection\ClassType($obj);

        if ($r->isAbstract()) {
            throw new \Exception('Class ' . $obj . ' is Abstract!');
        }

        if ($init) {
            $obj = $r->newInstanceArgs($parameters);
        }

        foreach ($r->getProperties() as $property) {
            $this->processProperty($obj, $property, $r);
        }

        $this->callInitMethods($obj);

        return $obj;
    }

    protected function callInitMethods($obj)
    {
        if (method_exists($obj, '__init') && is_callable(array($obj, '__init'))) {
            $obj->__init();
        }
    }

    /**
     * @param object $obj
     * @param \Nette\Reflection\Property $property
     * @param \Nette\Reflection\ClassType $r
     * @throws \Exception
     */

    protected function processProperty($obj, \Nette\Reflection\Property $property, $r)
    {
        if ($property->hasAnnotation('inject')) {
            if (!$property->hasAnnotation('var')) {
                throw new \Exception('No @var tag found for property ' . $property->getName() . ' with @inject tag');
            }
            $class = $property->getAnnotation('var');
            $eager = false;
            if ($property->getAnnotation('inject') === 'eagerly') {
                $eager = true;
            }
            $this->performDependencyInjection($obj, $property, $class, $eager, $r);
        }
    }

    /**
     * Perform lazy Dependency Injection
     *
     * @param mixed $parent
     * @param \ReflectionProperty $property
     * @param string $className
     * @param bool $eager
     * @param \Nette\Reflection\ClassType $r
     */

    protected function performDependencyInjection($parent, \ReflectionProperty $property, $className, $eager, $r)
    {
        $className = \Nette\Reflection\AnnotationsParser::expandClassName($className,$r);

        if ($className == 'Bonefish\DependencyInjection\Container') {
            $value = $this;
        } else {
            if (!$eager) {
                $value = new Proxy($className, $property, $parent, $this);
            } else {
                $value = $this->get($className);
            }

        }
        $this->injectValueIntoProperty($parent, $property, $value);
    }

    /**
     * Set value of property
     *
     * @param mixed $parent
     * @param \ReflectionProperty $property
     * @param mixed $value
     */

    protected function injectValueIntoProperty($parent, \ReflectionProperty $property, $value)
    {
        $property->setAccessible(true);
        $property->setValue($parent, $value);
    }

    /**
     * Clear all services
     */
    public function tearDown()
    {
        $this->objects = array();
    }

    /**
     * Check if alias is set
     *
     * @param string $alias
     * @return bool
     */
    public function issetAlias($alias)
    {
        return isset($this->alias[$alias]);
    }

    /**
     * Return array of all created services
     *
     * @return array
     */
    public function getSingletons()
    {
        $list = array();

        foreach ($this->objects as $className => $object) {
            $list[] = $className;
        }

        return $list;
    }
} 