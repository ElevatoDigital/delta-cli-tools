<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Host;
use DeltaCli\Log\DatabaseManager;
use DeltaCli\Log\SticklerLog as SticklerLogObject;

class SticklerLog implements DetectorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function detectLogOnHost(Host $host)
    {
        $host->getSshTunnel()->setUp();

        foreach ($this->databaseManager->getAll($host) as $database) {
            if ('postgres' === $database->getType() && in_array('delta_log', $database->getTableNames())) {
                return new SticklerLogObject($host, $database);
            }
        }

        return false;
    }
}
