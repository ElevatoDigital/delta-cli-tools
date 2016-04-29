<?php

namespace DeltaCli\Template;

use DeltaCli\Project;

class WordPress implements TemplateInterface
{
    private $deployScriptName = 'deploy';

    private $syncedThemes = [];

    private $syncedPlugins = [];

    private $localWordPressRoot = null;

    private $remoteWordPressRoot = 'httpdocs';

    public function apply(Project $project)
    {
        /*
        $project->createScript('wp:copy-uploads-folder', 'Copy the uploads folder from a remote environment.')
            ->addStep(

            );
        */
        
        if ('deploy' !== $this->deployScriptName) {
            $deployScript = $project->createScript($this->deployScriptName, 'Deploy your WP project.');
        } else {
            $deployScript = $project->getScript('deploy');
        }

        foreach ($this->syncedThemes as $theme) {
            $localPath  = "{$this->localWordPressRoot}/wp-content/themes/{$theme}/";
            $remotePath = "{$this->remoteWordPressRoot}/wp-content/themes/{$theme}";

            $deployScript->addStep(
                $project->rsync($localPath, $remotePath)
                    ->setName("sync-{$theme}-theme")
            );
        }

        foreach ($this->syncedPlugins as $plugin) {
            $localPath  = "{$this->localWordPressRoot}/wp-content/plugins/{$plugin}/";
            $remotePath = "{$this->remoteWordPressRoot}/wp-content/plugins/{$plugin}";

            $deployScript->addStep(
                $project->rsync($localPath, $remotePath)
                    ->setName("sync-{$plugin}-plugin")
            );
        }
    }

    public function setDeployScriptName($deployScriptName)
    {
        $this->deployScriptName = $deployScriptName;

        return $this;
    }

    public function addSyncedTheme($syncedTheme)
    {
        $this->syncedThemes[] = $syncedTheme;

        return $this;
    }

    public function addSyncedPlugin($syncedPlugin)
    {
        $this->syncedPlugins[] = $syncedPlugin;

        return $this;
    }
}
