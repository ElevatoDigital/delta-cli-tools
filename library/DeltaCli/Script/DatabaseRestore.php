<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseRestore extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:restore',
            'Restore from a database dump.'
        );
    }

    protected function configure()
    {
        parent::configure();
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                function () {
                    throw new \Exception('Coming Soon');
                }
            );
    }
}
