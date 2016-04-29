<?php

/* @var $project \DeltaCli\Project */

$project->setName('Delta CLI Tools Example');

$project
    ->createEnvironment('production')
    ->createEnvironment('staging');

$project->getDeployScript()
    ->addStep(
        'do-stuff',
        function () {
            sleep(1);
        }
    )
    ->addStep(
        'do-more-stuff',
        function () {
            echo 'Working hard!' . PHP_EOL;
        }
    )
    ->addStep(
        'uh-oh',
        function () {
            throw new \Exception('A PHP step failed!');
        }
    );
