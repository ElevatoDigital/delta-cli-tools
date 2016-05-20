<?php

/* @var $project \DeltaCli\Project */

$project->setName('Delta CLI Tools Example');

$project->addSlackHandle('@bgriffith');

$project->createEnvironment('production');

$project->createEnvironment('staging')
    ->setUsername('bgriffith')
    ->setSshPrivateKey(__DIR__ . '/ssh-keys/id_rsa')
    ->addHost('staging.deltasys.com');

$project->createEnvironment('example')
    ->setUsername('bgriffith')
    ->setSshPrivateKey(__DIR__ . '/ssh-keys/id_rsa')
    ->addHost('brad.plumbing')
    ->tunnelSshVia('staging');

$project->getScript('deploy')
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

$project->createScript('dry-runs-for-php-and-shell-commands', 'Custom steps can also support dry runs.')
    ->addStep(
        'shell-command-with-dry-run',
        $project->shellCommandSupportingDryRun(
            // Perform actual work
            'touch /tmp/important-file && cp /tmp/important-file /tmp/ultimate-destination',
            // Do something non-destructive when in dry-run mode
            'ls -l /tmp/important-file'
        )
    )
    ->addStep(
        'php-callable-with-dry-run',
        $project->phpCallableSupportingDryRun(
            // Perform actual work
            function () {
                file_put_contents(
                    '/tmp/file-to-write',
                    'Hey there!'
                );
            },
            // Do something non-destructive when in dry-run mode
            function () {
                if (!file_exists('/tmp/file-to-write')) {
                    echo 'file-to-write does not yet exist.' . PHP_EOL;
                } else {
                    echo 'file-to-write already exists.' . PHP_EOL;
                }
            }
        )
    );

$project->createScript('custom-script', 'Just an example custom script.')
    ->addStep(
        function () {
            echo "Hey!  It's custom!\n";
        }
    )
    ->addStep(
        'failing-step',
        function () {
            throw new \Exception('Ooops!');
        }
    );

$project->createScript('run-tests', 'Run PHPUnit tests.')
    ->addStep('phpunit', 'phpunit -c tests/phpunit.xml tests/');

$project->createScript('run-tests-with-coverage', 'Run PHPUnit tests.')
    ->addStep('phpunit', 'phpunit --coverage-html=test-coverage -c tests/phpunit.xml tests/');

$project->createScript('watch-tests', 'Watch for changes and run git status')
    ->addStep(
        $project->watch($project->getScript('run-tests'))
            ->setOnlyNotifyOnFailure(true)
            ->addPath('library/DeltaCli')
            ->addPath('tests')
    );

$project->createScript('composing-scripts', 'An example of calling one script from another.')
    ->addStep(
        function () {
            echo 'Doing things!';
        }
    )
    ->addStep($project->getScript('custom-script'));

$project->createEnvironmentScript('inline-naming-of-step', 'Shows naming a step via argument to addStep().')
    ->addStep('custom-step-name', $project->ssh('ls'))
    ->addEnvironmentSpecificStep('example', 'for-environment-steps-too', $project->ssh('ls'));

$project->createEnvironmentScript('rsync-example', 'An example using rsync.')
    ->addStep(
        $project->rsync('library', 'delta-cli-library')
            ->exclude('excluded-file')
    )
    ->addStep($project->allowWritesToRemoteFolder('delta-cli-library'))
    ->addStep($project->logAndSendNotifications());

$project->createEnvironmentScript('ssh-example', 'An example using SSH.')
    ->addStep($project->ssh('ls ~'));

$project->createEnvironmentScript('scp-example', 'An example using scp.')
    ->addStep($project->scp('delta-cli.php', 'delta-cli-via-scp.php'));

$project->createScript('notification-test', 'A script to test the notification API workflow.')
    ->addStep($project->logAndSendNotifications());
