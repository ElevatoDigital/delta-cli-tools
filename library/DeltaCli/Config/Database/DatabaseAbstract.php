<?php

namespace DeltaCli\Config\Database;

use DeltaCli\Exception\PrimaryKeyColumnAndValueCountMismatch;
use DeltaCli\Extension\Vagrant\Exception;
use DeltaCli\SshTunnel;

abstract class DatabaseAbstract implements DatabaseInterface
{
    /**
     * @var string
     */
    protected $databaseName;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var SshTunnel
     */
    protected $sshTunnel;

    public function __construct($databaseName, $username, $password, $host, $port = null)
    {
        $this->databaseName = $databaseName;
        $this->username     = $username;
        $this->password     = $password;
        $this->host         = $host;
        $this->port         = ($port ?: $this->getDefaultPort());
    }

    public function setSshTunnel(SshTunnel $sshTunnel)
    {
        $this->sshTunnel = $sshTunnel;

        return $this;
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

    public function getPort()
    {
        return $this->port;
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function fetchCol($sql, array $params = [])
    {
        $results = $this->query($sql, $params);
        $output  = [];

        foreach ($results as $row) {
            $values = array_values($row);
            $output[] = $values[0];
        }

        return $output;
    }

    public function update($tableName, array $data, $primaryKeyColumns, $primaryKeyValues)
    {
        return $this->query($this->assembleUpdateSql($tableName, $data, $primaryKeyColumns, $primaryKeyValues));
    }

    public function assembleUpdateSql($tableName, array $data, $primaryKeyColumns, $primaryKeyValues)
    {
        if (!is_array($primaryKeyColumns)) {
            $primaryKeyColumns = [$primaryKeyColumns];
        }

        if (!is_array($primaryKeyValues)) {
            $primaryKeyValues = [$primaryKeyValues];
        }

        if (count($primaryKeyValues) !== count($primaryKeyColumns)) {
            throw new PrimaryKeyColumnAndValueCountMismatch(
                sprintf(
                    'Cannot update %s because the primary key columns and values do not match up.',
                    $tableName
                )
            );
        }

        $whereClauseSegments = [];
        $setClauseSegments   = [];
        $bindParams          = [];

        foreach ($data as $columnName => $value) {
            $bindParams[]        = $value;
            $setClauseSegments[] = sprintf('%s = %%s', $this->quoteIdentifier($columnName));
        }

        foreach ($primaryKeyColumns as $index => $columnName) {
            $bindParams[]          = $primaryKeyValues[$index];
            $whereClauseSegments[] = sprintf('%s = %%s', $this->quoteIdentifier($columnName));
        }

        return $this->prepare(
            sprintf(
                'UPDATE %s SET %s WHERE %s;',
                $this->quoteIdentifier($tableName),
                implode(', ', $setClauseSegments),
                implode(' AND ', $whereClauseSegments)
            ),
            $bindParams
        );
    }

    protected function prepareResultsArrayFromCommandOutput(array $output, $delimiter)
    {
        $results = [];

        $headerLine = array_shift($output);
        $headers    = explode($delimiter, $headerLine);

        foreach ($output as $line) {
            $rowData        = explode($delimiter, $line);
            $rowWithHeaders = [];

            foreach ($rowData as $index => $column) {
                if (isset($headers[$index])) {
                    $header = $headers[$index];
                } else {
                    $header = $index;
                }

                $rowWithHeaders[$header] = $column;
            }

            $results[] = $rowWithHeaders;
        }

        return $results;
    }
}