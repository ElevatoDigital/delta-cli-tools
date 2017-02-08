<?php

namespace DeltaCli\Config\Detector;

use DeltaCli\Config\Config;
use DeltaCli\Config\Database\DatabaseFactory;
use DeltaCli\Environment;

class ApacheEnv implements DetectorInterface
{
    const TOKEN_DELIMITERS = " \n\t";

    public function getMostLikelyRemoteFilePath()
    {
        return 'conf/env-apache.conf';
    }

    public function getName()
    {
        return 'apache-environment-variables';
    }

    public function getPotentialFilePaths()
    {
        return [];
    }

    public function createConfigFromFile(Environment $environment, $configFile)
    {
        $tokens    = $this->tokenizeFile($configFile);
        $variables = $this->getEnvironmentVariablesFromTokens($tokens);
        $config    = new Config();

        if (isset($variables['WEBSITE_URL']) && $variables['WEBSITE_URL']) {
            $config->setBrowserUrl($variables['WEBSITE_URL']);
        }

        if ($this->databaseIsPresent('POSTGRES', $variables)) {
            $config->addDatabase($this->createDatabase('postgres', 'POSTGRES', $variables));
        }

        if ($this->databaseIsPresent('MYSQL', $variables)) {
            $config->addDatabase($this->createDatabase('mysql', 'MYSQL', $variables));
        }

        return $config;
    }

    private function createDatabase($type, $prefix, array $variables)
    {
        if (isset($variables["{$prefix}_HOSTNAME"]) && $variables["{$prefix}_HOSTNAME"]) {
            $host = "{$prefix}_HOSTNAME";
        } else {
            $host = 'localhost';
        }

        return DatabaseFactory::createInstance(
            $type,
            $variables["{$prefix}_DATABASE"],
            $variables["{$prefix}_USERNAME"],
            $variables["{$prefix}_PASSWORD"],
            $host
        );
    }

    private function databaseIsPresent($prefix, array $variables)
    {
        $params = ['USERNAME', 'PASSWORD', 'DATABASE'];

        foreach ($params as $suffix) {
            $param = "{$prefix}_{$suffix}";

            if (!isset($variables[$param]) || !$variables[$param]) {
                echo $param . PHP_EOL;
                return false;
            }
        }

        return true;
    }

    private function tokenizeFile($configFile)
    {
        $contents = file_get_contents($configFile);
        $tokens   = [];

        $token = strtok($contents, self::TOKEN_DELIMITERS);

        while (false !== $token) {
            $tokens[] = $token;

            $token = strtok(self::TOKEN_DELIMITERS);
        }

        return $tokens;
    }

    private function getEnvironmentVariablesFromTokens(array $tokens)
    {
        $variables = [];

        foreach ($tokens as $index => $token) {
            if ('SetEnv' === $token) {
                $name  = $tokens[$index + 1];
                $value = trim($tokens[$index + 2], '"');
                $variables[$name] = $value;
            }
        }

        return $variables;
    }
}
