<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;

class Scp extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $localFile;

    /**
     * @var string
     */
    private $remoteFile;

    public function __construct($localFile, $remoteFile)
    {
        $this->localFile  = $localFile;
        $this->remoteFile = $remoteFile;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return sprintf(
                'scp-%s-to-remote',
                basename($this->localFile)
            );
        }
    }

    public function runOnHost(Host $host)
    {
        $command = sprintf(
            'scp %s -P %s %s %s %s@%s:%s 2>&1',
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($host->getSshPort()),
            (is_dir($this->localFile) ? '-r' : ''),
            escapeshellarg($this->localFile),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            escapeshellarg($this->remoteFile)
        );

        exec($command, $output, $exitStatus);

        return [$output, $exitStatus];
    }
}
