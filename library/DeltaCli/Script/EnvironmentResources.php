<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class EnvironmentResources extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'env:resources',
            'Display the resources for an environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        parent::configure();
    }

    protected function addSteps()
    {
        $this
            ->addStep($this->getProject()->displayEnvironmentResources());
    }
}