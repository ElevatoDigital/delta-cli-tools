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
                'find %s -type d -exec chmod 777 {} \\;',
                escapeshellarg($this->remoteFolder)
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
            return sprintf(
                'allow-writes-to-%s',
                basename($this->remoteFolder) ?: 'remote-folder'
            );
        }
    }

}
