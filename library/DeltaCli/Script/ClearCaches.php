<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class ClearCaches extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'clear-caches',
            'Clear project-level and global Delta CLI caches.'
        );
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'clear-global-cache',
                function () {
                    $cachePath = $_SERVER['HOME'] . '/.delta-cli-cache.json';

                    if (file_exists($cachePath)) {
                        unlink($cachePath);
                    }
                }
            )
            ->addStep(
                'clear-project-cache',
                function () {
                    $cachePath = getcwd() . '/.delta-cli-cache.json';

                    if (file_exists($cachePath)) {
                        unlink($cachePath);
                    }
                }
            );
    }
}
