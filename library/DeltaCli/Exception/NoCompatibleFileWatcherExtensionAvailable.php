<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class NoCompatibleFileWatcherExtensionAvailable extends Exception implements ConsoleOutputInterface
{
    public function hasBanner()
    {
        return true;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render('Your system does not have fsevents (OS X) nor inotify (Linux) extensions installed.');

        $output->writeln(
            [
                'watch() steps in Delta CLI leverage either fsevents on OS X or inotify on Linux',
                'to watch folders for changes.  Install the appropriate PECL extension for your',
                'operating system to use watch().',
                '',
                'For fsevents on OS X, try:',
                'sudo delta install-fsevents',
                '',
                'For inotify on Linux, check your distro\'s package manager for a PHP inotify package.'
            ]
        );
    }
}
