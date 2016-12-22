<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use React\EventLoop\Factory as EventLoopFactory;

class SshWatchLogs extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'ssh:watch-logs',
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
                    $loop = EventLoopFactory::create();

                    foreach ($findLogsStep->getLogs() as $log) {
                        $log->attachToEventLoop($loop, $this->getProject()->getOutput());
                    }

                    $loop->run();
                }
            );
    }
}
