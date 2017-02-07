<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use DeltaCli\Log\DatabaseManager;

abstract class AbstractDatabaseLog implements DetectorInterface
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

        $log = false;

        $host->getSshTunnel()->setUp();

        foreach ($this->databaseManager->getAll($host) as $database) {
            if ($this->logIsPresent($database)) {
                $this->alreadyFoundOnPreviousHost = true;
                $log = $this->createLogObject($host, $database);
                break;
            }
        }

        $host->getSshTunnel()->tearDown();

        return $log;
    }

    abstract public function logIsPresent(DatabaseInterface $database);

    abstract public function createLogObject(Host $host, DatabaseInterface $database);
}