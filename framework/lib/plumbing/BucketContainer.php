<?php
  /**
 * Bucket.
 * Basic di-container for php.
 *
 * https://github.com/troelskn/bucket
 */

/**
 * Exceptions that are raised if the container can't fulfil a dependency during creation.
 * @package plumbing
 */
class BucketCreationException extends Exception
{
}

/**
 * BucketScope
 * @package plumbing
 * Internally used by `bucket_Container` to hold instances.
 */
class BucketScope
{
    /**
     * @var BucketScope|null
     */
    protected $top;
    /**
     * @var array
     */
    protected $instances = array();
    /**
     * @var array
     */
    protected $implementations = array();

    /**
     * @param BucketScope|null $top
     */
    function __construct(BucketScope $top = null)
    {
        $this->top = $top;
    }

    /**
     * @param string $classname
     * @return bool
     */
    function has($classname)
    {
        return isset($this->instances[$classname]) || ($this->top && $this->top->has($classname));
    }

    /**
     * @param string $classname
     * @return null|object
     */
    function get($classname)
    {
        return isset($this->instances[$classname]) ? $this->instances[$classname] : ($this->top ? $this->top->get($classname) : null);
    }

    /**
     * @param string $classname
     * @param object $instance
     * @return object
     */
    function set($classname, $instance)
    {
        return $this->instances[$classname] = $instance;
    }

    /**
     * @param string $interface
     * @return object
     */
    function getImplementation($interface)
    {
        $index = strtolower($interface);
        return isset($this->implementations[$index]) ? $this->implementations[$index] : ($this->top ? $this->top->getImplementation($interface) : $interface);
    }

    /**
     * @param string $interface
     * @param object $use_class
     * @return void
     */
    public function setImplementation($interface, $use_class)
    {
        $this->implementations[$interface] = $use_class;
    }
}

/**
 * The main container class.
 * @package plumbing
 */
class BucketContainer
{
    /**
     * @var array|null|StdClass
     */
    protected $factory;
    /**
     * @var BucketScope
     */
    protected $scope;

    /**
     * @param null|array $factory
     * @param null|BucketScope $scope
     */
    function __construct($factory = null, $scope = null)
    {
        if (is_array($factory))
        {
            $this->factory = new StdClass();
            foreach ($factory as $classname => $callback)
            {
                $this->RegisterFactory($classname, $callback);
            }
        }
        else
        {
            $this->factory = $factory ? $factory : new StdClass();
        }
        $this->scope = new BucketScope($scope);
    }

    /**
     * @param $classname
     * @param $callback
     */
    public function RegisterFactory($classname, $callback)
    {
        $this->factory->{'new_' . strtolower($classname)} = $callback;
    }

    /**
     * Clones the container, with a new sub-scope.
     * @return BucketContainer
     */
    function makeChildContainer()
    {
        return new self($this->factory, $this->scope);
    }

    /**
     * Gets a shared instance of a class.
     * @param $classname
     * @return null
     */
    public function get($classname)
    {
        $classname = $this->scope->getImplementation($classname);
        $name = strtolower($classname);
        if (!$this->scope->has($name))
        {
            $this->scope->set($name, $this->create($classname));
        }
        return $this->scope->get($name);
    }

    /**
     * Creates a new (transient) instance of a class.
     * @param string $classname
     * @return object
     */
    public function create($classname)
    {
        $classname = $this->scope->getImplementation($classname);
        if (isset($this->factory->{'new_' . strtolower($classname)}))
        {
            return call_user_func($this->factory->{'new_' . strtolower($classname)}, $this);
        }
        if (is_callable(array($this->factory, 'new_' . $classname)))
        {
            return $this->factory->{'new_' . $classname}($this);
        }
        return $this->createThroughReflection($classname);
    }

    /**
     * Sets the concrete implementation class to use for an interface/abstract class dependency.
     * @param string $interface
     * @param object $use_class
     */
    public function registerImplementation($interface, $use_class)
    {
        $this->scope->setImplementation(strtolower($interface), $use_class);
    }

    /**
     * Explicitly sets the implementation for a concrete class.
     * @param string $instance
     * @param null|string $classname
     */
    function set($instance, $classname = null)
    {
        if (!is_object($instance))
        {
            throw new Exception("First argument must be an object");
        }
        $name = strtolower($classname ? $classname : get_class($instance));
        $this->scope->set($name, $instance);
    }

    /**
     * @throws BucketCreationException
     * @param string $classname
     * @return object
     */
    protected function createThroughReflection($classname)
    {
        if (!class_exists($classname))
        {
            throw new BucketCreationException("Undefined class $classname");
        }

        $classname = strtolower($classname);
        $klass = new ReflectionClass($classname);
        if ($klass->isInterface() || $klass->isAbstract())
        {
            // TODO: is this redundant?
            $candidates = array();
            foreach (get_declared_classes() as $klassname)
            {
                $candidate_klass = new ReflectionClass($klassname);
                if (!$candidate_klass->isInterface() && !$candidate_klass->isAbstract())
                {
                    if ($candidate_klass->implementsInterface($classname))
                    {
                        $candidates[] = $klassname;
                    }
                    elseif ($candidate_klass->isSubclassOf($klass))
                    {
                        $candidates[] = $klassname;
                    }
                }
            }
            throw new BucketCreationException("No implementation registered for '$classname'. Possible candidates are: " . implode(', ', $candidates));
        }
        $dependencies = array();
        /** @var $ctor ReflectionMethod */
        $ctor = $klass->getConstructor();
        if ($ctor)
        {
            /** @var $parameter ReflectionParameter */
            foreach ($ctor->getParameters() as $parameter)
            {
                if (!$parameter->isOptional())
                {
                    $param_klass = $parameter->getClass();
                    if (!$param_klass)
                    {
                        throw new BucketCreationException("Can't auto-assign parameter '" . $parameter->getName() . "' for '" . $klass->getName() . "'");
                    }
                    $dependencies[] = $this->get($param_klass->getName());
                }
            }
            return $klass->newInstanceArgs($dependencies);
        }
        return $klass->newInstance();
    }
}
