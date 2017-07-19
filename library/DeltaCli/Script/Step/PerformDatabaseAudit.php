<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Console\Output\Spinner;
use DeltaCli\DatabaseAuditConfig;
use DeltaCli\Environment;
use DeltaCli\Host;
use Cocur\Slugify\Slugify;

class PerformDatabaseAudit extends EnvironmentHostsStepAbstract
{
    /**
     * @var array
     */
    private $createdByColumnNames = ['created_by', 'created_by_user_id'];

    /**
     * @var array
     */
    private $updatedByColumnNames = ['updated_by', 'updated_by_user_id'];

    /**
     * @var array
     */
    private $dateCreatedColumnNames = ['created_at', 'date_created', 'datetime_created'];

    /**
     * @var array
     */
    private $dateUpdatedColumnNames = ['updated_at', 'date_updated', 'datetime_updated'];

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var DatabaseAuditConfig
     */
    private $config;

    public function __construct(DatabaseInterface $database, DatabaseAuditConfig $config)
    {
        $this->database = $database;
        $this->config   = $config->generateConfigForSpecificDatabase($database);

        $this->limitToOnlyFirstHost();
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();

        $this->database->setSshTunnel($tunnel);

        $spinner = Spinner::forStep($this, $host);

        $output  = [];
        $changes = [];

        foreach ($this->database->getTableNames() as $tableName) {
            $spinner->spin("Auditing {$tableName}");

            if ($this->config->tableIsExcluded($tableName)) {
                $output[] = sprintf('<fg=cyan>Skipped %s because it was excluded in audit config.</>', $tableName);
                continue;
            }

            $columns     = $this->database->getColumns($tableName);
            $columnNames = [];

            foreach ($columns as $column) {
                $columnNames[] = $column['name'];
            }

            if (!array_intersect($columnNames, $this->createdByColumnNames)) {
                $changes[] = sprintf('<fg=red>Missing created by column on %s</>', $tableName);
            }

            if (!array_intersect($columnNames, $this->updatedByColumnNames)) {
                $changes[] = sprintf('<fg=red>Missing updated by column on %s</>', $tableName);
            }

            if (!array_intersect($columnNames, $this->dateUpdatedColumnNames)) {
                $changes[] = sprintf('<fg=red>Missing date updated column on %s</>', $tableName);
            }

            if (!array_intersect($columnNames, $this->dateCreatedColumnNames)) {
                $changes[] = sprintf('<fg=red>Missing date created column on %s</>', $tableName);
            }
        }

        $spinner->clear();

        $tunnel->tearDown();

        return [
            array_merge($output, $changes),
            (0 === count($changes) ? 0 : 1)
        ];
    }

    public function getName()
    {
        $slugify = new Slugify();

        return sprintf(
            'perform-db-audit-on-%s-from-%s',
            $slugify->slugify($this->database->getDatabaseName()),
            $this->environment->getName()
        );
    }

    private function generateChangeFileName(Environment $environment)
    {
        $slugify = new Slugify();

        return sprintf(
            'suggested-changes-to-%s-on-%s-based-on-audit-performed-%s.sql',
            $slugify->slugify($this->database->getDatabaseName()),
            $environment->getName(),
            date('Ymd-hiA')
        );
    }
}
