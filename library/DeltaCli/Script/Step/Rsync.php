<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Host;

class Rsync extends StepAbstract implements DryRunInterface, EnvironmentAwareInterface
{
    const LIVE = 'live';

    const DRY_RUN = 'dry-run';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $localPath;

    /**
     * @var string
     */
    private $remotePath;

    /**
     * @var array
     */
    private $excludes = [];

    public function __construct($localPath, $remotePath)
    {
        $this->localPath  = $localPath;
        $this->remotePath = $remotePath;
    }

    public function exclude($path)
    {
        $this->excludes[] = $path;

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

            list($hostOutput, $exitStatus) = $this->rsync($host, self::LIVE);

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

            if (!count($failedHosts) && count($misconfiguredHosts)) {
                $result->setExplanation('no hosts were added in the environment');
            } else {
                $explanations = [];

                if (count($failedHosts)) {
                    $explanations[] = count($failedHosts) . ' host(s) failed';
                }

                if (count($misconfiguredHosts)) {
                    $explanations[] = count($misconfiguredHosts) . ' host(s) were not configured for SSH';
                }

                $result->setExplanation(implode(' and ', $explanations));
            }
        }

        return $result;
    }

    public function dryRun()
    {

    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return sprintf(
                'rsync-%s-to-%s',
                basename(realpath($this->localPath)),
                (basename($this->remotePath) ?: 'remote')
            );
        }
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    private function rsync(Host $host, $mode)
    {
        $command = sprintf(
            'rsync %s %s -az -i -e %s %s %s@%s:%s 2>&1',
            (self::DRY_RUN === $mode ? '--dry-run' : ''),
            $this->assembleExcludeArgs(),
            escapeshellarg(sprintf('ssh -i %s -p %d', $host->getSshPrivateKey(), $host->getSshPort())),
            escapeshellarg($this->normalizePath($this->localPath)),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            escapeshellarg($this->normalizePath($this->remotePath))
        );

        exec($command, $output, $exitStatus);

        return [$output, $exitStatus];
    }

    private function normalizePath($path)
    {
        return rtrim($path, '/') . '/';
    }

    private function assembleExcludeArgs()
    {
        $args = [];

        foreach ($this->excludes as $exclude) {
            $args[] = sprintf('--exclude=%s', escapeshellarg($exclude));
        }

        return implode(' ', $args);
    }
}
