<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class RestartServices extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:restart-services',
            'Restart HTTP and database services.'
        );
    }

    protected function addSteps()
    {
        $this
            ->addStep($this->getProject()->getScript('vagrant:check-environment'))
            ->addStep('restart-apache', $this->getProject()->ssh('sudo /etc/init.d/httpd restart'))
            ->addStep('restart-nginx', $this->getProject()->ssh('sudo /etc/init.d/nginx restart'))
            ->addStep(
                'restart-postgres',
                $this->getProject()->ssh(
                    'sudo /etc/init.d/postgresql-9.4 restart || sudo /etc/init.d/postgresql-9.5 restart'
                )
            )
            ->addStep('restart-mysql', $this->getProject()->ssh('sudo /etc/init.d/mysqld restart'));
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');
    }
}
