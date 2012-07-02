<?php

/**
 * Exceptions that are raised if the container can't fulfil a dependency during creation.
 * @package plumbing
 */
class KrisDIContainerException extends Exception
{
}


/**
 * The main container class.
 * @package plumbing
 */
class KrisDIContainer
{
    /**
     * @var array
     */
    protected $factory = array();

    /**
     * @var array
     */
    protected $container = array();

    protected $implementations = array();

    /**
     * @param null|array $factory
     */
    function __construct($factory = null)
    {
        if (is_array($factory))
        {
            foreach ($factory as $className => $callback)
            {
                $this->registerFactory($className, $callback);
            }
        }

    }

    /**
     * @param $className
     * @param $callback
     */
    public function registerFactory($className, $callback)
    {
        $this->factory[strtolower($className)] = $callback;
    }


    /**
     * Gets a shared instance of a class.
     * @param $className
     * @return null
     */
    public function get($className)
    {
        $name = strtolower($className);
        if (!isset($this->container[$name]))
        {
            $this->container[$name] = $this->create($className);
        }
        return $this->container[$name];
    }

    /**
     * Creates a new (transient) instance of a class.
     * @param string $className
     * @return object
     */
    public function create($className)
    {
        $className = strtolower($className);
        if (isset($this->factory[$className]))
        {
            return call_user_func($this->factory[$className], $this);
        }
        return $this->createThroughReflection($className);
    }

    /**
     * Sets the concrete implementation class to use for an interface/abstract class dependency.
     * @param string $interface
     * @param string $use_class
     */
    public function registerImplementation($interface, $use_class)
    {
        $this->implementations[strtolower($interface)] = $use_class;
    }

    /**
     * Explicitly sets the implementation for a concrete class.
     * @param object $instance
     * @param null|string $className
     * @throws Exception
     */
    function set($instance, $className = null)
    {
        if (!is_object($instance))
        {
            throw new Exception("First argument must be an object");
        }
        $name = strtolower($className ? $className : get_class($instance));
        $this->container[$name] = $instance;
    }

    /**
     * @throws KrisDIContainerException
     * @param string $implementationName
     * @return object
     */
    protected function createThroughReflection($implementationName)
    {
        if (!isset($this->implementations[$implementationName]))
        {
            throw new KrisDIContainerException("No implementations for $implementationName");
        }

        $className = $this->implementations[$implementationName];

        if (!class_exists($className, true))
        {
            throw new KrisDIContainerException("Undefined class $className");
        }

        $className = strtolower($className);
        $class = new ReflectionClass($className);

        $dependencies = array();
        /** @var $ctor ReflectionMethod */
        $ctor = $class->getConstructor();
        if ($ctor)
        {
            /** @var $parameter ReflectionParameter */
            foreach ($ctor->getParameters() as $parameter)
            {
                if (!$parameter->isOptional())
                {
                    $paramClass = $parameter->getClass();
                    if (!$paramClass)
                    {
                        throw new KrisDIContainerException("Can't auto-assign parameter '" . $parameter->getName() . "' for '" . $class->getName() . "'");
                    }
                    $dependencies[] = $this->get($paramClass->getName());
                }
            }
            return $class->newInstanceArgs($dependencies);
        }
        return $class->newInstance();
    }

    /**
     * @param array $factories
     */
    public function registerFactories($factories)
    {
        foreach ($factories as $factory => $callback)
        {
            $this->registerFactory($factory, $callback);
        }
    }

    /**
     * @param array $implementations
     */
    public function registerImplementations($implementations)
    {
        foreach ($implementations as $name => $implementation)
        {
            $this->registerImplementation($name, $implementation);
        }

    }
}
