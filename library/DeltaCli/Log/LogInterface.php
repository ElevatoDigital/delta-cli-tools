<?php

namespace DeltaCli\Log;

use DeltaCli\Host;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface LogInterface
{
    const WATCH_BY_DEFAULT = true;

    const DONT_WATCH_BY_DEFAULT = false;

    /**
     * @return Host
     */
    public function getHost();

    /**
     * @return string
     */
    public function getName();

    /**
     * A simple description of where the log can be found or where it's data is located.  Typically either a file path
     * or a DB table.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Whether the log should be watched by default when running the ssh:watch-logs command.  This should be turned off
     * for very high-volume logs that would add more noise than signal.
     *
     * @return boolean
     */
    public function getWatchByDefault();

    /**
     * Do whatever you need to do to get the log attached to the React event loop so it can be watched.
     *
     * @return void
     */
    public function attachToEventLoop(LoopInterface $loop, OutputInterface $output);

    /**
     * Stop any timers or processes used by this watcher.
     *
     * @return mixed
     */
    public function stop();
}
