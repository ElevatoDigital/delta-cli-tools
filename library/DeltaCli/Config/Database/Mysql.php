<?php

namespace DeltaCli\Config\Database;

use DeltaCli\Exception\DatabaseQueryFailed;
use DeltaCli\Exec;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class Mysql extends DatabaseAbstract
{
    public function getShellCommand()
    {
        return sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($this->username),
            escapeshellarg($this->password),
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
                ['Display Tables', 'SHOW TABLES;'],
                ['Display Columns from a Table', 'SHOW COLUMNS FROM [table-name];'],
                ['Use Expanded Output Formatting', 'Append \G to the end of your query.'],
                ['Open Editor for More Complex Queries', '\e'],
                ['Server Status', '\s'],
                ['Display Help', 'help'],
                ['Turn on Timing of All Commands', '"SET profiling=1" and "SHOW PROFILES;"'],
                ['Quit', '\q']
            ]
        );

        $table->render();
    }

    public function getType()
    {
        return 'mysql';
    }

    public function getDumpCommand()
    {
        return sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($this->username),
            escapeshellarg($this->password),
            escapeshellarg($this->host),
            escapeshellarg($this->port),
            escapeshellarg($this->databaseName)
        );
    }

    public function emptyDb()
    {
        foreach ($this->fetchCol('SHOW TABLES;') AS $table) {
            $this->query("DROP TABLE {$table};");
        }
    }

    public function query($sql, array $params = [])
    {
        $sql = $this->escapeQueryParams($sql, $params);

        $command = sprintf(
            'echo %s | %s --batch 2>&1',
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
        return 3306;
    }

    private function escapeQueryParams($sql, array $params)
    {
        $params = array_map(
            function ($value) {
                return sprintf("'%s'", str_replace("'", "\\'", $value));
            },
            $params
        );

        return vsprintf($sql, $params);
    }
}
