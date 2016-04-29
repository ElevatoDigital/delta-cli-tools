<?php

namespace DeltaCli\Script\Step;

use Closure;
use Exception;

class PhpCallable extends StepAbstract
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var string
     */
    private $name;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function run()
    {
        try {
            call_user_func($this->callable);
        } catch (Exception $e) {

        }
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else if (is_array($this->callable)) {
            return $this->generateNameForArrayCallable($this->callable);
        } else if (is_string($this->callable)) {
            return $this->callable;
        } else if ($this->callable instanceof Closure) {
            return 'PHP Closure';
        } else {
            return 'PHP Callback';
        }
    }

    private function generateNameForArrayCallable(array $callable)
    {
        if (is_string($callable[0])) {
            return sprintf('%s::%s()', $callable[0], $callable[1]);
        } else {
            return sprintf('%s->%s()', get_class($callable[0]), $callable[1]);
        }
    }
}
