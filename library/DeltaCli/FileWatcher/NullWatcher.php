<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\Exception\NoCompatibleFileWatcherExtensionAvailable;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;

class NullWatcher implements FileWatcherInterface
{
    public function addWatch(array $paths, Script $script, $onlyNotifyOnFailure, $stopOnFailure)
    {
        throw new NoCompatibleFileWatcherExtensionAvailable();
    }

    public function displayNotification(Script $script, Result $result)
    {
        throw new NoCompatibleFileWatcherExtensionAvailable();
    }

    public function startLoop()
    {
        throw new NoCompatibleFileWatcherExtensionAvailable();
    }

    public function getInput()
    {
        throw new NoCompatibleFileWatcherExtensionAvailable();
    }

    public function getOutput()
    {
        throw new NoCompatibleFileWatcherExtensionAvailable();
    }
}
