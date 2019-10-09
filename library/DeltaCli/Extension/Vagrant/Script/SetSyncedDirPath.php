<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class SetSyncedDirPath extends Script
{
    private $path;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:set-synced-dir-path',
            'Set the path to your synced /delta directory if it is not in root.'
        );
    }
}
