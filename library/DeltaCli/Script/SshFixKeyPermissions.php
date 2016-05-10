<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

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

    protected function addSteps()
    {
        $this->addStep($this->getProject()->fixSshKeyPermissions());
    }
}
