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
     * @var bool
     */
    private $delete = false;

    /**
     * @var array
     */
    private $excludes = [];

    public function __construct($localPath, $remotePath)
    {
        $this->localPath  = $localPath;
        $this->remotePath = $remotePath;
    }

    public function delete()
    {
        $this->delete = true;

        return $this;
    }

    public function exclude($path)
    {
        $this->excludes[] = $path;

        return $this;
    }

    public function run()
    {
        return $this->runWithMode(self::LIVE);
    }

    public function dryRun()
    {
        return $this->runWithMode(self::DRY_RUN);
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

    private function runWithMode($mode)
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

            list($hostOutput, $exitStatus) = $this->rsync($host, $mode);

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
            $result->setExplanation(
                'on all ' . count($this->environment->getHosts()) . ' host(s)' .
                (self::DRY_RUN === $mode ? ' in dry run mode' : '')
            );
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

    private function rsync(Host $host, $mode)
    {
        $command = sprintf(
            'rsync %s %s %s -az -i -e %s %s %s@%s:%s 2>&1',
            (self::DRY_RUN === $mode ? '--dry-run' : ''),
            $this->assembleExcludeArgs(),
            ($this->delete ? '--delete' : ''),
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
