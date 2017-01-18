<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;

class RestoreDatabase extends EnvironmentHostsStepAbstract
{
    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var string
     */
    private $dumpFileName;

    public function __construct(DatabaseInterface $database, $dumpFileName)
    {
        $this->database     = $database;
        $this->dumpFileName = $dumpFileName;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();

        if (!file_exists($this->dumpFileName) || !is_readable($this->dumpFileName)) {
            throw new \Exception("Could not read dump file at: {$this->dumpFileName}.");
        }

        $command = $this->database->getShellCommand();

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
