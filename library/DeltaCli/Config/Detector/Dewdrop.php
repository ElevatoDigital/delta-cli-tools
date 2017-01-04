<?php

namespace DeltaCli\Config\Detector;

use DeltaCli\Config\Config;
use DeltaCli\Config\Database;
use DeltaCli\Environment;

class Dewdrop implements DetectorInterface
{
    public function getMostLikelyRemoteFilePath()
    {
        return 'zend/dewdrop-config.php';
    }

    public function getName()
    {
        return 'dewdrop';
    }

    public function getPotentialFilePaths()
    {
        return [
            'dewdrop-config.php',
            'src/dewdrop-config.php'
        ];
    }

    public function createConfigFromFile(Environment $environment, $configFile)
    {
        $data   = require $configFile;
        $values = $data[$environment->getApplicationEnv()]['db'];
        $config = new Config();

        $config->addDatabase(
            new Database(
                $this->getDatabaseType($values['type']),
                $values['name'],
                $values['username'],
                $values['password'],
                $values['host']
            )
        );

        return $config;
    }

    private function getDatabaseType($type)
    {
        switch ($type) {
            case 'pgsql':
                return 'postgres';
            case 'mysql':
                return 'mysql';
            default:
                return 'unknown';
        }
    }
}
