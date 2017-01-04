<?php

namespace DeltaCli\Extension;

use DeltaCli\Extension\Vagrant\Script\CheckEnvironment;
use DeltaCli\Extension\Vagrant\Script\CreateVhost;
use DeltaCli\Extension\Vagrant\Script\Mysql\Create as CreateMysql;
use DeltaCli\Extension\Vagrant\Script\Postgres\Create as CreatePostgres;
use DeltaCli\Extension\Vagrant\Script\RestartServices;
use DeltaCli\Project;

class Vagrant implements ExtensionInterface
{
    public function extend(Project $project)
    {
        $project->addScript(new CheckEnvironment($project));
        $project->addScript(new RestartServices($project));
        $project->addScript(new CreateVhost($project));
        $project->addScript(new CreatePostgres($project));
        $project->addScript(new CreateMysql($project));
    }
}
