<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseSearchAndReplace extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:search-and-replace',
            'Search for and replace a string throughout a database.'
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
