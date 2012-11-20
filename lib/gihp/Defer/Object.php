<?php

namespace gihp\Defer;

/**
 * Defer the loading of an object
 * @internal
 */
class Object
{
    /**
     * The data to insert in the object
     * @var array
     */
    private $data;
    /**
     * Reflection of the object
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * Creates a new defer object
     * @param array  $data   The data to set the properties to, as an array
     * @param string $object The full class name of the class to load. The class must implement Deferrable
     */
    public function __construct($data, $object)
    {
        $this->data = $data;
        $this->reflection = new \ReflectionClass($object);
        if(!$this->reflection->implementsInterface(__NAMESPACE__.'\\Deferrable'))
            throw new \LogicException($object.' should implement Deferrable');
    }

    /**
     * Injects the data in the class
     * @param  object $object The object to inject the data in
     * @return object The instanciated class with its loaded properties
     */
    public function injectData($object = null)
    {
        if ($object !== null) {
            $reflection = new \ReflectionClass($object);
            if ($reflection->getParentClass()->name !== $this->reflection->name) {
                throw new \LogicException('You can only inject data in a class of the same type');
            }
            $class = $object;
        } else {
            $reflection = $this->reflection;
            $class = $this->reflection->newInstanceWithoutConstructor();
        }
        foreach ($this->data as $key=>$value) {
            $prop = $reflection->getProperty($key);
            $prop->setAccessible(true);
            // When the value given only is a reference, load it.
            if ($value instanceof Reference) {
                $value = $value->loadRef();
            } elseif (is_array($value)) {
                $value = self::injectDataInArray($value);
            }
            $prop->setValue($class, $value);
        }

        return $class;
    }

    /**
     * Recursively injects References in an array
     * @param  array $array The array to parse
     * @return array The array with references resolved
     */
    private static function injectDataInArray($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::injectDataInArray($value);
            } elseif ($value instanceof Reference) {
                $value = $value->loadRef();
            } else {
                continue;
            }
        }

        return $array;
    }

    /**
     * Hacks the deferred class to a subclass that invokes the data injection
     * @return object The instanciated class hack
     */
    private function hackClass()
    {
        $ns = $this->reflection->getNamespaceName();
        if($ns !==  '') $ns='\\'.$ns;
        $name = $this->reflection->getShortName();
        $dirname = __DIR__.'/HackedClasses/'.str_replace('\\', '/', $ns);
        $filename = $name.'.php';

        $modified = false;
        if (file_exists($dirname.'/'.$filename)) {
            $realclassmod = filemtime($this->reflection->getFileName());
            $hackclassmod = filemtime($dirname.'/'.$filename);
            if ($hackclassmod < $realclassmod) {
                $modified = true;
            }
        }

        if (!file_exists($dirname.'/'.$filename)||$modified) {

            $tpl_params = '%s$%s %s,';
            $tpl_params_call = '$%s,';
            $tpl_methods = 'function %s(%s) {
                $this->defer->injectData($this);

                return parent::%1$s(%s);
            }';

            $tpl_code = '<?php namespace gihp\\Defer\\HackedClasses%s;
    class %s extends %1$s\\%2$s {
        private $defer;
        %s
    }';

            $methods = $this->reflection->getMethods();

            $methods_code = '';
            foreach ($methods as $method) {
                if($method->isStatic()) continue;
                $parameter_code = '';
                $parameter_call_code = '';
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {

                    $default_val = '';
                    if ($parameter->isDefaultValueAvailable()) {
                        $default_val = '='.var_export($parameter->getDefaultValue(), true);
                    }
                    $ref = '';
                    if ($parameter->isPassedByReference()) {
                        $ref = '&';
                    }
                    $pname = $parameter->getName();
                    $parameter_code.=sprintf($tpl_params, $ref, $pname, $default_val);
                    $parameter_call_code .= sprintf($tpl_params_call, $pname);
                }
                $parameter_code = substr($parameter_code, 0, -1);
                $parameter_call_code = substr($parameter_call_code,0,-1);

                $mname = $method->getName();
                $methods_code.=sprintf($tpl_methods, $mname, $parameter_code, $parameter_call_code);

            }

            $code =sprintf($tpl_code, $ns, $name, $methods_code);
            if(!is_dir($dirname))
                mkdir($dirname,0755, true);

            file_put_contents($dirname.'/'.$filename, $code);
        }
        $hackreflection = new \ReflectionClass(__NAMESPACE__.'\\HackedClasses'.$ns.'\\'.$name);
        $hack = $hackreflection->newInstanceWithoutConstructor();

        $deferprop = $hackreflection->getProperty('defer');
        $deferprop->setAccessible(true);
        $deferprop->setValue($hack, $this);

        return $hack;
    }

    public function getClass()
    {
        return $this->hackClass();
    }

    public static function defer($data, $object)
    {
        $defer = new self($data, $object);

        return $defer->getClass();
    }
}
