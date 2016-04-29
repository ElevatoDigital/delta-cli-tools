<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exception\UnrecognizedStepInput;

class StepFactory
{
    /**
     * @return StepInterface
     */
    public function factory(array $args)
    {
        if ($this->isNamedPhpCallable($args)) {
            return $this->createPhpCallable($args[1], $args[0]);
        } else if ($this->isUnnamedPhpCallable($args)) {
            return $this->createPhpCallable($args[0]);
        } else if ($this->isNamedShellCommand($args)) {
            return $this->createShellCommand($args[1], $args[0]);
        } else if ($this->isUnnamedShellCommand($args)) {
            return $this->createShellCommand($args[0]);
        } else if ($this->isStepObject($args)) {
            return $args[0];
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

    protected function isNamedShellCommand(array $args)
    {
        return isset($args[0]) && is_string($args[0]) && isset($args[1]) && is_string($args[1]);
    }

    protected function isUnnamedShellCommand(array $args)
    {
        return isset($args[0]) && is_string($args[0]);
    }

    protected function createShellCommand($command, $name = null)
    {
        $step = new ShellCommand($command);
        $step->setName($name);
        return $step;
    }

    protected function isStepObject(array $args)
    {
        return isset($args[0]) && $args[0] instanceof StepInterface;
    }
}
