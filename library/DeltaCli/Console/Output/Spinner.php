<?php

namespace DeltaCli\Console\Output;

use DeltaCli\Host;
use DeltaCli\Script\Step\StepInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Spinner
{
    private static $defaultOutput;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $characters = [
        '|', '/', '-', '\\'
    ];

    private $firstCall = true;

    /**
     * @var int
     */
    private $currentCharacter = 0;

    /**
     * @var string
     */
    private $defaultMessage = '';

    public function __construct(OutputInterface $output, $defaultMessage = '')
    {
        $this->output         = $output;
        $this->defaultMessage = $defaultMessage;
    }

    public function spin($message = null)
    {
        if (!$this->output->isDecorated()) {
            return;
        }

        if (null === $message) {
            $message = $this->defaultMessage;
        }

        if (!$this->firstCall) {
            $this->clear();
        }

        if (!array_key_exists($this->currentCharacter, $this->characters)) {
            $this->currentCharacter = 0;
        }

        $character = $this->characters[$this->currentCharacter];

        $this->currentCharacter += 1;

        $this->output->writeln("<comment>{$character} {$message} </comment>");

        $this->firstCall = false;

        usleep(30000);
    }

    public function clear()
    {
        if ($this->firstCall || !$this->output->isDecorated()) {
            return;
        }

        // Move the cursor to the beginning of the line
        $this->output->write("\x0D");

        // Erase the line
        $this->output->write("\x1B[2K");

        $this->output->write("\x1B[1A\x1B[2K");
    }

    public static function setDefaultOutput(OutputInterface $output)
    {
        self::$defaultOutput = $output;
    }

    public static function forStep(StepInterface $step, Host $host = null)
    {
        return new Spinner(
            self::$defaultOutput,
            sprintf(
                'Running %s%s%s...',
                $step->getName(),
                (null !== $host ? ' on ' : ''),
                (null !== $host ? $host->getHostname() : '')
            )
        );
    }
}