<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\Script;
use DeltaCli\Script\Step\Result;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface FileWatcherInterface
{
    /**
     * @param array $paths
     * @param Script $script
     * @param bool $onlyNotifyOnFailure
     * @param bool $stopOnFailure
     * @return mixed
     */
    public function addWatch(array $paths, Script $script, $onlyNotifyOnFailure, $stopOnFailure);

    public function displayNotification(Script $script, Result $result);

    public function startLoop();

    /**
     * @return InputInterface
     */
    public function getInput();

    /**
     * @return OutputInterface
     */
    public function getOutput();
}
