<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exception\InvalidStepResult;
use Symfony\Component\Console\Output\OutputInterface;

class Result
{
    const SUCCESS = 'success';

    const FAILURE = 'failure';

    const INVALID = 'invalid';

    const WARNING = 'warning';

    const SKIPPED = 'skipped';

    /**
     * @var StepInterface
     */
    private $step;

    /**
     * @var string
     */
    private $status;

    /**
     * @var array
     */
    private $output;

    /**
     * @var string
     */
    private $explanation;

    public function __construct(StepInterface $step, $status, $output = null)
    {
        if (!$this->statusIsValid($status)) {
            throw new InvalidStepResult("'{$status}'' is not a valid result for a script step.");
        }

        if (!is_array($output)) {
            if (trim($output)) {
                $output = explode(PHP_EOL, trim($output));
            } else {
                $output = [];
            }
        }

        $this->step   = $step;
        $this->status = $status;
        $this->output = $output;
    }

    public function setExplanation($explanation)
    {
        $this->explanation = $explanation;

        return $this;
    }

    public function render(OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<fg=%s>%s</>',
                $this->getStatusColor(),
                $this->getMessageText()
            )
        );

        if (count($this->output)) {
            $indentedOutput = array_map(
                function ($line) {
                    return '  ' . $line;
                },
                $this->output
            );

            $output->writeln($indentedOutput);
        }
    }

    public function getMessageText()
    {
        return sprintf(
            '%s %s%s.',
            $this->step->getName(),
            $this->getStatusMessage(),
            $this->explanation ? ' ' . $this->explanation : ''
        );
    }

    public function isFailure()
    {
        return self::INVALID === $this->status || self::FAILURE === $this->status;
    }

    private function getStatusColor()
    {
        switch ($this->status) {
            case self::SUCCESS:
                return 'green';
            case self::INVALID:
                return 'red';
            case self::FAILURE:
                return 'red';
            case self::WARNING:
                return 'yellow';
            case self::SKIPPED:
                return 'cyan';
        }

        return 'white';
    }

    private function getStatusMessage()
    {
        switch ($this->status) {
            case self::SUCCESS:
                return 'completed successfully';
            case self::INVALID:
                return 'did not return a valid result';
            case self::FAILURE:
                return 'failed';
            case self::WARNING:
                return 'generated a warning';
            case self::SKIPPED:
                return 'was skipped';
        }

        return 'white';
    }

    private function statusIsValid($status)
    {
        switch ($status) {
            case self::SKIPPED:
            case self::FAILURE:
            case self::INVALID:
            case self::WARNING:
            case self::SUCCESS:
                return true;
        }

        return false;
    }
}
