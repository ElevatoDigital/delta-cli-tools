<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class BackupDbs extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:backup-dbs',
            'Backup all MySQL and Postgres databases to the /delta folder.'
        );
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'backup-mysql',
                $this->getProject()->ssh('mysqldump -A --user=root --password=delta > /delta/mysql.sql')
            )
            ->addStep(
                'backup-postgres',
                $this->getProject()->ssh('pg_dumpall -U postgres > /delta/postgres.sql')
            )
            ->addStep(
                'output-status',
                function () {
                    echo 'Postgres databases are in /delta/postgres.sql.  MySQL databases are in /delta/mysql.sql.';
                }
            );
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');
    }
}
