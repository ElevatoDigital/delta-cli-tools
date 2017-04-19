<?php

namespace DeltaCli\Script\Step;

use Cocur\Slugify\Slugify;
use DeltaCli\Console\Output\Spinner;
use DeltaCli\FileTransferPaths;
use DeltaCli\Host;
use DeltaCli\Script as ScriptObject;
use DeltaCli\SshTunnel;

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
     * @var string
     */
    private $direction;

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

    /**
     * @var bool
     */
    private $excludeCommonDeltaFiles = true;

    /**
     * @var string
     */
    private $flags = '-az --no-p';

    /**
     * @var Slugify
     */
    private $slugify;

    public function __construct($localPath, $remotePath, $direction = FileTransferPaths::UP)
    {
        $this->localPath  = $localPath;
        $this->remotePath = $remotePath;
        $this->direction  = $direction;
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

    public function includeCommonDeltaFiles()
    {
        $this->excludeCommonDeltaFiles = false;

        return $this;
    }

    public function excludeCommonDeltaFiles()
    {
        $this->excludeCommonDeltaFiles = true;

        return $this;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    public function preRun(ScriptObject $script)
    {
        $this->checkIfExecutableExists('rsync', 'rsync --version');
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
        } elseif (FileTransferPaths::UP === $this->direction) {
            return $this->getSlugify()->slugify(
                sprintf(
                    'rsync-%s-to-%s',
                    (basename(realpath($this->localPath)) ?: 'local'),
                    (basename($this->remotePath) ?: 'remote')
                )
            );
        } else {
            return $this->getSlugify()->slugify(
                sprintf(
                    'rsync-%s-to-%s',
                    (basename($this->remotePath) ?: 'remote'),
                    (basename(realpath($this->localPath)) ?: 'local')
                )
            );
        }
    }

    public function setSlugify(Slugify $slugify)
    {
        $this->slugify = $slugify;

        return $this;
    }

    public function getSlugify()
    {
        if (!$this->slugify) {
            $this->slugify = new Slugify();
        }

        return $this->slugify;
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $pathArguments = $this->assemblePathArguments($tunnel);

        $command = sprintf(
            'rsync %s %s %s %s -i -e %s %s %s 2>&1',
            (self::DRY_RUN === $this->mode ? '--dry-run' : ''),
            $this->assembleExcludeArgs(),
            ($this->delete ? '--delete' : ''),
            $this->flags,
            escapeshellarg($tunnel->getCommand()),
            $pathArguments[0],
            $pathArguments[1]
        );

        $this->execSsh($host, $command, $output, $exitStatus, Spinner::forStep($this, $host));

        $tunnel->tearDown();

        $changeSet = $this->generateChangeSetFromOutput($output);

        return [$changeSet->getOutput(), $exitStatus, $changeSet->getVerboseOutput()];
    }

    protected function getSuccessfulResultExplanation(array $hosts)
    {
        $message = parent::getSuccessfulResultExplanation($hosts);
        $message .= (self::DRY_RUN === $this->mode ? ' in dry run mode' : '');
        return $message;
    }

    private function generateChangeSetFromOutput(array $rawOutput)
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

    private function normalizePath($path)
    {
        return rtrim($path, '/') . '/';
    }

    private function assemblePathArguments(SshTunnel $tunnel)
    {
        $localArgument = escapeshellarg($this->normalizePath($this->localPath));

        $remoteArgument = sprintf(
            '%s@%s:%s',
            escapeshellarg($tunnel->getUsername()),
            escapeshellarg($tunnel->getHostname()),
            escapeshellarg($this->normalizePath($this->remotePath))
        );

        if (FileTransferPaths::UP === $this->direction) {
            $arguments = [$localArgument, $remoteArgument];
        } else {
            $arguments = [$remoteArgument, $localArgument];
        }

        return $arguments;
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

        if ($this->excludeCommonDeltaFiles) {
            $args[] = '--exclude=delta_maintenance_page.php';
            $args[] = '--exclude=dewdrop-config.local.php';
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

    private function stripChangeFromLine($line)
    {
        $dividerPosition = strpos($line, ' ');
        return trim(substr($line, $dividerPosition));
    }
}
