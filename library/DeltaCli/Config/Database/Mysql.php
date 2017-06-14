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

    public function getTableNames()
    {
        return $this->fetchCol('SHOW TABLES;');
    }

    public function getColumns($tableName)
    {
        $sql = sprintf("DESCRIBE `%s`;", $tableName);

        $columns = [];

        foreach ($this->query($sql) as $columnData) {
            $columns[] = [
                'name' => $columnData['Field'],
                'type' => $columnData['Type']
            ];
        }

        return $columns;
    }

    public function getForeignKeys($tableName)
    {
        $sql = 'SELECT
                    column_name,
                    referenced_table_name,
                    referenced_column_name
                FROM information_schema.key_column_usage
                WHERE
                    table_name = %s
                    AND referenced_table_name IS NOT NULL
                    AND referenced_column_name IS NOT NULL';

        $references = [];

        foreach ($this->query($sql, [$tableName]) as $reference) {
            $column = $reference['column_name'];

            $references[$column] = array(
                'table'  => $reference['referenced_table_name'],
                'column' => $reference['referenced_column_name']
            );
        }
        return $references;
    }

    public function getPrimaryKey($tableName)
    {
        $primaryKey = [];

        $keys = $this->query(
            sprintf(
                "SHOW KEYS FROM %s WHERE Key_name = 'PRIMARY'",
                $this->quoteIdentifier($tableName)
            )
        );

        foreach ($keys as $key) {
            $primaryKey[] = $key['Column_name'];
        }

        return $primaryKey;
    }

    public function emptyDb()
    {
        foreach ($this->getTableNames() AS $table) {
            $this->query("SET FOREIGN_KEY_CHECKS=0; DROP TABLE {$table};");
        }
    }

    public function query($sql, array $params = [])
    {
        $sql = $this->prepare($sql, $params);

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

    public function quoteIdentifier($identifier)
    {
        return sprintf('`%s`', $identifier);
    }

    public function getDefaultPort()
    {
        return 3306;
    }

    public function prepare($sql, array $params = [])
    {
        $params = array_map(
            function ($value) {
                return sprintf("'%s'", str_replace("'", "\\'", $value));
            },
            $params
        );

        return vsprintf($sql, $params);
    }

    public function getReplaceNewlinesExpression($column)
    {
        return "REPLACE({$column}, '\n', '')";
    }
}
