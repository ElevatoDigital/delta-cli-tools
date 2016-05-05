<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;

class Rsync extends EnvironmentHostsStepAbstract implements DryRunInterface
{
    const LIVE = 'live';

    const DRY_RUN = 'dry-run';

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

    /**
     * @var string
     */
    private $mode = self::LIVE;

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
        $this->mode = self::LIVE;
        return $this->runOnAllHosts();
    }

    public function dryRun()
    {
        $this->mode = self::DRY_RUN;
        return $this->runOnAllHosts();
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

    public function runOnHost(Host $host)
    {
        $command = sprintf(
            'rsync %s %s %s -az --no-p -i -e %s %s %s@%s:%s 2>&1',
            (self::DRY_RUN === $this->mode ? '--dry-run' : ''),
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

    protected function getSuccessfulResultExplanation(array $hosts)
    {
        $message = parent::getSuccessfulResultExplanation($hosts);
        $message .= (self::DRY_RUN === $this->mode ? ' in dry run mode' : '');
        return $message;
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
