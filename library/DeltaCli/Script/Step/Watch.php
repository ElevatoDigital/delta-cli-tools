<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exception\FseventsExtensionNotInstalled;
use DeltaCli\Exec;
use DeltaCli\Project;
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
     * @var Project
     */
    private $project;

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

    /**
     * @var bool
     */
    private $stopOnFailure = false;

    /**
     * @var bool
     */
    private $onlyNotifyOnFailure = false;

    public function __construct(ScriptObject $script)
    {
        $this->script  = $script;
        $this->project = $script->getProject();
        $this->input   = $this->project->getInput();
        $this->output  = $this->project->getOutput();
    }

    public function getName()
    {
        return 'watch';
    }

    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function setStopOnFailure($stopOnFailure)
    {
        $this->stopOnFailure = $stopOnFailure;

        return $this;
    }

    public function setOnlyNotifyOnFailure($onlyNotifyOnFailure)
    {
        $this->onlyNotifyOnFailure = $onlyNotifyOnFailure;

        return $this;
    }

    public function run()
    {
        if (!extension_loaded('fsevents')) {
            throw new FseventsExtensionNotInstalled('fsevents module is required to watch for filesystem chagnes.');
        }

        $previousRunFailed = false;

        foreach ($this->paths as $path) {
            fsevents_add_watch(
                $path,
                function () use (&$previousRunFailed) {
                    $scriptStep = new ScriptStep($this->script, $this->input);
                    $result     = $scriptStep->run();

                    $result->render($this->output);

                    // Display notifications on OS X
                    if (!$this->onlyNotifyOnFailure || $result->isFailure() || $previousRunFailed) {
                        Exec::run(
                            sprintf(
                                'osascript -e %s',
                                escapeshellarg(
                                    sprintf(
                                        'display notification "%s" with title "%s %s"',
                                        $result->getMessageText(),
                                        $this->project->getName(),
                                        $this->script->getName()
                                    )
                                )
                            ),
                            $output,
                            $exitStatus
                        );
                    }

                    if ($result->isFailure()) {
                        $previousRunFailed = true;
                    } else {
                        $previousRunFailed = false;
                    }
                }
            );
        }

        if (count($this->paths)) {
            fsevents_start();
        }

        return new Result($this, Result::SUCCESS);
    }
}
