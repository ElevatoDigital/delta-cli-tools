<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;

class AllowWritesToRemoteFolder extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $remoteFolder;

    public function __construct($remoteFolder)
    {
        $this->remoteFolder = $remoteFolder;
    }

    public function runOnHost(Host $host)
    {
        $ssh = new Ssh(
            sprintf(
                'find %s -user %s -type d -exec chmod 777 {} \\; 2>&1',
                escapeshellarg($this->remoteFolder),
                escapeshellarg($host->getUsername())
            )
        );

        $ssh
            ->setSelectedEnvironment($this->environment)
            ->setName($this->name);

        return $ssh->runOnHost($host);
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            $folderName = $this->remoteFolder;

            if ('.' === $folderName) {
                $folderName = null;
            }

            return sprintf(
                'allow-writes-to-%s',
                basename($folderName) ?: 'remote-folder'
            );
        }
    }

}
