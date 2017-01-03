<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database;
use DeltaCli\Environment;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;

class DumpDatabase extends EnvironmentHostsStepAbstract
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var string
     */
    private $dumpFileName;

    public function __construct(Database $database)
    {
        $this->database = $database;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $this->dumpFileName = $this->generateDumpFileName($host->getEnvironment());

        $tunnel = $host->getSshTunnel();
        $port   = $tunnel->setUp();

        if (false === $port) {
            $dumpCommand = $this->database->getDumpCommand();
        } else {
            $dumpCommand = $this->database->getDumpCommand($tunnel->getHostname(), $port);
        }

        $this->execSsh(
            $host,
            sprintf(
                '%s > %s 2> /dev/null',
                $tunnel->assembleSshCommand($dumpCommand),
                escapeshellarg($this->dumpFileName)
            ),
            $output,
            $exitStatus
        );

        $tunnel->tearDown();

        if (0 === $exitStatus) {
            $output[] = sprintf(
                'Successfully created database dump in %s.',
                $this->dumpFileName
            );
        }

        return [$output, $exitStatus];
    }

    public function getName()
    {
        $slugify = new Slugify();
        return 'dump-' . $slugify->slugify($this->database->getDatabaseName()) . '-database';
    }

    /**
     * @return string
     */
    public function getDumpFileName()
    {
        return $this->dumpFileName;
    }

    private function generateDumpFileName(Environment $environment)
    {
        $slugify = new Slugify();

        return sprintf(
            '%s-dump-%s-from-%s.sql',
            $slugify->slugify($this->database->getDatabaseName()),
            date('Ymd-hiA'),
            $environment->getName()
        );
    }
}
