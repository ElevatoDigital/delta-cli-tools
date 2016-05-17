<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Script;

abstract class StepAbstract implements StepInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $environments = [];

    abstract public function run();

    /**
     * @param $environment
     * @return $this
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function preRun(Script $script)
    {

    }

    public function addStepToScript(Script $script, StepInterface $step)
    {

    }

    public function postRun(Script $script)
    {

    }

    public function appliesToEnvironment(Environment $selectedEnvironment)
    {
        if (!count($this->environments)) {
            return true;
        }

        /* @var $environment Environment */
        foreach ($this->environments as $environment) {
            $environmentName = (is_string($environment) ? $environment : $environment->getName());

            if ($environmentName === $selectedEnvironment->getName()) {
                return true;
            }
        }

        return false;
    }
}
