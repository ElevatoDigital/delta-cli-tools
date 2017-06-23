<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class EnvironmentNotebook extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'env:notebook',
            'Generate HTML for a Teamwork notebook.'
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
            ->addStep($this->getProject()->displayEnvironmentNotebookHtml());
    }
}