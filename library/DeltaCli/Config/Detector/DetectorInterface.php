<?php

namespace DeltaCli\Config\Detector;

use DeltaCli\Config\Config;
use DeltaCli\Environment;

interface DetectorInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getMostLikelyRemoteFilePath();

    /**
     * @return string[]
     */
    public function getPotentialFilePaths();

    /**
     * @param Environment $environment
     * @param string $configFile
     * @return Config
     */
    public function createConfigFromFile(Environment $environment, $configFile);
}
