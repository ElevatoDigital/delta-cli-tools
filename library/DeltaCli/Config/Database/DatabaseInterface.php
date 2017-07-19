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
     * @return integer
     */
    public function getPort();

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
     * @param array $params
     * @return string
     */
    public function prepare($sql, array $params = []);

    /**
     * @param string $sql
     * @return mixed
     */
    public function query($sql, array $params = []);

    /**
     * @param string $tableName
     * @param array $data
     * @param mixed $primaryKeyColumns
     * @param mixed $primaryKeyValues
     * @return mixed
     */
    public function update($tableName, array $data, $primaryKeyColumns, $primaryKeyValues);

    /**
     * @param string $tableName
     * @param array $data
     * @param mixed $primaryKeyColumns
     * @param mixed $primaryKeyValues
     * @return mixed
     */
    public function assembleUpdateSql($tableName, array $data, $primaryKeyColumns, $primaryKeyValues);

    /**
     * Quote an identifier string (e.g. table name, column name, etc).
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier);

    /**
     * Get the primary key column(s) for a table.  Always returns an array to support multi-column keys.
     *
     * @param string $tableName
     * @return array
     */
    public function getPrimaryKey($tableName);

    /**
     * @param OutputInterface $output
     */
    public function renderShellHelp(OutputInterface $output);

    /**
     * @param SshTunnel $sshTunnel
     * @return $this
     */
    public function setSshTunnel(SshTunnel $sshTunnel);

    /**
     * Return a SQL expression that can be used to replace newlines with a single space.
     *
     * @param string $column
     * @return string
     */
    public function getReplaceNewlinesExpression($column);

    /**
     * @return string
     */
    public function getTimestampDataType();

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $type
     * @param boolean $nullable
     * @param string $default
     * @return mixed
     */
    public function generateAddColumnDdl($tableName, $columnName, $type, $nullable, $default);
}
