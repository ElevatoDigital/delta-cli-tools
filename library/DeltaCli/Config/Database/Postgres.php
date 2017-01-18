<?php

namespace DeltaCli\Config\Database;

use DeltaCli\Exception\DatabaseQueryFailed;
use DeltaCli\Exec;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class Postgres extends DatabaseAbstract
{
    public function getShellCommand()
    {
        return sprintf(
            'PGPASSWORD=%s psql -q -U %s -h %s -p %s %s',
            escapeshellarg($this->password),
            escapeshellarg($this->username),
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->databaseName)
        );
    }

    public function renderShellHelp(OutputInterface $output)
    {
        $table = new Table($output);

        $table->addRows(
            [
                ['Display Tables', '\dt+'],
                ['Display Columns from a Table', '\d [table-name]'],
                ['Automatically Enable Expanded Display Mode', '\x auto'],
                ['Open Editor for More Complex Queries', '\e'],
                ['Display Help', '\?'],
                ['Turn on Timing of All Commands', '\timing'],
                ['Run Query Repeatedly and Show Results', '\watch [number-of-seconds]'],
                ['Connection Information', '\conninfo'],
                ['Quit', '\q']
            ]
        );

        $table->render();
    }

    public function getType()
    {
        return 'postgres';
    }

    public function getDumpCommand()
    {
        return sprintf(
            'PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s',
            escapeshellarg($this->password),
            escapeshellarg($this->username),
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->databaseName)
        );
    }

    public function emptyDb()
    {
        $this->query('DROP SCHEMA public CASCADE;');
        $this->query('CREATE SCHEMA public;');
    }

    public function query($sql, array $params = [])
    {
        $sql = $this->escapeQueryParams($sql, $params);

        $command = sprintf(
            'echo %s | %s -v ON_ERROR_STOP=1 --pset=footer -A -q 2>&1',
            escapeshellarg($sql),
            $this->getShellCommand()
        );

        Exec::run($this->sshTunnel->assembleSshCommand($command), $output, $exitStatus);

        if ($exitStatus) {
            throw new DatabaseQueryFailed(implode(PHP_EOL, $output));
        }

        if (!isset($output[0]) || !trim($output[0])) {
            return [];
        }

        return $this->prepareResultsArrayFromCommandOutput($output, "\t");
    }

    public function getDefaultPort()
    {
        return 5432;
    }


    private function escapeQueryParams($sql, array $params)
    {
        $params = array_map(
            function ($value) {
                return sprintf("'%s'", str_replace("'", "''", $value));
            },
            $params
        );

        return vsprintf($sql, $params);
    }
}
