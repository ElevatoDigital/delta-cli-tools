<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exception\UnrecognizedStepInput;

class StepFactory
{
    public function __construct()
    {

    }

    /**
     * @return StepInterface
     */
    public function factory(array $args)
    {
        if ($this->isNamedPhpCallable($args)) {
            return $this->createPhpCallable($args[1], $args[0]);
        } else if ($this->isUnnamedPhpCallable($args)) {
            return $this->createPhpCallable($args[0]);
        } else {
            throw new UnrecognizedStepInput();
        }
    }

    protected function isNamedPhpCallable(array $args)
    {
        return isset($args[0]) && is_string($args[0]) && isset($args[1]) && is_callable($args[1]);
    }

    protected function isUnnamedPhpCallable(array $args)
    {
        return isset($args[0]) && is_callable($args[0]);
    }

    protected function createPhpCallable(callable $callable, $name = null)
    {
        $step = new PhpCallable($callable);
        $step->setName($name);
        return $step;
    }
}
