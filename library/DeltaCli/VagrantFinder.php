<?php

namespace DeltaCli;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class VagrantFinder
{
    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $questionHelper;
        $this->input          = $input;
        $this->output         = $output;
    }

    public function locateVagrantPath()
    {
        Exec::run('vagrant global-status --prune', $output, $exitStatus);

        if (!count($output) || $exitStatus) {
            return '';
        }

        /**
         * When there are no environments found, Vagrant unhelpfully still show the normal headers and has a zero
         * exit status.  So, we just look for the message beneath those headers about the lack of environments.  Ugh.
         */
        if (false !== stripos($output[0], 'no active Vagrant')) {
            return '';
        }

        $machines = $this->parseMachinesFromOutput($output);

        if (1 === count($machines)) {
            $machine = reset($machines);
            return $machine['directory'];
        }

        return $this->promptToSelectMachine($machines);
    }

    private function promptToSelectMachine(array $machines)
    {
        $choices = [];

        foreach ($machines as $machine) {
            $choices[] = $machine['directory'];
        }

        $question = new ChoiceQuestion(
            '<fg=cyan>Please select the Vagrant box you want to use with Delta CLI</>',
            $choices
        );

        $question->setMaxAttempts(10);
        $question->setErrorMessage('Machine %s is invalid.');

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    private function parseMachinesFromOutput(array $output)
    {
        $headers      = $this->parseHeadersFromFirstLine($output[0]);
        $machineLines = $this->findMachineLinesInOutput($output);
        $machines     = $this->assignHeadersToMachineLines($headers, $machineLines);
        return $machines;
    }

    private function parseHeadersFromFirstLine($outputLine)
    {
        $titles  = ['id', 'name', 'provider', 'state', 'directory'];
        $headers = [];

        foreach ($titles as $index => $title) {
            if (isset($titles[$index + 1])) {
                $end = strpos($outputLine, $titles[$index + 1]);
            } else {
                $end = null;
            }

            $headers[] = [
                'start' => strpos($outputLine, $title),
                'end'   => $end,
                'title' => $title
            ];
        }

        return $headers;
    }

    /**
     * Get all lines from output between the dashed line beneath the headers and the blank
     * line following the listing of machines.
     *
     * @param array $output
     * @return array
     */
    private function findMachineLinesInOutput(array $output)
    {
        $machineLines = [];

        $output = array_slice($output, 2);

        foreach ($output as $line) {
            if ('' === trim($line)) {
                break;
            }

            $machineLines[] = $line;
        }

        return $machineLines;
    }

    private function assignHeadersToMachineLines(array $headers, array $machineLines)
    {
        $machines = [];

        foreach ($machineLines as $line) {
            $machine = [];

            foreach ($headers as $header) {
                $title = $header['title'];

                if ($header['end']) {
                    $machine[$title] = trim(substr($line, $header['start'], $header['end'] - $header['start']));
                } else {
                    $machine[$title] = trim(substr($line, $header['start']));
                }
            }

            $machines[] = $machine;
        }

        return $machines;
    }
}