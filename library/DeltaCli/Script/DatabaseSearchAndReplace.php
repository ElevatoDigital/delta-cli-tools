<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class DatabaseSearchAndReplace extends Script
{
    /**
     * @var string
     */
    private $searchString;

    /**
     * @var string
     */
    private $replacementString;

    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

    /**
     * @var Script\Step\GenerateSearchAndReplaceSql
     */
    private $generateSearchAndReplaceSqlStep;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:search-and-replace',
            'Search for and replace a string throughout a database.'
        );
    }

    protected function configure()
    {
        parent::configure();

        $this->requireEnvironment();

        $this->addSetterArgument(
            'search-string',
            InputArgument::REQUIRED,
            'The string you want to search for.'
        );

        $this->addSetterArgument(
            'replacement-string',
            InputArgument::REQUIRED,
            'The string you want to replace it with.'
        );

        $this->findDbsStep = $this->getProject()->findDatabases();
        $this->findDbsStep->configure($this->getDefinition());
    }

    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;

        return $this;
    }

    public function setReplacementString($replacementString)
    {
        $this->replacementString = $replacementString;

        return $this;
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                $this->getProject()->sanityCheckPotentiallyDangerousOperation(
                    'Perform search and replace on database contents.'
                )
            )
            ->addStep($this->findDbsStep)
            ->addStep(
                'generate-search-and-replace-sql',
                function () {
                    $this->generateSearchAndReplaceSqlStep = $this->getProject()->generateSearchAndReplaceSql(
                        $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput()),
                        $this->searchString,
                        $this->replacementString
                    );

                    $this->generateSearchAndReplaceSqlStep->setSelectedEnvironment($this->getEnvironment());

                    return $this->generateSearchAndReplaceSqlStep->run();
                }
            )
            ->addStep(
                'apply-sql-file',
                function () {
                    if (file_exists($this->generateSearchAndReplaceSqlStep->getSqlPath())) {
                        $restoreStep = $this->getProject()->restoreDatabase(
                            $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput()),
                            $this->generateSearchAndReplaceSqlStep->getSqlPath()
                        );

                        $restoreStep->setSelectedEnvironment($this->getEnvironment());

                        return $restoreStep->run();
                    }
                }
            )
            ->addStep(
                'remove-sql-file',
                function () {
                    if (file_exists($this->generateSearchAndReplaceSqlStep->getSqlPath())) {
                        unlink($this->generateSearchAndReplaceSqlStep->getSqlPath());
                    }
                }
            );
    }
}
