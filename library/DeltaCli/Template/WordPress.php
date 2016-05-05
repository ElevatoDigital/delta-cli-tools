<?php

namespace DeltaCli\Template;

use DeltaCli\Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;


class WordPress implements TemplateInterface
{
    private $deployScriptName = 'deploy';

    private $syncedThemes = [];

    private $syncedPlugins = [];

    private $localWordPressRoot = null;

    private $remoteWordPressRoot = 'httpdocs';

    public function getInstallerChoiceKey()
    {
        return 'w';
    }

    public function getName()
    {
        return 'WordPress';
    }

    public function install(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {
        $themes  = $this->askInstallerSyncedFolderQuestion($questionHelper, 'themes', $input, $output);
        $plugins = $this->askInstallerSyncedFolderQuestion($questionHelper, 'plugins', $input, $output);

        ob_start();
        require __DIR__ . '/_delta-cli-templates/word-press.php';
        return ob_get_clean();
    }

    public function apply(Project $project)
    {
        $this->getLocalWordPressRoot();

        if ('deploy' !== $this->deployScriptName) {
            $deployScript = $project->createScript($this->deployScriptName, 'Deploy your WP project.');
        } else {
            $deployScript = $project->getScript('deploy');
        }

        if ($project->hasEnvironment('production')) {
            $deployScript->addEnvironmentSpecificStep('production', $project->ssh('backup'));
        }

        foreach ($this->syncedThemes as $theme) {
            $localPath  = "{$this->localWordPressRoot}/wp-content/themes/{$theme}/";
            $remotePath = "{$this->remoteWordPressRoot}/wp-content/themes/{$theme}/";

            $deployScript->addStep(
                $project->rsync($localPath, $remotePath)
                    ->setName("sync-{$theme}-theme")
            );
        }

        foreach ($this->syncedPlugins as $plugin) {
            $localPath  = "{$this->localWordPressRoot}/wp-content/plugins/{$plugin}/";
            $remotePath = "{$this->remoteWordPressRoot}/wp-content/plugins/{$plugin}/";

            $deployScript->addStep(
                $project->rsync($localPath, $remotePath)
                    ->setName("sync-{$plugin}-plugin")
            );
        }

        $deployScript->addStep(
            $project->allowWritesToRemoteFolder("{$this->remoteWordPressRoot}/wp-content/uploads")
                ->setName('change-upload-folder-permissions')
        );
    }

    public function getLocalWordPressRoot()
    {
        if (null === $this->localWordPressRoot) {
            $cwd = getcwd();

            if (file_exists($cwd . '/src')) {
                $this->localWordPressRoot = $cwd . '/src';
            } else {
                $this->localWordPressRoot = $cwd;
            }
        }

        return $this->localWordPressRoot;
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

    public function setLocalWordPressRoot($localWordPressRoot)
    {
        $this->localWordPressRoot = rtrim($localWordPressRoot, '/');

        return $this;
    }

    public function setRemoteWordPressRoot($remoteWordPressRoot)
    {
        $this->remoteWordPressRoot = rtrim($remoteWordPressRoot, '/');

        return $this;
    }

    private function askInstallerSyncedFolderQuestion(
        QuestionHelper $questionHelper,
        $wpContentFolderName,
        InputInterface $input,
        OutputInterface $output
    ) {
        $choices = $this->getInstallerChoices($wpContentFolderName);

        if (0 === count($choices)) {
            $output->writeln("<comment>No WordPress {$wpContentFolderName} found.</comment>");
            return [];
        }

        $question = new ChoiceQuestion(
            "<question>Would you like to include any {$wpContentFolderName} when deploying this project?</question>",
            $choices
        );

        $question->setMultiselect(true);

        return $questionHelper->ask($input, $output, $question);
    }

    private function getInstallerChoices($wpContentFolderName)
    {
        $path = $this->getLocalWordPressRoot() . '/wp-content/' . $wpContentFolderName;

        if (!is_dir($path)) {
            return [];
        }

        $folder  = opendir($path);
        $options = [];

        while ($themeFolder = readdir($folder)) {
            if (0 !== strpos($themeFolder, '.') && is_dir("{$path}/{$themeFolder}")) {
                $options[] = $themeFolder;
            }
        }

        closedir($folder);

        return $options;
    }
}
