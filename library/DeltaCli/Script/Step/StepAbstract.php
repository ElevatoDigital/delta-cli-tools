<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

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
