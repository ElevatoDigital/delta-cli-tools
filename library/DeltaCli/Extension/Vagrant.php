<?php

namespace DeltaCli\Extension;

use DeltaCli\Extension\Vagrant\Script\CheckEnvironment;
use DeltaCli\Extension\Vagrant\Script\CreateVhost;
use DeltaCli\Project;

class Vagrant implements ExtensionInterface
{
    public function extend(Project $project)
    {
        $project->addScript(new CheckEnvironment($project));

        $project->createScript('vagrant:restart-services', 'Restart HTTP and database services.')
            ->addStep($project->getScript('vagrant:check-environment'))
            ->addStep('restart-apache', 'sudo /etc/init.d/httpd restart')
            ->addStep('restart-nginx', 'sudo /etc/init.d/nginx restart')
            ->addStep('restart-postgres', 'sudo /etc/init.d/postgresql-9.4 restart')
            ->addStep('restart-mysql', 'sudo /etc/init.d/mysqld restart');

        $project->addScript(new CreateVhost($project));
    }
}
