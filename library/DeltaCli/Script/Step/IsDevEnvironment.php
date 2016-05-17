<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;

class IsDevEnvironment extends StepAbstract implements DryRunInterface, EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'is-dev-environment';
        }
    }

    public function run()
    {
        return $this->runInternal();
    }

    public function dryRun()
    {
        return $this->runInternal();
    }

    public function runInternal()
    {
        if ($this->environment->isDevEnvironment()) {
            return new Result($this, Result::SUCCESS);
        } else {
            return new Result($this, Result::FAILURE);
        }
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }
}
