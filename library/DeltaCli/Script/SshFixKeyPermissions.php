<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputOption;

class SshFixKeyPermissions extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'ssh:fix-key-permissions',
            'Fix permissions for SSH keys.'
        );
    }

    protected function configure()
    {
        $this->addSetterOption(
            'environment',
            null,
            InputOption::VALUE_REQUIRED,
            'The environment whose keys you want to correct.'
        );

        parent::configure();
    }

    protected function addSteps()
    {
        $this->addStep($this->getProject()->fixSshKeyPermissions());
    }
}
