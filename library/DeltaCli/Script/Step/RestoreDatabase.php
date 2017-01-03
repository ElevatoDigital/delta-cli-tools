<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;

class RestoreDatabase extends EnvironmentHostsStepAbstract
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var string
     */
    private $dumpFileName;

    public function __construct(Database $database, $dumpFileName)
    {
        $this->database     = $database;
        $this->dumpFileName = $dumpFileName;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();
        $port   = $tunnel->setUp();

        if (!file_exists($this->dumpFileName) || !is_readable($this->dumpFileName)) {
            throw new \Exception("Could not read dump file at: {$this->dumpFileName}.");
        }

        if (false === $port) {
            $command = $this->database->getShellCommand();
        } else {
            $command = $this->database->getShellCommand($tunnel->getHostname(), $port);
        }

        $this->execSsh(
            $host,
            sprintf(
                '%s < %s 2> /dev/null',
                $tunnel->assembleSshCommand($command),
                escapeshellarg($this->dumpFileName)
            ),
            $output,
            $exitStatus
        );

        $tunnel->tearDown();

        if (0 === $exitStatus) {
            $output[] = sprintf(
                'Successfully restored database dump %s to %s.',
                $this->dumpFileName,
                $this->database->getDatabaseName()
            );
        }

        return [$output, $exitStatus];
    }

    public function getName()
    {
        $slugify = new Slugify();
        return 'restore-' . $slugify->slugify($this->database->getDatabaseName()) . '-database';
    }

    /**
     * @return string
     */
    public function getDumpFileName()
    {
        return $this->dumpFileName;
    }
}
