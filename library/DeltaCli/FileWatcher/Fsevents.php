<?php

namespace DeltaCli\FileWatcher;

use DeltaCli\Exec;
use DeltaCli\Script;
use DeltaCli\Script\Step\Result;
use React\ChildProcess\Process as ChildProcess;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\Timer\TimerInterface;
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

    /**
     * @var array
     */
    private $watches = [];

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    public function addWatch(array $paths, Script $script, $onlyNotifyOnFailure, $stopOnFailure)
    {
        $callback = FileWatcherFactory::createWatchCallback($this, $script, $onlyNotifyOnFailure, $stopOnFailure);

        $this->watches[] = [
            'paths' => array_map(
                function ($input) {
                    return rtrim(realpath($input), '/');
                },
                $paths
            ),
            'callback' => $callback
        ];

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
        $loop = EventLoopFactory::create();

        exec(sprintf('chmod +x %s', escapeshellarg(__DIR__ . '/fsevents-util/fsevents')));

        $childProcess = new ChildProcess($this->assembleFseventsCommand());

        $childProcess->on(
            'exit',
            function () {
            }
        );

        $childProcess->start($loop);

        $childProcess->stdout->on(
            'data',
            function ($processOutput)  {
                $paths = explode(PHP_EOL, $processOutput);

                foreach ($paths as $path) {
                    $path = rtrim(realpath(trim($path)), '/');

                    foreach ($this->watches as $watch) {
                        $matches = false;

                        foreach ($watch['paths'] as $watchPath) {
                            if (0 === strpos($path, $watchPath)) {
                                $matches = true;
                                break;
                            }
                        }

                        if ($matches) {
                            /* @var callable $callback */
                            $callback = $watch['callback'];
                            $callback($path);
                        }
                    }
                }
            }
        );

        $loop->run();
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }

    private function assembleFseventsCommand()
    {
        $paths = [];

        foreach ($this->watches as $watch) {
            foreach ($watch['paths'] as $path) {
                $paths[] = $path;
            }
        }

        $paths = array_unique($paths);

        return sprintf(
            __DIR__ . '/fsevents-util/fsevents %s',
            implode(' ', array_map('escapeshellarg', $paths))
        );
    }
}
