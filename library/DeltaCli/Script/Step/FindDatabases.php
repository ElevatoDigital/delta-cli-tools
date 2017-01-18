<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Config;
use DeltaCli\Config\ConfigFactory;
use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;

class FindDatabases extends EnvironmentHostsStepAbstract
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var array
     */
    private $databases = [];

    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $configs = $this->configFactory->detectConfigsOnHost($host);

        if (false !== $configs) {
            /* @var $config Config */
            foreach ($configs as $config) {
                foreach ($config->getDatabases() as $database) {
                    $this->databases[] = $database;
                }
            }
        }
    }

    public function getName()
    {
        return 'find-databases';
    }

    /**
     * @return DatabaseInterface[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }
}
