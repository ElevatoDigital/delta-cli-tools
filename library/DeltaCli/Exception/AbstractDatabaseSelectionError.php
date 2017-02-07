<?php

namespace DeltaCli\Exception;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Console\Output\Banner;
use DeltaCli\Console\Output\DatabasesTable;
use DeltaCli\Environment;
use Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDatabaseSelectionError extends Exception implements ConsoleOutputInterface
{
    /**
     * @var Environment
     */
    protected $environment;

    protected $databaseOption;

    protected $databaseTypeOption;

    /**
     * @var DatabaseInterface[]
     */
    protected $availableDatabases = [];

    /**
     * @var DatabaseInterface[]
     */
    protected $matchedDatabases = [];

    protected $databaseName;

    protected $databaseType;

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setDatabaseOption($databaseOption)
    {
        $this->databaseOption = $databaseOption;

        return $this;
    }

    public function setDatabaseTypeOption($databaseTypeOption)
    {
        $this->databaseTypeOption = $databaseTypeOption;

        return $this;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    public function setAvailableDatabases(array $availableDatabases)
    {
        $this->availableDatabases = $availableDatabases;

        return $this;
    }

    /**
     * @param array $matchedDatabases
     * @return $this
     */
    public function setMatchedDatabases(array $matchedDatabases)
    {
        $this->matchedDatabases = $matchedDatabases;

        return $this;
    }

    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;

        return $this;
    }

    public function setDatabaseType($databaseType)
    {
        $this->databaseType = $databaseType;

        return $this;
    }

    abstract public function getBannerTitle();

    abstract public function displayMessage(OutputInterface $output);

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render($this->getBannerTitle());

        $this->displayMessage($output);

        $table = new Table($output);

        $table
            ->setHeaders(['Option', 'Description'])
            ->addRow(["--{$this->databaseOption}", 'Specify the name of the database you want to select.'])
            ->addRow(["--{$this->databaseTypeOption}", 'Specify the type of the database (e.g. mysql or postgres).']);

        $table->render();

        if (count($this->matchedDatabases) && ($this->databaseName || $this->databaseType)) {
            $databaseNames = [];

            foreach ($this->matchedDatabases as $database) {
                $databaseNames[] = $database->getDatabaseName();
            }

            $output->writeln(
                [
                    '',
                    'Your current use of those options matched these databases:',
                    implode(', ', $databaseNames)
                ]
            );
        }

        $output->writeln(
            [
                '',
                'Here are the databases available in the environment:',
                ''
            ]
        );

        $databasesTable = new DatabasesTable($output, $this->availableDatabases);
        $databasesTable->render();
    }

    public function hasBanner()
    {
        return true;
    }
}
