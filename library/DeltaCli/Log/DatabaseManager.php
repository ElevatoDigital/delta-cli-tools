<?php

namespace DeltaCli\Log;

use DeltaCli\Config\ConfigFactory;
use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use DeltaCli\Script\Step\FindDatabases;

class DatabaseManager
{
    /**
     * @var DatabaseInterface[]
     */
    private $databases;

    /**
     * return DatabaseInterface[]
     */
    public function getAll(Host $host)
    {
        if (null === $this->databases) {
            $findDbsSteps = new FindDatabases(new ConfigFactory());
            $findDbsSteps->setSelectedEnvironment($host->getEnvironment());
            $findDbsSteps->run();

            $databases = $findDbsSteps->getDatabases();

            $tunnel = $host->createSshTunnel();
            $tunnel->setUp();

            foreach ($databases as $database) {
                $database->setSshTunnel($tunnel);
            }

            $this->databases = $databases;
        }

        return $this->databases;
    }
}
