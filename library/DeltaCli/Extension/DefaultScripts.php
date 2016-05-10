<?php

namespace DeltaCli\Extension;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Project;
use DeltaCli\Script\SshFixKeyPermissions as SshFixKeyPermissionsScript;
use DeltaCli\Script\SshInstallKey as SshInstallKeyScript;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultScripts implements ExtensionInterface
{
    public function extend(Project $project)
    {
        $project->addScript(new SshFixKeyPermissionsScript($project));
        $project->addScript(new SshInstallKeyScript($project));

        $project->createScript('deploy', 'Deploy this project.')
            ->requireEnvironment()
            ->addDefaultStep($project->gitStatusIsClean())
            ->addDefaultStep($project->gitBranchMatchesEnvironment())
            ->addDefaultStep($project->fixSshKeyPermissions())
            ->setPlaceholderCallback(
                function (OutputInterface $output) {
                    $banner = new Banner($output);
                    $banner->setBackground('cyan');
                    $banner->render('A deploy script has not yet been created for this project.');

                    $output->writeln(
                        [
                            'Learn more about how to write a good deploy script for your project on Github at:',
                            '<fg=blue;options=underscore>https://github.com/DeltaSystems/delta-cli-tools</>'
                        ]
                    );
                }
            );
    }
}
