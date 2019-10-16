<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Cache;
use DeltaCli\Project;
use DeltaCli\Script;

class BackupDbs extends Script
{
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Project $project, Cache $cache)
    {
        $this->cache = $cache;

        parent::__construct(
            $project,
            'vagrant:backup-dbs',
            'Backup all MySQL and Postgres databases to the synced delta folder.'
        );
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'backup-mysql',
                $this->getProject()->ssh(
                    sprintf(
                        'mysqldump -A --user=root --password=delta > %s/mysql.sql',
                        $this->cache->fetch('synced-dir-path')
                    )
                )
            )
            ->addStep(
                'backup-postgres',
                $this->getProject()->ssh(
                    sprintf(
                        'pg_dumpall -U postgres > %s/postgres.sql',
                        $this->cache->fetch('synced-dir-path')
                    )
                )
            )
            ->addStep(
                'output-status',
                function () {
                    echo sprintf(
                        'Postgres databases are in %s/postgres.sql.  MySQL databases are in %s/mysql.sql.',
                        $this->cache->fetch('synced-dir-path'),
                        $this->cache->fetch('synced-dir-path')
                    );
                }
            );
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');
    }
}
