<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exception\DeltaApiResourcesFailedToLoad;

class DisplayEnvironmentNotebookHtml extends DeltaApiAbstract implements EnvironmentAwareInterface
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
            $this->environment->displayNotebookHtml($this->output);
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
            return 'display-environment-notebook-html';
        }
    }

}