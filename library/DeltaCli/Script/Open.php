<?php

namespace DeltaCli\Script;

use DeltaCli\Config\ConfigFactory;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;

class Open extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'open',
            'Open your environment in a browser.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();
        parent::configure();
    }

    protected function addSteps()
    {
        $browserUrl = null;

        $this
            ->addStep(
                'detect-browser-url',
                function () use (&$browserUrl) {
                    $configFactory = new ConfigFactory($this->getProject()->getCache());
                    $environment   = $this->getEnvironment();
                    $hosts         = $environment->getHosts();
                    $host          = reset($hosts);
                    $configs       = $configFactory->detectConfigsOnHost($host);

                    foreach ($configs as $config) {
                        if ($config->hasBrowserUrl()) {
                            $browserUrl = $config->getBrowserUrl();
                            break;
                        }
                    }

                    if ($browserUrl) {
                        return new Result($this->getStep('detect-browser-url'), Result::SUCCESS);
                    } else {
                        $envName = $environment->getName();

                        return new Result(
                            $this->getStep('detect-browser-url'),
                            Result::FAILURE,
                            [
                                "Delta CLI could not find the browser URL for the {$envName} environment.",
                                'You can specify one manually in your delta-cli.php.',
                                "  \$project->getEnvironment('{$envName}')->getManualConfig()->setBrowserUrl"
                                . "('example.org');"
                            ]
                        );
                    }
                }
            )
            ->addStep(
                'open-browser',
                function () use (&$browserUrl) {
                    exec(
                        sprintf('open %s', escapeshellarg($browserUrl)),
                        $output,
                        $exitStatus
                    );

                    if (127 === (int) $exitStatus) {
                        exec(
                            sprintf('xdg-open %s &> /dev/null', escapeshellarg($browserUrl)),
                            $output,
                            $exitStatus
                        );
                    }
                }
            );
    }
}