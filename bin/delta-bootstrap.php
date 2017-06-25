<?php

ini_set('memory_limit', '4096M');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));
} else {
    define('VENDOR_PATH', realpath(__DIR__ . '/../../../'));
}

require_once VENDOR_PATH . '/autoload.php';

if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Chicago');
}

use DeltaCli\ArgvInput;
use DeltaCli\Command\CreateProjectConfig;
use DeltaCli\Command\DatabaseShell;
use DeltaCli\Command\ListEnvironments;
use DeltaCli\Command\SshKeyGen;
use DeltaCli\Command\SshShell;
use DeltaCli\Console\Output\Spinner;
use DeltaCli\ComposerVersion;
use DeltaCli\Console\Output\Banner;
use DeltaCli\Exception\ConsoleOutputInterface;
use DeltaCli\Debug;
use DeltaCli\Project;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

try {
    $input       = new ArgvInput();
    $output      = new ConsoleOutput();
    $application = new Application();
    $project     = new Project($application, $input, $output);

    Spinner::setDefaultOutput($output);

    $input->setProject($project);

    $composerVersion = new ComposerVersion(VENDOR_PATH);
    $application->setVersion($composerVersion->getCurrentVersion());

    Debug::createSingletonInstance($output);

    $application->setCatchExceptions(false);
    $application->setName('Delta CLI');
    $application->add(new CreateProjectConfig($project));
    $application->add(new DatabaseShell($project));
    $application->add(new ListEnvironments($project));
    $application->add(new SshKeyGen());
    $application->add(new SshShell($project));
    $application->addCommands($project->getScripts());
    $application->run($input, $output);
} catch (Exception $e) {
    if (!$e instanceof ConsoleOutputInterface || !$e->hasBanner()) {
        $banner = new Banner($output);
        $banner->setBackground('red');
        $banner->render(get_class($e));
    }

    if ($e instanceof ConsoleOutputInterface) {
        $e->outputToConsole($output);
    } else {
        $output->writeln($e->getMessage());
    }

    if ($output->isVerbose()) {
        $output->writeln('');
        $output->writeln('Debugging backtrace:');
        $output->writeln($e->getTraceAsString());
    }

    exit(1);
}

