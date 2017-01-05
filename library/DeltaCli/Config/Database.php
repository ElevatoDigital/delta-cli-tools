<?php

namespace DeltaCli\Config;

use DeltaCli\Config\Database\TypeHandler\TypeHandlerFactory;
use DeltaCli\Exception\PostgresCommandFailed;
use DeltaCli\Exec;
use DeltaCli\SshTunnel;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

class Database
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var \DeltaCli\Config\Database\TypeHandler\TypeHandlerInterface
     */
    private $typeHandler;

    public function __construct($type, $databaseName, $username, $password, $host, $port = null)
    {
        $this->type         = $type;
        $this->databaseName = $databaseName;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
        $this->typeHandler  = TypeHandlerFactory::createInstance($this->type);
        $this->port         = (null === $port ? $this->getDefaultPort() : $port);
    }

    public function getType()
    {
        return $this->typeHandler->getName();
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function createPdoConnection($hostname = null, $port = null)
    {
        return $this->typeHandler->createPdoConnection(
            $this->username,
            $this->password,
            ($hostname ?: $this->host),
            $this->databaseName,
            ($port ?: $this->port)
        );
    }

    public function getShellCommand($hostname = null, $port = null)
    {
        return $this->typeHandler->getShellCommand(
            $this->username,
            $this->password,
            ($hostname ?: $this->host),
            $this->databaseName,
            ($port ?: $this->port)
        );
    }

    public function getDumpCommand($hostname = null, $port = null)
    {
        return $this->typeHandler->getDumpCommand(
            $this->username,
            $this->password,
            ($hostname ?: $this->host),
            $this->databaseName,
            ($port ?: $this->port)
        );
    }

    public function emptyDb(PDO $pdo)
    {
        $this->typeHandler->emptyDb($pdo);
    }

    /**
     * Mac OS does not include the pgsql PDO driver by default, so we have this method as a hack to clear out
     * a Postgres DB without needing that driver.
     *
     * @param SshTunnel $sshTunnel
     * @param integer|boolean $tunnelPort
     * @throws PostgresCommandFailed
     * @return void
     */
    public function emptyPostgresDbWithoutPdo(SshTunnel $sshTunnel, $tunnelPort)
    {
        $shellCommand = $this->getShellCommand(
            ($tunnelPort ? $sshTunnel->getHostname() : $this->host),
            $tunnelPort
        );

        $dropSchema = sprintf(
            "echo 'DROP SCHEMA public CASCADE;' | %s 2>&1",
            $shellCommand
        );

        Exec::run($sshTunnel->assembleSshCommand($dropSchema), $output, $exitStatus);

        if ($exitStatus) {
            $output = implode(PHP_EOL, $output);
            throw new PostgresCommandFailed("Failed to drop Postgres public schema: {$output}.");
        }

        $createSchema = sprintf(
            "echo 'CREATE SCHEMA public;' | %s 2>&1",
            $shellCommand
        );

        Exec::run($sshTunnel->assembleSshCommand($createSchema), $output, $exitStatus);

        if ($exitStatus) {
            $output = implode(PHP_EOL, $output);
            throw new PostgresCommandFailed("Failed to create Postgres public schema: {$output}.");
        }
    }

    public function renderShellHelp(OutputInterface $output)
    {
        $this->typeHandler->renderShellHelp($output);
    }

    private function getDefaultPort()
    {
        return $this->typeHandler->getDefaultPort();
    }
}
