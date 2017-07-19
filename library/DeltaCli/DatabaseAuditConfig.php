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

    /**
     * @var bool
     */
    private $excludeDeltaZendTables = true;

    /**
     * @var bool
     */
    private $excludeDewdropTables = true;

    /**
     * @var bool
     */
    private $excludeWordPressCoreTables = false;

    /**
     * @var string
     */
    private $wordPressPrefix;

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
        if ($this->excludeWordPressCoreTables && $this->isWordPressCoreTable($table)) {
            return true;
        }

        if ($this->excludeDeltaZendTables && 0 === strpos($table, 'delta_')) {
            return true;
        }

        if ($this->excludeDewdropTables && 0 === strpos($table, 'dewdrop_')) {
            return true;
        }

        return in_array($table, $this->excludedTables);
    }

    public function setWordPressPrefix($wordPressPrefix)
    {
        $this->wordPressPrefix = $wordPressPrefix;

        return $this;
    }

    public function excludeWordPressCoreTables($prefix)
    {
        $this->excludeWordPressCoreTables = true;

        $this->wordPressPrefix = $prefix;

        return $this;
    }

    public function includeWordPressCoreTables()
    {
        $this->excludeWordPressCoreTables = false;

        return $this;
    }

    public function excludeDeltaZendTables()
    {
        $this->excludeDeltaZendTables = true;

        return $this;
    }

    public function includeDeltaZendTables()
    {
        $this->excludeDeltaZendTables = false;

        return $this;
    }

    public function excludeDewdropTables()
    {
        $this->excludeDewdropTables = true;

        return $this;
    }

    public function includeDewdropTables()
    {
        $this->excludeDewdropTables = false;

        return $this;
    }

    public function isWordPressCoreTable($table)
    {
        if (!$this->wordPressPrefix) {
            throw new \Exception('WordPress prefix must be set before checking for core tables.');
        }

        $coreTableNames = [
            'posts',
            'comments',
            'links',
            'options',
            'postmeta',
            'terms',
            'term_taxonomy',
            'term_relationships',
            'termmeta',
            'commentmeta'
        ];

        foreach ($coreTableNames as $coreTableName) {
            $pattern = sprintf(
                '/^%s.*%s$/',
                preg_quote($this->wordPressPrefix, '/'),
                preg_quote($coreTableName, '/')
            );

            if (preg_match($pattern, $table)) {
                return true;
            }
        }

        return false;
    }
}
