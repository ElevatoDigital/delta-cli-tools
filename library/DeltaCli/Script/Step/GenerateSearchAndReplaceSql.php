<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Exception\DatabaseQueryFailed;
use DeltaCli\Host;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSearchAndReplaceSql extends EnvironmentHostsStepAbstract
{
    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var OutputInterface;
     */
    private $output;

    /**
     * @var string
     */
    private $searchString;

    /**
     * @var string
     */
    private $replacementString;

    /**
     * @var string
     */
    private $sqlPath;

    public function __construct(DatabaseInterface $database, OutputInterface $output, $searchString, $replacementString)
    {
        $this->database          = $database;
        $this->searchString      = $searchString;
        $this->replacementString = $replacementString;
        $this->output            = $output;

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $this->sqlPath = sprintf('%s/.%s.sql', getcwd(), uniqid('delta-cli-search-and-replace', true));

        $host->getSshTunnel()->setUp();

        $database = $this->database;
        $database->setSshTunnel($host->getSshTunnel());

        $tableNames  = $database->getTableNames();
        $progressBar = new ProgressBar($this->output, count($tableNames));

        $numberOfReplacements = 0;
        $skippedTables        = [];

        foreach ($tableNames as $table) {
            $primaryKeyColumns = $database->getPrimaryKey($table);

            if (!count($primaryKeyColumns)) {
                $skippedTables[] = $table;
            } else {
                $numberOfReplacements += $this->processTableData($table, $primaryKeyColumns);
            }

            $progressBar->advance();
        }

        $progressBar->clear();

        foreach ($skippedTables as $table) {
            $this->output->writeln(
                sprintf(
                    '<comment>Could not perform search and replace on %s because no primary key was found.</comment>',
                    $table
                )
            );
        }

        $host->getSshTunnel()->tearDown();

        if ($numberOfReplacements) {
            return [["Generated SQL to replace the search string {$numberOfReplacements} times."], 0];
        } else {
            return [['Search string was not found in the database.'], 0];
        }
    }

    public function getSqlPath()
    {
        return $this->sqlPath;
    }

    public function getName()
    {
        return 'generate-search-and-replace-sql';
    }

    private function processTableData($table, array $primaryKeyColumns)
    {
        $numberOfReplacements = 0;

        $data = $this->database->query(
            sprintf(
                'SELECT * FROM %s;',
                $this->database->quoteIdentifier($table)
            )
        );

        foreach ($data as $row) {
            $columnsToUpdate = $this->processRow($row);

            if (count($columnsToUpdate)) {
                $numberOfReplacements += 1;

                $primaryKeyValues = [];

                foreach ($primaryKeyColumns as $column) {
                    $primaryKeyValues[] = $row[$column];
                }

                try {
                    file_put_contents(
                        $this->sqlPath,
                        $this->database->assembleUpdateSql(
                            $table,
                            $columnsToUpdate,
                            $primaryKeyColumns,
                            $primaryKeyValues
                        ) . PHP_EOL,
                        LOCK_EX | FILE_APPEND
                    );
                } catch (DatabaseQueryFailed $exception) {

                }
            }
        }

        return $numberOfReplacements;
    }


    private function processRow($row)
    {
        $columnsToUpdate = [];

        foreach ($row as $column => $originalValue) {
            $filteredValue = $this->recursiveUnserializeReplace(
                $this->searchString,
                $this->replacementString,
                $originalValue
            );

            if ($filteredValue !== $originalValue) {
                $columnsToUpdate[$column] = $filteredValue;
            }
        }

        return $columnsToUpdate;
    }

    private function recursiveUnserializeReplace($from = '', $to = '', $data = '', $serialised = false)
    {
        // Some unserialized data cannot be re-serialised eg. SimpleXMLElements
        try {
            if (is_string($data) && false !== ($unserialized = @unserialize($data))) {
                $data = $this->recursiveUnserializeReplace($from, $to, $unserialized, true);
            } elseif (is_array($data)) {
                $tmp = [];
                foreach ($data as $key => $value) {
                    $tmp[$key] = $this->recursiveUnserializeReplace($from, $to, $value, false);
                }

                $data = $tmp;
                unset($tmp);
            } elseif (is_object($data)) {
                $dataClass = get_class($data);

                $tmp = new $dataClass();

                /* @var $data object */
                foreach ($data as $key => $value) {
                    $tmp->$key = $this->recursiveUnserializeReplace($from, $to, $value, false);
                }

                $data = $tmp;
                unset($tmp);
            } else {
                if (is_string($data)) {
                    $data = str_replace($from, $to, $data);
                }
            }

            if ($serialised) {
                return serialize($data);
            }
        } catch (\Exception $error) {

        }

        return $data;
    }
}
