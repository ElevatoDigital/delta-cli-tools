<?php

namespace DeltaCli\Config\Database;

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

    protected function prepareResultsArrayFromCommandOutput(array $output, $delimiter)
    {
        $results = [];

        $headerLine = array_shift($output);
        $headers    = explode($delimiter, $headerLine);

        foreach ($output as $line) {
            $rowData        = explode($delimiter, $line);
            $rowWithHeaders = [];

            foreach ($rowData as $index => $column) {
                $header = $headers[$index];

                $rowWithHeaders[$header] = $column;
            }

            $results[] = $rowWithHeaders;
        }

        return $results;
    }
}