<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use Cocur\Slugify\Slugify;
use DeltaCli\Script as ScriptObject;

class GenerateDatabaseDiagram extends StepAbstract
{
    /**
     * @var DatabaseInterface
     */
    private $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function preRun(ScriptObject $script)
    {
        $this->checkIfExecutableExists('dot', 'dot -V');
    }

    public function run()
    {
        $tableNodes = [];
        $references = [];

        foreach ($this->database->getTableNames() as $tableName) {
            $tableNodes[] = $this->renderTableNode($tableName);
            $references[] = $this->renderReferencesForTable($tableName);
        }

        $filename = uniqid('delta-cli-db-diagram', true);

        file_put_contents("/tmp/{$filename}.dot", $this->renderDiagram($tableNodes), LOCK_EX);

        $command = sprintf(
            'dot -T pdf /tmp/%s.dot -o /tmp/%s.pdf 2>&1',
            $filename,
            $filename
        );

        $this->exec($command, $output, $exitStatus);

        unlink("/tmp/{$filename}.dot");

        passthru("open /tmp/{$filename}.pdf");
    }

    public function getName()
    {
        $slugify = new Slugify();

        return 'generate-database-diagram-for-' . $slugify->slugify($this->database->getDatabaseName());
    }

    private function renderTableNode($tableName)
    {
        $columns = $this->database->getColumns($tableName);

        ob_start();
        require __DIR__ . '/templates/database-table.phtml';
        return ob_get_clean();
    }

    private function renderDiagram(array $tableNodes)
    {
        ob_start();
        require __DIR__ . '/templates/database-diagram.phtml';
        return ob_get_clean();
    }

    private function renderReferencesForTable($tableName)
    {
        $references = $this->database->getForeignKeys($tableName);

        ob_start();
        require __DIR__ . '/templates/database-references.phtml';
        return ob_get_clean();
    }
}