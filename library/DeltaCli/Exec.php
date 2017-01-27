<?php

namespace DeltaCli;

use DeltaCli\Console\Output\Spinner;
use React\ChildProcess\Process as ChildProcess;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class Exec
{
    /**
     * @var Exec
     */
    private static $instance;

    public static function run($command, &$output, &$exitStatus, Spinner $spinner = null)
    {
        if (!self::$instance) {
            self::$instance = new Exec();
        }

        self::$instance->runCommand($command, $output, $exitStatus, $spinner);
    }

    public static function getCommandRunner()
    {
        if (!self::$instance) {
            self::$instance = new Exec();
        }

        return function ($command, &$output, &$exitStatus, Spinner $spinner = null) {
            self::$instance->runCommand($command, $output, $exitStatus, $spinner);
        };
    }

    public static function resetInstance()
    {
        self::$instance = null;
    }

    public function runCommand($command, &$output, &$exitStatus, Spinner $spinner = null)
    {
        Debug::log("Running `{$command}`...");

        $stopwatch = new Stopwatch();
        $stopwatch->start('exec');

        $loop   = EventLoopFactory::create();
        $output = [];

        $childProcess = new ChildProcess($command);


        $childProcess->on(
            'exit',
            function ($processExitStatus) use (&$exitStatus, $loop, $spinner) {
                $exitStatus = $processExitStatus;
                $loop->stop();

                if ($spinner) {
                    $spinner->clear();
                }
            }
        );

        $loop->addTimer(
            0.001,
            function (TimerInterface $timer) use ($childProcess, &$output) {
                $childProcess->start($timer->getLoop());

                $childProcess->stdout->on(
                    'data',
                    function ($processOutput) use (&$output) {
                        $output[] = $processOutput;
                    }
                );

                $childProcess->stderr->on(
                    'data',
                    function ($processOutput) use (&$output) {
                        $output[] = $processOutput;
                    }
                );
            }
        );

        if ($spinner) {
            $loop->addPeriodicTimer(
                0.25,
                function () use ($spinner) {
                    $spinner->spin();
                }
            );
        }

        $loop->run();

        $exitStatus  = (int) $exitStatus;
        $event       = $stopwatch->stop('exec');
        $timeElapsed = $event->getDuration();

        Debug::log("Exited with {$exitStatus} status after {$timeElapsed}ms");
    }
}
