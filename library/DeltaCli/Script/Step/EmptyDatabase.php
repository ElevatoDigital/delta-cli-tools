<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database;
use DeltaCli\Exception\PgsqlPdoDriverNotInstalled;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;
use DeltaCli\Script as ScriptObject;
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

        try {
            $pdo = $this->database->createPdoConnection(
                ($tunnelPort ? $this->sshTunnel->getHostname() : $this->database->getHost()),
                $tunnelPort
            );

            $this->database->emptyDb($pdo);
        } catch (PgsqlPdoDriverNotInstalled $e) {
            $this->database->emptyPostgresDbWithoutPdo($this->sshTunnel, $tunnelPort);
        }

        $this->sshTunnel->tearDown();
    }

    public function getName()
    {
        $slugify = new Slugify();
        return 'empty-' . $slugify->slugify($this->database->getDatabaseName()) . '-database';
    }
}
