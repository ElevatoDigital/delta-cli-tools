<?php

namespace DeltaCli\Script;

use DeltaCli\Exec;
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
        exec('autoconf', $output, $exitStatus);

        $autoconfInstalled = true;

        // 127 is "command not found"
        if (127 === $exitStatus) {
            $autoconfInstalled = false;
        }

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
                            'phpize is not available.  Run `xcode-select --install` and click the install button.'
                        );
                    }
                }
            )
            ->addStep(
                'check-for-make',
                function () {
                    exec('make -v', $output, $exitStatus);

                    if ($exitStatus) {
                        throw new Exception(
                            'make is not available.  Run `xcode-select --install` and click the install button.'
                        );
                    }
                }
            )
            ->addStep(
                'delete-tmp-autoconf-folder-if-present',
                function () {
                    exec('rm -rf /tmp/autoconf*');
                }
            )
            ->addStep(
                'download-autoconf',
                $this->autoconfStep(
                    'curl -O http://ftp.gnu.org/gnu/autoconf/autoconf-latest.tar.gz',
                    $autoconfInstalled
                )
            )
            ->addStep(
                'extract-autoconf',
                $this->autoconfStep('tar xvfz autoconf-latest.tar.gz', $autoconfInstalled)
            )
            ->addStep(
                'configure-autoconf',
                $this->autoconfStep('cd autoconf* && ./configure --prefix=/usr/local', $autoconfInstalled)
            )
            ->addStep(
                'make-autoconf',
                $this->autoconfStep('cd autoconf* && make', $autoconfInstalled)
            )
            ->addStep(
                'install-autoconf',
                $this->autoconfStep('cd autoconf* && make install', $autoconfInstalled)
            )
            ->addStep(
                'delete-tmp-folder-if-present',
                function () {
                    if (file_exists('/tmp/php-pecl-fsevents')) {
                        exec('rm -rf /tmp/php-pecl-fsevents');
                    }
                }
            )
            ->addStep(
                'retrieve-extension-from-git',
                'git clone https://github.com/griffbrad/php-pecl-fsevents.git /tmp/php-pecl-fsevents'
            )
            ->addStep(
                'run-phpize',
                'export PHP_AUTOCONF=/usr/local/bin/autoconf; cd /tmp/php-pecl-fsevents && phpize'
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
                'grep ^extension /etc/php.ini | grep fsevents.so || '
                . 'echo "extension=/usr/local/php/fsevents.so" >> /etc/php.ini'
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

    private function autoconfStep($command, $autoconfInstalled)
    {
        return function () use ($command, $autoconfInstalled) {
            if ($autoconfInstalled) {
                echo 'autoconf is already installed';
                return;
            } else {
                Exec::run(sprintf('cd /tmp && %s', $command), $output, $exitStatus);

                if ($exitStatus) {
                    throw new Exception("Failed to install autoconf while running: {$command}");
                }
            }
        };
    }
}
