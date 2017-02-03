<?php

namespace DeltaCli\Extension;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Project;
use DeltaCli\Script\InstallFsevents;
use DeltaCli\Script\Log;
use DeltaCli\Script\ClearCaches as ClearCachesScript;
use DeltaCli\Script\DatabaseCopy as DatabaseCopyScript;
use DeltaCli\Script\DatabaseDiagram as DatabaseDiagramScript;
use DeltaCli\Script\DatabaseDump as DatabaseDumpScript;
use DeltaCli\Script\DatabaseList as DatabaseListScript;
use DeltaCli\Script\DatabaseRestore as DatabaseRestoreScript;
use DeltaCli\Script\DatabaseSearchAndReplace as DatabaseSearchAndReplaceScript;
use DeltaCli\Script\DatabaseTunnel as DatabaseTunnelScript;
use DeltaCli\Script\Diff as DiffScript;
use DeltaCli\Script\EnvironmentCreate as EnvironmentCreateScript;
use DeltaCli\Script\Open as OpenScript;
use DeltaCli\Script\Rsync as RsyncScript;
use DeltaCli\Script\Scp as ScpScript;
use DeltaCli\Script\LogsList as LogsListScript;
use DeltaCli\Script\SshFixKeyPermissions as SshFixKeyPermissionsScript;
use DeltaCli\Script\SshInstallKey as SshInstallKeyScript;
use DeltaCli\Script\LogsWatch as LogsWatchScript;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultScripts implements ExtensionInterface
{
    public function extend(Project $project)
    {
        $project->addScript(new ClearCachesScript($project));
        $project->addScript(new DiffScript($project));
        //$project->addScript(new EnvironmentCreateScript($project));
        $project->addScript(new InstallFsevents($project));
        $project->addScript(new Log($project));
        $project->addScript(new RsyncScript($project));
        $project->addScript(new SshFixKeyPermissionsScript($project));
        $project->addScript(new SshInstallKeyScript($project));
        $project->addScript(new ScpScript($project));
        $project->addScript(new DatabaseCopyScript($project));
        $project->addScript(new DatabaseDumpScript($project));
        $project->addScript(new DatabaseDiagramScript($project));
        $project->addScript(new DatabaseListScript($project));
        $project->addScript(new DatabaseRestoreScript($project));
        $project->addScript(new DatabaseSearchAndReplaceScript($project));
        $project->addScript(new DatabaseTunnelScript($project));
        $project->addScript(new LogsListScript($project));
        $project->addScript(new LogsWatchScript($project));
        $project->addScript(new OpenScript($project));

        $project->createScript('deploy', 'Deploy this project.')
            ->requireEnvironment()
            ->addDefaultStep($project->gitStatusIsClean())
            ->addDefaultStep($project->gitBranchMatchesEnvironment())
            ->addDefaultStep($project->fixSshKeyPermissions())
            ->addDefaultStep(
                $project->logAndSendNotifications()
                    ->setSendNotificationsOnScriptFailure(false)
            )
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

        $project->createScript('git:latest-tag', 'Display the latest git tag for this project.')
            ->addStep('display-latest-git-tag', 'git describe --abbrev=0 --tags');
    }
}
