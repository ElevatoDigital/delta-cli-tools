<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Host;

class Scp extends StepAbstract implements EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $localFile;

    /**
     * @var string
     */
    private $remoteFile;

    public function __construct($localFile, $remoteFile)
    {
        $this->localFile  = $localFile;
        $this->remoteFile = $remoteFile;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return sprintf(
                'scp-%s-to-remote',
                basename($this->localFile)
            );
        }
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function run()
    {
        $output = [];

        $failedHosts        = [];
        $misconfiguredHosts = [];

        /* @var $host Host */
        foreach ($this->environment->getHosts() as $host) {
            if (!$host->hasRequirementsForSshUse()) {
                $misconfiguredHosts[] = $host;
                continue;
            }

            list($hostOutput, $exitStatus) = $this->scp($host);

            if ($exitStatus) {
                $failedHosts[] = $host;
            }

            $output[] = $host->getHostname();

            foreach ($hostOutput as $line) {
                $output[] = '  ' . $line;
            }
        }

        if (count($this->environment->getHosts()) && !count($failedHosts) && !count($misconfiguredHosts)) {
            $result = new Result($this, Result::SUCCESS, $output);
            $result->setExplanation('on all ' . count($this->environment->getHosts()) . ' host(s)');
        } else {
            $result = new Result($this, Result::FAILURE, $output);

            if (!count($this->environment->getHosts())) {
                $result->setExplanation('because no hosts were added in the environment');
            } else {
                $explanations = [];

                if (count($failedHosts)) {
                    $explanations[] = count($failedHosts) . ' host(s) failed';
                }

                if (count($misconfiguredHosts)) {
                    $explanations[] = count($misconfiguredHosts) . ' host(s) were not configured for SSH';
                }

                $result->setExplanation('because ' . implode(' and ', $explanations));
            }
        }

        return $result;
    }

    private function scp(Host $host)
    {
        $command = sprintf(
            'scp %s -P %s %s %s %s@%s:%s 2>&1',
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($host->getSshPort()),
            (is_dir($this->localFile) ? '-r' : ''),
            escapeshellarg($this->localFile),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            escapeshellarg($this->remoteFile)
        );

        exec($command, $output, $exitStatus);

        return [$output, $exitStatus];
    }
}
