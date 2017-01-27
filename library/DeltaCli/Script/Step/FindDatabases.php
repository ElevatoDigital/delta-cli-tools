<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Config;
use DeltaCli\Config\ConfigFactory;
use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Exception\DatabaseNotFound;
use DeltaCli\Exception\MultipleDatabasesFound;
use DeltaCli\Exception\NoDatabasesAvailable;
use DeltaCli\Host;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindDatabases extends EnvironmentHostsStepAbstract
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var array
     */
    private $databases = [];

    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;

        $this->limitToOnlyFirstHost();
    }

    public function configure(InputDefinition $inputDefinition)
    {
        $inputDefinition->addOption(
            new InputOption(
                'database',
                null,
                InputOption::VALUE_REQUIRED,
                'The database you want to use.'
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                'database-type',
                null,
                InputOption::VALUE_REQUIRED,
                'The type of database.  Only needed if the name is ambiguous.'
            )
        );

        return $this;
    }

    public function runOnHost(Host $host)
    {
        $configs = $this->configFactory->detectConfigsOnHost($host);

        if (false !== $configs) {
            /* @var $config Config */
            foreach ($configs as $config) {
                foreach ($config->getDatabases() as $database) {
                    $this->databases[] = $database;
                }
            }
        }
    }

    public function getName()
    {
        return 'find-databases';
    }

    /**
     * @return DatabaseInterface[]
     */
    public function getDatabases()
    {
        return $this->databases;
    }

    /**
     * @param InputInterface $input
     * @return DatabaseInterface
     */
    public function getSelectedDatabase(
        InputInterface $input,
        $databaseOptionName = 'database',
        $databaseTypeOptionName = 'database-type'
    )
    {
        if (0 === count($this->databases)) {
            $exception = new NoDatabasesAvailable();
            $exception->setEnvironment($this->environment);
            throw $exception;
        }

        $matches = [];

        if ($input->hasOption($databaseOptionName) && $input->getOption($databaseOptionName)) {
            foreach ($this->databases as $database) {
                if ($database->getDatabaseName() === $input->getOption($databaseOptionName)) {
                    $matches[] = $database;
                }
            }
        } else {
            $matches = $this->databases;
        }

        if ($input->hasOption($databaseTypeOptionName) && $input->getOption($databaseTypeOptionName)) {
            /* @var $nameMatches DatabaseInterface[] */
            $nameMatches = $matches;
            $matches     = [];

            foreach ($nameMatches as $database) {
                if ($database->getType() === $input->getOption($databaseTypeOptionName)) {
                    $matches[] = $database;
                }
            }
        }

        if (0 === count($matches)) {
            $exception = new DatabaseNotFound();
            $exception
                ->setEnvironment($this->environment)
                ->setDatabaseOption($databaseOptionName)
                ->setDatabaseTypeOption($databaseTypeOptionName)
                ->setMatchedDatabases($matches)
                ->setAvailableDatabases($this->databases);
            throw $exception;
        } elseif (1 < count($matches)) {
            $exception = new MultipleDatabasesFound();
            $exception
                ->setEnvironment($this->environment)
                ->setDatabaseOption($databaseOptionName)
                ->setDatabaseTypeOption($databaseTypeOptionName)
                ->setMatchedDatabases($matches)
                ->setAvailableDatabases($this->databases);
            throw $exception;
        }

        return reset($matches);
    }

    private function getOptionValue(InputInterface $input, $optionName)
    {
        $value = '[unspecified]';

        if ($input->hasOption($optionName) && $input->getOption($optionName)) {
            $value = $input->getOption($optionName);
        }

        return $value;
    }
}
