<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exec;
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

    /**
     * @var bool
     */
    private $excludeVcs = true;

    /**
     * @var bool
     */
    private $excludeOsCruft = true;

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

    public function includeVcs()
    {
        $this->excludeVcs = false;

        return $this;
    }

    public function excludeVcs()
    {
        $this->excludeVcs = true;

        return $this;
    }

    public function includeOsCruft()
    {
        $this->excludeOsCruft = false;

        return $this;
    }

    public function excludeOsCruft()
    {
        $this->excludeOsCruft = true;

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
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $command = sprintf(
            'rsync %s %s %s -az --no-p -i -e %s %s %s@%s:%s 2>&1',
            (self::DRY_RUN === $this->mode ? '--dry-run' : ''),
            $this->assembleExcludeArgs(),
            ($this->delete ? '--delete' : ''),
            escapeshellarg($tunnel->getCommand()),
            escapeshellarg($this->normalizePath($this->localPath)),
            escapeshellarg($tunnel->getUsername()),
            escapeshellarg($tunnel->getHostname()),
            escapeshellarg($this->normalizePath($this->remotePath))
        );

        Exec::run($command, $output, $exitStatus);

        $tunnel->tearDown();

        return [$this->filterItemizedOutput($output), $exitStatus];
    }

    protected function getSuccessfulResultExplanation(array $hosts)
    {
        $message = parent::getSuccessfulResultExplanation($hosts);
        $message .= (self::DRY_RUN === $this->mode ? ' in dry run mode' : '');
        return $message;
    }

    private function filterItemizedOutput(array $rawOutput)
    {
        $filteredOutput = [];

        foreach ($rawOutput as $line) {
            $change = $this->parseChangeFromLine($line);

            if ($this->outputChangeIsOnlyTimeChange($change) || $this->outputChangeIsSymlink($change)) {
                continue;
            } elseif ($this->outputChangeIsNewFile($change)) {
                $filteredOutput[] = $this->replaceChangeWithNewPrefixOnLine($line, 'New File');
            } elseif ($this->outputChangeIsNewDirectory($change)) {
                $filteredOutput[] = $this->replaceChangeWithNewPrefixOnLine($line, 'New Dir');
            } elseif ($this->outputChangeIsFileUpdate($change)) {
                $filteredOutput[] = $this->replaceChangeWithNewPrefixOnLine($line, 'Update');
            } elseif ($this->outputChangeIsDeletion($change)) {
                $filteredOutput[] = $this->replaceChangeWithNewPrefixOnLine($line, 'Delete');
            } else {
                $filteredOutput[] = $line;

            }
        }

        return $filteredOutput;
    }

    private function normalizePath($path)
    {
        return rtrim($path, '/') . '/';
    }

    private function assembleExcludeArgs()
    {
        $args = [];

        if ($this->excludeVcs) {
            $args[] = '--cvs-exclude';
            $args[] = '--exclude=.git';
        }

        if ($this->excludeOsCruft) {
            $args[] = '--exclude=.DS_Store';
            $args[] = '--exclude=.Spotlight-V100';
            $args[] = '--exclude=.Trashes';
            $args[] = '--exclude=ehthumbs.db';
            $args[] = '--exclude=Thumbs.db';
        }

        foreach ($this->excludes as $exclude) {
            $args[] = sprintf('--exclude=%s', escapeshellarg($exclude));
        }

        return implode(' ', $args);
    }

    private function outputChangeIsOnlyTimeChange($change)
    {
        return 'd..t....' === $change || 'f..t....' === $change;
    }

    private function outputChangeIsSymlink($change)
    {
        return 'L' === substr($change, 1, 1);
    }

    private function outputChangeIsNewFile($change)
    {
        return 'f+++++++' === $change;
    }

    private function outputChangeIsNewDirectory($change)
    {
        return 'd+++++++' === $change;
    }

    private function outputChangeIsFileUpdate($change)
    {
        return 'f.s' === substr($change, 0, 3);
    }

    private function outputChangeIsDeletion($change)
    {
        return 'deleting' === $change;
    }

    private function parseChangeFromLine($line, $includeDirection = false)
    {
        $line   = trim($line);
        $change = substr($line, 0, strpos($line, ' '));

        if (!$includeDirection) {
            $change = substr($change, 1);
        }

        return $change;
    }

    private function replaceChangeWithNewPrefixOnLine($line, $newPrefix)
    {
        $dividerPosition = strpos($line, ' ');

        return sprintf(
            '%-8s %s',
            substr($newPrefix, 0, $dividerPosition),
            substr($line, $dividerPosition)
        );
    }
}
