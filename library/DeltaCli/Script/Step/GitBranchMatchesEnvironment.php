<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exec;

class GitBranchMatchesEnvironment extends StepAbstract implements DryRunInterface, EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    public function getName()
    {
        return 'git-branch-matches-environment';
    }

    public function run()
    {
        if ($this->environment->isDevEnvironment()) {
            $result = new Result($this, Result::SKIPPED);
            $result->setExplanation("because {$this->environment->getName()} is a dev environment.");
            return $result;
        }

        if (!$this->environment->getGitBranch()) {
            return new Result(
                $this,
                Result::WARNING,
                [
                    "{$this->environment->getName()} is not associated with a git branch.",
                    'This could allow the incorrect branch to be deployed accidentally.',
                    "See http://bit.ly/delta-cli-git-env for more information."
                ]
            );
        }

        Exec::run('git status --porcelain -b 2>&1', $output, $exitStatus);

        if ($exitStatus) {
            return new Result($this, Result::FAILURE, $output);
        } else {
            $statusBranch = $this->parseBranchFromOutput($output[0]);

            if ($statusBranch === $this->environment->getGitBranch()) {
                return new Result($this, Result::SUCCESS);
            } else {
                $environmentName = $this->environment->getName();
                $gitBranch       = $this->environment->getGitBranch();

                return new Result(
                    $this,
                    Result::FAILURE,
                    [
                        "The {$environmentName} environment is associated with the {$gitBranch} git branch, but",
                        "you are currently on the {$statusBranch} branch."
                    ]
                );
            }
        }
    }

    public function dryRun()
    {
        return $this->run();
    }

    private function parseBranchFromOutput($output)
    {
        if (false === strpos($output, '.')) {
            $branch = substr($output, 3);

        } else {
            $prefixTrimmed = substr($output, 3);
            $branch = substr($prefixTrimmed, 0, strpos($prefixTrimmed, '.'));
        }

        return trim($branch);
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }
}
