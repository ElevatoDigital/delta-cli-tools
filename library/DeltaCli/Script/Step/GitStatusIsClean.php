<?php

namespace DeltaCli\Script\Step;

class GitStatusIsClean extends StepAbstract implements DryRunInterface
{
    public function getName()
    {
        return 'git-status-is-clean';
    }

    public function run()
    {
        exec('git status --porcelain 2>&1', $output, $exitStatus);

        if ($exitStatus) {
            return new Result($this, Result::FAILURE, $output);
        } else if (count($output)) {
            $statusOutput = [
                'You have uncommitted changes.  These should be committed or added to .gitignore before',
                'deploying to avoid running un-tracked code changes outside development environments.',
                ''
            ];

            foreach ($output as $line) {
                $statusOutput[] = '  ' . $line;
            }

            return new Result($this, Result::FAILURE, $statusOutput);
        } else {
            return new Result($this, Result::SUCCESS);
        }
    }

    public function dryRun()
    {
        return $this->run();
    }
}
