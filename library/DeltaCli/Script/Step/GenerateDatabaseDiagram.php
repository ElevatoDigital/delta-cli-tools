<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Config\Database\DatabaseInterface;
use Cocur\Slugify\Slugify;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDatabaseDiagram extends StepAbstract
{
    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var int
     */
    private $edgeId = 0;

    public function __construct(DatabaseInterface $database, OutputInterface $output)
    {
        $this->database = $database;
        $this->output   = $output;
    }

    public function preRun(ScriptObject $script)
    {
        $this->checkIfExecutableExists('dot', 'dot -V');
    }

    public function run()
    {
        $tables = $this->database->getTableNames();

        $tableNodes      = [];
        $referencesNodes = [];

        $progressBar = new ProgressBar($this->output, count($tables));

        foreach ($tables as $tableName) {
            $tableNodes[]      = $this->renderTableNode($tableName);
            $referencesNodes[] = $this->renderReferencesForTable($tableName);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->output->writeln(PHP_EOL);

        $filename = uniqid('delta-cli-db-diagram', true);

        file_put_contents("/tmp/{$filename}.dot", $this->renderDiagram($tableNodes, $referencesNodes), LOCK_EX);

        $command = sprintf(
            'dot -T pdf /tmp/%s.dot -o /tmp/%s.pdf 2>&1',
            $filename,
            $filename
        );

        $this->exec($command, $output, $exitStatus);

        if ($exitStatus) {
            return new Result($this, Result::FAILURE, $output);
        } else {
            unlink("/tmp/{$filename}.dot");
            $command = "open /tmp/{$filename}.pdf";
            if(defined('SHELL_WRAPPER')){
                $command = sprintf(SHELL_WRAPPER,escapeshellcmd($command));
            }
            passthru($command);
        }
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

    private function renderDiagram(array $tableNodes, array $referenceNodes)
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