<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\Exception\NoCompatibleFileWatcherExtensionAvailable;
use DeltaCli\Script;
use DeltaCli\Script\Step\Script as ScriptStep;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FileWatcherFactory
{
    public static function factory(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('fsevents')) {
            return new Fsevents($input, $output);
        } else if (extension_loaded('inotify')) {
            return new Inotify($input, $output);
        } else {
            throw new NoCompatibleFileWatcherExtensionAvailable();
        }
    }

    public static function createWatchCallback(
        FileWatcherInterface $fileWatcher,
        Script $script,
        $onlyNotifyOnFailure,
        $stopOnFailure
    ) {
        $previousRunFailed = false;

        return function () use (&$previousRunFailed, $fileWatcher, $script, $onlyNotifyOnFailure, $stopOnFailure) {
            $timestamp = date('Y-m-d G:i:s');

            $fileWatcher->getOutput()->writeln(
                "<comment>Running {$script->getName()} script at {$timestamp}...</comment>"
            );

            $scriptStep = new ScriptStep($script, $fileWatcher->getInput());

            if ($script->getEnvironment()) {
                $scriptStep->setSelectedEnvironment($script->getEnvironment());
            }

            $result = $scriptStep->run();

            $result->render($fileWatcher->getOutput());

            if (!$onlyNotifyOnFailure || $result->isFailure() || $previousRunFailed) {
                $fileWatcher->displayNotification($script, $result);
            }

            if ($result->isFailure()) {
                $previousRunFailed = true;
            } else {
                $previousRunFailed = false;
            }
        };
    }
}