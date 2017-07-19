<?php

namespace DeltaCli;

use DeltaCli\Config\Database\DatabaseInterface;

class DatabaseAuditConfig
{
    /**
     * @var callable[]
     */
    private $databaseSpecificConfigurationCallbacks = [];

    /**
     * @var string[]
     */
    private $excludedTables = [];

    public function applyDatabaseSpecificConfiguration(callable $configurationCallback)
    {
        $this->databaseSpecificConfigurationCallbacks[] = $configurationCallback;

        return $this;
    }

    public function generateConfigForSpecificDatabase(DatabaseInterface $database)
    {
        $config = clone($this);

        foreach ($this->databaseSpecificConfigurationCallbacks as $callback) {
            $callback($database);
        }

        return $config;
    }

    public function excludeTable($table)
    {
        $this->excludedTables[] = $table;

        return $this;
    }

    public function tableIsExcluded($table)
    {
        return in_array($table, $this->excludedTables);
    }
}
