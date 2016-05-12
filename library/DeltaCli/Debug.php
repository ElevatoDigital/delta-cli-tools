<?php

namespace DeltaCli;

use Symfony\Component\Console\Output\OutputInterface;

class Debug
{
    /**
     * @var Debug
     */
    private static $instance;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public static function createSingletonInstance(OutputInterface $output)
    {
        self::$instance = new Debug($output);
    }

    public static function log($message)
    {
        if (!self::$instance) {
            return;
        }

        self::$instance->writeLog($message);
    }

    public function writeLog($message)
    {
        if ($this->output->getVerbosity() !== OutputInterface::VERBOSITY_DEBUG) {
            return;
        }

        if (!is_array($message)) {
            $message = [$message];
        }

        foreach ($message as $index => $line) {
            $message[$index] = sprintf('<fg=magenta>%s</>', $line);
        }

        $this->output->writeln($message);
    }
}
