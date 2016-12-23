<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Helper\Table;

class LogsList extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'logs:list',
            'Find and list the logs on a remote environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();
        parent::configure();
    }

    protected function addSteps()
    {
        $findLogsStep = $this->getProject()->findLogs();

        $this
            ->addStep($findLogsStep)
            ->addStep(
                'list-logs',
                function () use ($findLogsStep) {
                    $table = new Table($this->getProject()->getOutput());

                    $table->setHeaders(['Name', 'Host', 'Location', 'Is Watched By Default?']);

                    foreach ($findLogsStep->getLogs() as $log) {
                        $table->addRow(
                            [
                                $log->getName(),
                                $log->getHost()->getHostname(),
                                $log->getDescription(),
                                $log->getWatchByDefault() ? 'Yes' : 'No'
                            ]
                        );
                    }

                    $table->render();
                }
            );
    }
}
