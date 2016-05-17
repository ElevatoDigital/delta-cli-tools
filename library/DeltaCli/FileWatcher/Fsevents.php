<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\Exec;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Fsevents implements FileWatcherInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    public function addWatch(array $paths, Script $script, $onlyNotifyOnFailure, $stopOnFailure)
    {
        $callback = FileWatcherFactory::createWatchCallback($this, $script, $onlyNotifyOnFailure, $stopOnFailure);

        foreach ($paths as $path) {
            /* @noinspection PhpUndefinedFunctionInspection */
            fsevents_add_watch($path, $callback);
        }

        return $this;
    }

    public function displayNotification(Script $script, Result $result)
    {
        Exec::run(
            sprintf(
                'osascript -e %s',
                escapeshellarg(
                    sprintf(
                        'display notification "%s" with title "%s %s"',
                        $result->getMessageText(),
                        $script->getProject()->getName(),
                        $script->getName()
                    )
                )
            ),
            $output,
            $exitStatus
        );
    }

    public function startLoop()
    {
        /* @noinspection PhpUndefinedFunctionInspection */
        fsevents_start();
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }
}
