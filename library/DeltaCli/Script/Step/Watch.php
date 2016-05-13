<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exec;
use DeltaCli\Script as ScriptObject;
use DeltaCli\Script\Step\Script as ScriptStep;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Watch extends StepAbstract
{
    /**
     * @var ScriptObject
     */
    private $script;

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
    private $paths = [];

    public function __construct(ScriptObject $script, InputInterface $input, OutputInterface $output)
    {
        $this->script = $script;
        $this->input  = $input;
        $this->output = $output;
    }

    public function getName()
    {
        return 'watch';
    }

    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function run()
    {
        if (!extension_loaded('fsevents')) {
            throw new \Exception('fsevents module is required to watch for filesystem chagnes.');
        }

        foreach ($this->paths as $path) {
            fsevents_add_watch(
                $path,
                function () {
                    $scriptStep = new ScriptStep($this->script, $this->input);
                    $result     = $scriptStep->run();

                    $result->render($this->output);

                    // Display notifications on OS X
                    Exec::run(
                        sprintf(
                            'osascript -e %s',
                            escapeshellarg(
                                sprintf(
                                    'display notification "%s" with title "%s"',
                                    $result->getMessageText(),
                                    $this->script->getName()
                                )
                            )
                        ),
                        $output,
                        $exitStatus
                    );
                }
            );
        }

        if (count($this->paths)) {
            fsevents_start();
        }

        return new Result($this, Result::SUCCESS);
    }
}
