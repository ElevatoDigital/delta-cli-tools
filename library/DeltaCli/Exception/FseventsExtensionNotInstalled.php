<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class FseventsExtensionNotInstalled extends Exception implements ConsoleOutputInterface
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
            ->render('Using Watch requires the fsevents PECL extension on OS X.');

        $output->writeln(
            [
                'Watching files for changes is currently only supported on OS X using',
                'the fsevents PECL extension.  You can install this extension by running',
                'the following command:',
                'sudo delta install-fsevents'
            ]
        );
    }
}
