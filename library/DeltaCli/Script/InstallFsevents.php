<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Exception;

class InstallFsevents extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'install-fsevents',
            'Install the fsevents PECL extension on OS X.'
        );
    }

    protected function addSteps()
    {

        $this
            ->addStep(
                'ensure-root',
                function () {
                    if ('root' !== trim(shell_exec('whoami'))) {
                        throw new Exception('install-fsevents must be run as root.');
                    }
                }
            )
            ->addStep(
                'ensure-osx',
                function () {
                    if ('Darwin' !== php_uname('s')) {
                        throw new Exception('fsevents PECL extension can only be installed on OS X.');
                    }
                }
            )
            ->addStep(
                'check-for-phpize',
                function () {
                    if (!file_exists('/usr/bin/phpize') || !is_executable('/usr/bin/phpize')) {
                        throw new Exception(
                            'phpize is required to build fsevents.  Run xcode-select --install to install it.'
                        );
                    }
                }
            )
            ->addStep(
                'retrieve-extension-from-git',
                'git clone https://github.com/griffbrad/php-pecl-fsevents.git /tmp/php-pecl-fsevents'
            )
            ->addStep(
                'run-phpize',
                'cd /tmp/php-pecl-fsevents && phpize'
            )
            ->addStep(
                'run-configure',
                'cd /tmp/php-pecl-fsevents && ./configure --enable-fsevents'
            )
            ->addStep(
                'run-make',
                'cd /tmp/php-pecl-fsevents && make'
            )
            ->addStep(
                'create-folder',
                'mkdir -p /usr/local/php'
            )
            ->addStep(
                'copy-module',
                'cp /tmp/php-pecl-fsevents/modules/fsevents.so /usr/local/php/fsevents.so'
            )
            ->addStep(
                'add-to-phpdotini',
                'echo "extension=/usr/local/php/fsevents.so" >> /etc/php.ini'
            )
            ->addStep(
                'cleanup',
                'rm -rf /tmp/php-pecl-fsevents'
            )
            ->addStep(
                'check-installation',
                function () {
                    $installed = (int) trim(shell_exec('php -i | grep fsevents | wc -l'));

                    if (!$installed) {
                        throw new Exception('Installation failed.');
                    }
                }
            );
    }
}
