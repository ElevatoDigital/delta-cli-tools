<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\FileWatcher\Inotify\Watch;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Inotify implements FileWatcherInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var resource
     */
    private $inotify;

    /**
     * @var array
     */
    private $watches = [];

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input   = $input;
        $this->output  = $output;
        $this->inotify = inotify_init();

        stream_set_blocking($this->inotify, 0);
    }

    public function addWatch(array $paths, Script $script, $onlyNotifyOnFailure, $stopOnFailure)
    {
        $callback = FileWatcherFactory::createWatchCallback($this, $script, $onlyNotifyOnFailure, $stopOnFailure);

        $this->watches[] = Watch::factory($this->inotify, $paths, $callback);

        return $this;
    }

    public function displayNotification(Script $script, Result $result)
    {
        // TODO: Implement displayNotification() method.
    }

    public function startLoop()
    {
        while (true) {
            /** @noinspection PhpUndefinedFunctionInspection */
            $events = inotify_read($this->inotify);

            if ($events) {
                $this->triggerWatchesMatchingEvents($events);
            }

            usleep(50000);
        }
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }

    private function triggerWatchesMatchingEvents(array $events)
    {
        $watchesTriggered = [];

        foreach ($events as $event) {
            /* @var $watch Watch */
            foreach ($this->watches as $watch) {
                if (in_array(spl_object_hash($watch), $watchesTriggered)) {
                    continue;
                }

                if ($watch->matchesEvent($event)) {
                    $watch->runCallback();

                    $watchesTriggered[] = spl_object_hash($watch);
                }
            }
        }
    }
}
