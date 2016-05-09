<?php

namespace DeltaCli;

class ComposerVersion
{
    private $vendorPath;

    public function __construct($vendorPath)
    {
        $this->vendorPath = $vendorPath;
    }

    public function getCurrentVersion()
    {
        $jsonFile = $this->vendorPath . '/composer/installed.json';

        if (!file_exists($jsonFile) || !is_readable(($jsonFile))) {
            return '0.0.0';
        }

        $jsonContent        = file_get_contents($jsonFile);
        $installedLibraries = json_decode($jsonContent, true);

        foreach ($installedLibraries as $library) {
            if (isset($library['name']) && 'deltasystems/delta-cli' === $library['name']) {
                return $library['version'];
            }
        }

        return 'git';
    }
}
