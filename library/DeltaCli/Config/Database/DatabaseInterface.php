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
     * @return string
     */
    public function getUsername();

    /**
     * @return string
     */
    public function getPassword();

    /**
     * @return string
     */
    public function getHost();

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
     * @return string[]
     */
    public function getTableNames();

    /**
     * @param string $tableName
     * @return array
     */
    public function getColumns($tableName);

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
