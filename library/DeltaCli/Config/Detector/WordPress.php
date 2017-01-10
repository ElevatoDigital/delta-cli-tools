<?php

namespace DeltaCli\Config\Detector;

use DeltaCli\Config\Config;
use DeltaCli\Config\Database;
use DeltaCli\Environment;

class WordPress implements DetectorInterface
{
    public function getMostLikelyRemoteFilePath()
    {
        return 'httpdocs/wp-config.php';
    }

    public function getPotentialFilePaths()
    {
        return [
            'wp-config.php',
            'src/wp-config.php'
        ];
    }

    public function getName()
    {
        return 'wordpress';
    }

    public function createConfigFromFile(Environment $environment, $configFile)
    {
        $config = new Config();
        $config->addDatabase($this->createDatabaseFromConfigFile($configFile));
        return $config;
    }

    private function createDatabaseFromConfigFile($configFile)
    {
        $source    = file_get_contents($configFile);
        $tokens    = token_get_all($source);
        $constants = [];

        foreach ($tokens as $index => $token) {
            if ($this->tokenIsConstant($token)) {
                list($name, $value) = $this->parseConstantFromIndex($index, $tokens);

                $constants[$name] = $value;
            }
        }

        return new Database(
            'mysql',
            $constants['DB_NAME'],
            $constants['DB_USER'],
            $constants['DB_PASSWORD'],
            $constants['DB_HOST']
        );
    }

    private function tokenIsConstant($token)
    {
        if (!is_array($token)) {
            return false;
        }

        return T_STRING === $token[0] && 'define' === $token[1];
    }

    private function parseConstantFromIndex($defineTokenIndex, array $tokens)
    {
        $name  = null;
        $value = null;

        foreach ($tokens as $index => $token) {
            if ($index < $defineTokenIndex || !is_array($token) || T_CONSTANT_ENCAPSED_STRING !== $token[0]) {
                continue;
            }

            $token[1] = trim($token[1], "'");

            if (!$name) {
                $name = $token[1];
            } else if (!$value) {
                $value = $token[1];
                break;
            }
        }

        return [$name, $value];
    }
}
