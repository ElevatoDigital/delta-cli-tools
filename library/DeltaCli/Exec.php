<?php

namespace DeltaCli;

use Symfony\Component\Stopwatch\Stopwatch;

class Exec
{
    /**
     * @var Exec
     */
    private static $instance;

    public static function run($command, &$output, &$exitStatus)
    {
        if (!self::$instance) {
            self::$instance = new Exec();
        }

        self::$instance->runCommand($command, $output, $exitStatus);
    }

    public static function getCommandRunner()
    {
        if (!self::$instance) {
            self::$instance = new Exec();
        }

        return function ($command, &$output, &$exitStatus) {
            self::$instance->runCommand($command, $output, $exitStatus);
        };
    }

    public function runCommand($command, &$output, &$exitStatus)
    {
        Debug::log("Running `{$command}`...");

        $stopwatch = new Stopwatch();
        $stopwatch->start('exec');

        exec($command, $output, $exitStatus);

        $event       = $stopwatch->stop('exec');
        $timeElapsed = $event->getDuration();

        Debug::log("Exited with {$exitStatus} status after {$timeElapsed}ms");
    }
}
