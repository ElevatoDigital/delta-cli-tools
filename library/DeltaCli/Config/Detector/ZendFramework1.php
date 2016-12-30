<?php

namespace DeltaCli\Config\Detector;

use DeltaCli\Config\Config;
use DeltaCli\Config\Database;
use DeltaCli\Environment;

class ZendFramework1
{
    public function getPotentialFilePaths()
    {
        return [
            'zend/application/configs/application.ini',
            'application/configs/application.ini',
            'src/application/configs/application.ini'
        ];
    }

    public function createConfigFromFile(Environment $environment, $configFile)
    {
        $data   = parse_ini_file($configFile, true);
        $values = $this->getValuesForEnvironment($environment->getApplicationEnv(), $data);
        $config = new Config();

        foreach ($this->findDatabasesInValues($values) as $database) {
            $config->addDatabase($database);
        }

        return $config;
    }

    protected function getValuesForEnvironment($environmentName, array $config)
    {
        $values = [];

        foreach ($config as $environmentDeclaration => $environmentValues) {
            if (!$this->configEnvironmentDeclarationMatchesName($environmentDeclaration, $environmentName)) {
                continue;
            }

            $inheritedValues = [];
            $colonPosition   = strpos($environmentDeclaration, ':');

            if (false !== $colonPosition) {
                $inheritedEnvironment = trim(substr($environmentDeclaration, $colonPosition + 1));
                $inheritedValues      = $this->getValuesForEnvironment($inheritedEnvironment, $config);
            }

            $values = array_merge($inheritedValues, $environmentValues);
        }

        return $values;
    }

    private function configEnvironmentDeclarationMatchesName($environmentDeclaration, $environmentName)
    {
        $colonPosition = strpos($environmentDeclaration, ':');

        if (false !== $colonPosition) {
            $environmentDeclaration = trim(substr($environmentDeclaration, 0, $colonPosition));
        }

        return strtolower($environmentDeclaration) === strtolower($environmentName);
    }

    private function findDatabasesInValues(array $configValues)
    {
        $databases = [];

        foreach ($configValues as $name => $configValue) {
            if ('resources.db.adapter' === $name) {
                $databases[] = new Database(
                    $this->getDatabaseType($configValue),
                    $configValues['resources.db.params.dbname'],
                    $configValues['resources.db.params.username'],
                    $configValues['resources.db.params.password'],
                    $configValues['resources.db.params.host']
                );
            }
        }

        return $databases;
    }

    private function getDatabaseType($configValue)
    {
        switch ($configValue) {
            case 'Pdo_Pgsql':
                return 'postgres';
            case 'Pdo_Mysql':
                return 'mysql';
            default:
                return 'unknown';
        }
    }
}
