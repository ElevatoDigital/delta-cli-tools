<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;
use DeltaCli\SshTunnel;

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

        if ('vagrant' === $host->getEnvironment()->getName()) {
            $this->adjustMaxAllowedPacketInVagrant($host, $tunnel);
        }

        $command = $this->database->getShellCommand();

        $this->execSsh(
            $host,
            sprintf(
                '%s < %s 2>&1',
                $tunnel->assembleSshCommand($command),
                escapeshellarg($this->dumpFileName)
            ),
            $output,
            $exitStatus
        );

        $tunnel->tearDown();

        if (0 === $exitStatus) {
            $output[] = sprintf(
                'Successfully ran SQL file %s on %s in %s.',
                $this->dumpFileName,
                $this->database->getDatabaseName(),
                $host->getEnvironment()->getName()
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

    /**
     * When max_allowed_packet is low, it's possible for restores to fail because of the large commands generated
     * by mysqldump.  In the Delta Vagrant environment, we can automatically fix this problem for users by adjusting
     * the variable as the root user.
     *
     * @param Host $host
     * @param SshTunnel $sshTunnel
     * @return void
     */
    private function adjustMaxAllowedPacketInVagrant(Host $host, SshTunnel $sshTunnel)
    {
        $sql = 'SET GLOBAL max_allowed_packet=104857600;';
        $cmd = sprintf('echo %s | mysql --user=root --password=delta', escapeshellarg($sql));

        $this->execSsh(
            $host,
            $sshTunnel->assembleSshCommand($cmd),
            $output,
            $exitStatus
        );
    }
}
