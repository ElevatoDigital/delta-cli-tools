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

    /**
     * @var bool
     */
    private $alreadyFoundOnPreviousHost = false;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function detectLogOnHost(Host $host)
    {
        // Stickler logs are in the DB so there is no use adding them for more than one host in an environment
        if ($this->alreadyFoundOnPreviousHost) {
            return false;
        }

        $host->getSshTunnel()->setUp();

        foreach ($this->databaseManager->getAll($host) as $database) {
            if ('postgres' === $database->getType() && in_array('delta_log', $database->getTableNames())) {
                $this->alreadyFoundOnPreviousHost = true;

                return new SticklerLogObject($host, $database);
            }
        }

        $host->getSshTunnel()->tearDown();

        return false;
    }
}
