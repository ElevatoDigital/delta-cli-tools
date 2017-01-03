<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;
use DeltaCli\Script;
use DeltaCli\SshTunnel;

class EmptyDatabase extends EnvironmentHostsStepAbstract
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var SshTunnel
     */
    private $sshTunnel;

    public function __construct(Database $database)
    {
        $this->database = $database;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $this->sshTunnel = $host->getSshTunnel();

        $tunnelPort = $this->sshTunnel->setUp();

        $pdo = $this->database->createPdoConnection(
            ($tunnelPort ? $this->sshTunnel->getHostname() : $this->database->getHost()),
            $tunnelPort
        );

        $this->database->emptyDb($pdo);
    }

    public function getName()
    {
        $slugify = new Slugify();
        return 'empty-' . $slugify->slugify($this->database->getDatabaseName()) . '-database';
    }

    public function postRun(Script $script)
    {
        if ($this->sshTunnel) {
            $this->sshTunnel->tearDown();
        }
    }
}
