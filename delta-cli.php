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
        'ls -lh'
    )
    ->addStep(
        'shell-command-with-name',
        'ls /tmp'
    )
    ->addStep(
        'uh-oh',
        function () {
            throw new \Exception('A PHP step failed!');
        }
    )
    ->addEnvironmentSpecificStep(
        'staging',
        'only-on-staging',
        function () {
            echo 'You must be deploying to staging!' . PHP_EOL;
        }
    );

$project->createScript('custom-script', 'Just an example custom script.')
    ->addStep(
        function () {
            echo "Hey!  It's custom!\n";
        }
    );

$project->createScript('composing-scripts', 'An example of calling one script from another.')
    ->addStep(
        function () {
            echo 'Doing things!';
        }
    )
    ->addStep($project->getScript('custom-script'));
