<?php

namespace DeltaCli\Script\Step;

use Symfony\Component\Console\Output\OutputInterface;

class DisplayLog extends DeltaApiAbstract
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $script;

    public function getName()
    {
        return 'display-log';
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setScript($script)
    {
        $this->script = $script;

        return $this;
    }

    public function run()
    {
        $response = $this->apiClient->fetchLog($this->script, $this->environment);

        if (200 !== $response->getStatusCode()) {
            $this->output->writeln('<error>Failed to fetch project log.</error>');
        } else {
            $json    = json_decode($response->getBody(), true);
            $entries = $json['entries'];

            foreach ($entries as $entry) {
                if ($entry['environment']) {
                    $header = sprintf('%s (%s)', $entry['script'], $entry['environment']);
                } else {
                    $header = $entry['script'];
                }

                if (Result::SUCCESS === $entry['status']) {
                    $status = 'info';
                } else {
                    $status = 'error';
                }

                $this->output->writeln(
                    [
                        "<{$status}>{$header}</{$status}>",
                        sprintf('Run By: %s', $entry['run_by_user']),
                        sprintf('Date:   %s', $this->formatTimestamp($entry['date_run']))
                    ]
                );

                if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
                    $this->output->writeln('');

                    foreach ($entry['steps'] as $step) {
                        $this->output->writeln(
                            sprintf(
                                '<fg=%s>%s</>',
                                $this->getStatusColor($step['status']),
                                $step['status_message']
                            )
                        );

                        if (trim($step['output']) && OutputInterface::VERBOSITY_VERY_VERBOSE <= $this->output->getVerbosity()) {
                            $this->output->writeln($this->filterStepOutput($step['output']));
                        }
                    }

                    $this->output->writeln(
                        [
                            '',
                            '---'
                        ]
                    );
                }

                $this->output->writeln('');
            }
        }

        $this->abort();
    }

    protected function abort()
    {
        exit;
    }

    private function getStatusColor($statusCode)
    {
        switch ($statusCode) {
            case Result::SUCCESS:
                return 'green';
            case Result::INVALID:
                return 'red';
            case Result::FAILURE:
                return 'red';
            case Result::WARNING:
                return 'yellow';
            case Result::SKIPPED:
                return 'cyan';
        }

        return 'white';
    }

    private function formatTimestamp($isoTimestamp)
    {
        return date('D M j Y G:i', strtotime($isoTimestamp));
    }

    private function filterStepOutput($output)
    {
        $lines = explode(PHP_EOL, $output);

        return array_map(
            function ($line) {
                return $line;
            },
            $lines
        );
    }
}
