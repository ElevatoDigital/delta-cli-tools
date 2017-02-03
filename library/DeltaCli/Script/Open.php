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
        $cacheKey   = sprintf('%s_browser_url', $this->getEnvironment()->getName());

        $this
            ->addStep(
                'detect-browser-url',
                function () use (&$browserUrl, $cacheKey) {
                    if (!($browserUrl = $this->getProject()->getCache()->fetch($cacheKey))) {
                        $browserUrl = $this->detectBrowserUrl();
                    }

                    if ($browserUrl) {
                        return new Result($this->getStep('detect-browser-url'), Result::SUCCESS);
                    } else {
                        $envName = $this->getEnvironment()->getName();

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
            )
            ->addStep(
                'refresh-browser-url-cache',
                function () use ($cacheKey) {
                    $this->getProject()->getCache()->store(
                        $cacheKey,
                        $this->detectBrowserUrl()
                    );
                }
            );
    }

    private function detectBrowserUrl()
    {
        $configFactory = new ConfigFactory($this->getProject()->getCache());
        $environment   = $this->getEnvironment();
        $hosts         = $environment->getHosts();
        $host          = reset($hosts);
        $configs       = $configFactory->detectConfigsOnHost($host);
        $browserUrl    = null;

        foreach ($configs as $config) {
            if ($config->hasBrowserUrl()) {
                $browserUrl = $config->getBrowserUrl();
                break;
            }
        }

        return $browserUrl;
    }
}