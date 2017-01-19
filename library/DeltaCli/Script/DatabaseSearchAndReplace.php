<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
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
            ->addStep($this->findDbsStep)
            ->addStep(
                'search-and-replace',
                function () {
                    $hosts = $this->getEnvironment()->getHosts();
                    $host = reset($hosts);

                    $host->getSshTunnel()->setUp();

                    $database = $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput());
                    $database->setSshTunnel($host->getSshTunnel());

                    $tableNames  = $database->getTableNames();
                    $progressBar = new ProgressBar($this->getProject()->getOutput(), count($tableNames));

                    $numberOfReplacements = 0;

                    foreach ($tableNames as $table) {
                        $data = $database->query("SELECT * FROM {$table};");

                        foreach ($data as $row) {
                            foreach ($row as $column => $originalValue) {
                                $filteredValue = $this->recursiveUnserializeReplace(
                                    $this->searchString,
                                    $this->replacementString,
                                    $originalValue
                                );

                                if ($filteredValue !== $originalValue) {
                                    $numberOfReplacements += 1;
                                }
                            }
                        }

                        $progressBar->advance();
                    }

                    $progressBar->clear();

                    $host->getSshTunnel()->tearDown();

                    return new Result($this->findDbsStep, Result::SUCCESS, ["Replaced {$numberOfReplacements} times."]);
                }
            );
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
        } catch (Exception $error) {

        }

        return $data;
    }
}
