<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exception\DeltaApiResourcesFailedToLoad;

class DisplayEnvironmentResources extends DeltaApiAbstract implements EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function run()
    {
        try {
            $this->environment->displayResources($this->output);
        } catch (DeltaApiResourcesFailedToLoad $exception) {
            return new Result($this, Result::FAILURE, [$exception->getMessage()]);
        }

        return new Result($this, Result::SUCCESS);
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'display-environment-resources';
        }
    }

}