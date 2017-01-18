<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;
use DeltaCli\Script as ScriptObject;

class EmptyDatabase extends EnvironmentHostsStepAbstract
{
    /**
     * @var DatabaseInterface
     */
    private $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $sshTunnel = $host->getSshTunnel();
        $sshTunnel->setUp();

        $this->database
            ->setSshTunnel($sshTunnel)
            ->emptyDb();

        $sshTunnel->tearDown();
    }

    public function getName()
    {
        $slugify = new Slugify();
        return 'empty-' . $slugify->slugify($this->database->getDatabaseName()) . '-database';
    }
}
