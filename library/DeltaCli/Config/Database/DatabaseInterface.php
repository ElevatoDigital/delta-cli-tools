<?php

namespace DeltaCli\Config\Database;

use DeltaCli\SshTunnel;
use Symfony\Component\Console\Output\OutputInterface;

interface DatabaseInterface
{
    /**
     * @param string $databaseName
     * @param string $username
     * @param string $password
     * @param string $host
     * @param integer|null $port
     */
    public function __construct($databaseName, $username, $password, $host, $port = null);

    /**
     * @return string
     */
    public function getDatabaseName();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $username
     * @param string $password
     * @param string $hostname
     * @param string $databaseName
     * @param integer $port
     * @return string
     */
    public function getShellCommand();

    /**
     * @param string $hostname
     * @param integer $port
     * @return string
     */
    public function getDumpCommand();

    /**
     * @return integer
     */
    public function getDefaultPort();

    /**
     * @return void
     */
    public function emptyDb();

    /**
     * @param string $sql
     * @return mixed
     */
    public function query($sql, array $params = []);

    /**
     * @param OutputInterface $output
     */
    public function renderShellHelp(OutputInterface $output);

    /**
     * @param SshTunnel $sshTunnel
     * @return $this
     */
    public function setSshTunnel(SshTunnel $sshTunnel);
}
