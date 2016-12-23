<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use React\EventLoop\Factory as EventLoopFactory;

class LogsWatch extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'logs:watch',
            'Watch the logs on a remote environment.'
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
                'watch-logs',
                function () use ($findLogsStep) {
                    $loop   = EventLoopFactory::create();
                    $output = $this->getProject()->getOutput();

                    foreach ($findLogsStep->getLogs() as $log) {
                        if ($log->getWatchByDefault()) {
                            $log->attachToEventLoop($loop, $this->getProject()->getOutput());
                        } else {
                            $output->writeln(
                                sprintf(
                                    '<comment>Skipping %s because it is not watched by default.</comment>',
                                    $log->getName()
                                )
                            );
                        }
                    }

                    $loop->run();
                }
            );
    }
}
