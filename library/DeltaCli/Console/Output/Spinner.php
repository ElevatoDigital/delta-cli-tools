<?php

namespace DeltaCli\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class Spinner
{
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

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function spin($message)
    {
        if (!$this->output->isDecorated()) {
            return;
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
        if (!$this->output->isDecorated()) {
            return;
        }

        // Move the cursor to the beginning of the line
        $this->output->write("\x0D");

        // Erase the line
        $this->output->write("\x1B[2K");

        $this->output->write("\x1B[1A\x1B[2K");
    }
}