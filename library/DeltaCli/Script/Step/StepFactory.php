<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exception\UnrecognizedStepInput;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StepFactory
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * @return StepInterface
     */
    public function factory(array $args)
    {
        if ($this->isNamedStepObject($args)) {
            /* @var $step StepInterface */
            $step = $args[1];
            $step->setName($args[0]);
            return $step;
        } else if ($this->isUnnamedStepObject($args)) {
            return $args[0];
        } else if ($this->isNamedPhpCallable($args)) {
            return $this->createPhpCallable($args[1], $args[0]);
        } else if ($this->isUnnamedPhpCallable($args)) {
            return $this->createPhpCallable($args[0]);
        } else if ($this->isNamedShellCommand($args)) {
            return $this->createShellCommand($args[1], $args[0]);
        } else if ($this->isUnnamedShellCommand($args)) {
            return $this->createShellCommand($args[0]);
        } else if ($this->isScriptObject($args)) {
            return new Script($args[0], $this->input, $this->output);
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

    protected function isScriptObject(array $args)
    {
        return isset($args[0]) && $args[0] instanceof ScriptObject;
    }

    protected function isNamedStepObject(array $args)
    {
        return isset($args[0]) && is_string($args[0]) && isset($args[1]) && $args[1] instanceof StepInterface;
    }

    protected function isUnnamedStepObject(array $args)
    {
        return isset($args[0]) && $args[0] instanceof StepInterface;
    }
}
