<?php

namespace DeltaCli;

use DeltaCli\Script\Step\ChangeSet;

class Rsync
{
    /**
     * @var string
     */
    private $sourcePath;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var array
     */
    private $excludes = [];

    /**
     * @var bool
     */
    private $excludeVcs = true;

    /**
     * @var bool
     */
    private $excludeOsCruft = true;

    /**
     * @var bool
     */
    private $delete = false;

    /**
     * @var bool
     */
    private $dryRun = false;

    /**
     * @var string
     */
    private $flags = '-az --no-p';

    /**
     * Rsync constructor.
     * @param string $sourcePath
     * @param string $destinationPath
     */
    public function __construct($sourcePath, $destinationPath)
    {
        $this->sourcePath      = $this->normalizePath($sourcePath);
        $this->destinationPath = $this->normalizePath($destinationPath);
    }

    public function setExcludes(array $excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    public function setDelete($delete)
    {
        $this->delete = $delete;

        return $this;
    }

    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    public function __toString()
    {
        $command   = 'rsync';
        $arguments = [];

        if ($this->dryRun) {
            $arguments[] = '--dry-run';
        }

        if ($this->delete) {
            $arguments[] = '--delete';
        }

        $arguments[] = $this->flags;
        $arguments[] = '-i';
        $arguments[] = $this->assembleExcludeArgs();

        return $command . implode(' ', $arguments);
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
            $args[] = '--exclude=google*.html';
        }

        foreach ($this->excludes as $exclude) {
            $args[] = sprintf('--exclude=%s', escapeshellarg($exclude));
        }

        return implode(' ', $args);
    }

    /**
     * @param array $rawOutput
     * @return ChangeSet
     */
    public function generateChangeSetFromOutput(array $rawOutput)
    {
        $changeSet = new ChangeSet();

        foreach ($rawOutput as $line) {
            $change = $this->parseChangeFromLine($line);
            $file   = $this->stripChangeFromLine($line);

            if ($this->outputChangeIsOnlyTimeChange($change) || $this->outputChangeIsSymlink($change)) {
                continue;
            } elseif ($this->outputChangeIsNewFile($change)) {
                $changeSet->newFile($file);
            } elseif ($this->outputChangeIsNewDirectory($change)) {
                $changeSet->newDirectory($file);
            } elseif ($this->outputChangeIsFileUpdate($change)) {
                $changeSet->update($file);
            } elseif ($this->outputChangeIsDeletion($change)) {
                $changeSet->delete($file);
            } else {
                $changeSet->update($file);
            }
        }

        return $changeSet;
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

    private function stripChangeFromLine($line)
    {
        $dividerPosition = strpos($line, ' ');
        return trim(substr($line, $dividerPosition));
    }

    private function normalizePath($path)
    {
        return rtrim($path, '/') . '/';
    }
}
