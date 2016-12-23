<?php

namespace DeltaCli;

use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Exception\ProjectNotConfigured;
use DeltaCli\Exception\ScriptNotFound;
use DeltaCli\Extension\DefaultScripts as DefaultScriptsExtension;
use DeltaCli\Extension\Vagrant as VagrantExtension;
use DeltaCli\FileWatcher\FileWatcherInterface;
use DeltaCli\FileWatcher\FileWatcherFactory;
use DeltaCli\Log\Detector\DetectorSet as LogDetectorSet;
use DeltaCli\Script\Step\AllowWritesToRemoteFolder as AllowWritesToRemoteFolderStep;
use DeltaCli\Script\Step\FindLogs as FindLogsStep;
use DeltaCli\Script\Step\FixSshKeyPermissions as FixSshKeyPermissionsStep;
use DeltaCli\Script\Step\GitBranchMatchesEnvironment as GitBranchMatchesEnvironmentStep;
use DeltaCli\Script\Step\GitStatusIsClean as GitStatusIsCleanStep;
use DeltaCli\Script\Step\IsDevEnvironment as IsDevEnvironmentStep;
use DeltaCli\Script\Step\LogAndSendNotifications as LogAndSendNotificationsStep;
use DeltaCli\Script\Step\PhpCallableSupportingDryRun as PhpCallableSupportingDryRunStep;
use DeltaCli\Script\Step\Rsync as RsyncStep;
use DeltaCli\Script\Step\Scp as ScpStep;
use DeltaCli\Script\Step\ShellCommandSupportingDryRun as ShellCommandSupportingDryRunStep;
use DeltaCli\Script\Step\Ssh as SshStep;
use DeltaCli\Script\Step\Watch as WatchStep;
use DeltaCli\Template\TemplateInterface;
use DeltaCli\Template\WordPress as WordPressTemplate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Project
{
    /**
     * @var string
     */
    private $name = 'Delta Systems CLI Tools';

    /**
     * @var array
     */
    private $environments = [];

    /**
     * @var array
     */
    private $scripts = [];

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $configFileLoaded = false;

    /**
     * @var string
     */
    private $minimumVersionRequired;

    /**
     * @var FileWatcherInterface
     */
    private $fileWatcher;

    /**
     * @var string
     */
    private $slackChannel;

    /**
     * @var array
     */
    private $slackHandles = [];

    /**
     * Project constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $defaultScriptsExtension = new DefaultScriptsExtension();
        $defaultScriptsExtension->extend($this);

        $vagrantExtension = new VagrantExtension();
        $vagrantExtension->extend($this);
    }

    public function requiresVersion($minimumVersionRequired)
    {
        $this->minimumVersionRequired = $minimumVersionRequired;

        return $this;
    }

    public function getMinimumVersionRequired()
    {
        return $this->minimumVersionRequired;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function configFileExists()
    {
        return file_exists(getcwd() . '/delta-cli.php');
    }

    public function loadConfigFile()
    {
        if (!$this->configFileLoaded) {
            $cwd = getcwd();

            if (!file_exists($cwd . '/delta-cli.php')) {
                throw new ProjectNotConfigured();
            }

            $project = $this;
            require_once $cwd . '/delta-cli.php';

            $this->configFileLoaded = true;

            if (!$this->hasEnvironment('vpn')) {
                $this->createEnvironment('vpn')
                    ->setUsername('delta')
                    ->addHost('vpn.deltasys.com');

                $privateKeyPath = $cwd . '/ssh-keys/id_rsa';

                if (file_exists($privateKeyPath)) {
                    $this->getEnvironment('vpn')->setSshPrivateKey($privateKeyPath);
                }
            }
        }
    }

    public function writeConfig($contents)
    {
        file_put_contents(getcwd() . '/delta-cli.php', $contents, LOCK_EX);
    }

    public function applyTemplate(TemplateInterface $template)
    {
        $template->apply($this);

        return $this;
    }

    public function wordPressTemplate()
    {
        return new WordpressTemplate();
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        // Load config file if available so we can get custom project name
        if ($this->configFileExists()) {
            $this->loadConfigFile();
        }

        return $this->name;
    }

    /**
     * @return string
     */
    public function getSlackChannel()
    {
        return $this->slackChannel;
    }

    /**
     * @param string $slackChannel
     * @return $this
     */
    public function setSlackChannel($slackChannel)
    {
        $this->slackChannel = '#' . ltrim($slackChannel, '#');

        return $this;
    }

    /**
     * @return array
     */
    public function getSlackHandles()
    {
        return $this->slackHandles;
    }

    /**
     * @param string $slackHandle
     * @return $this
     */
    public function addSlackHandle($slackHandle)
    {
        $this->slackHandles[] = '@' . ltrim($slackHandle, '@');

        return $this;
    }

    public function getScripts()
    {
        // Load config file if available so we can get custom scripts
        if ($this->configFileExists()) {
            $this->loadConfigFile();
        }

        return $this->scripts;
    }

    /**
     * @param $name
     * @return Script
     * @throws ScriptNotFound
     */
    public function getScript($name)
    {
        if (!array_key_exists($name, $this->scripts)) {
            throw new ScriptNotFound("No script could be found with the name '{$name}'.");
        }

        return $this->scripts[$name];
    }

    public function addScript(Script $script)
    {
        $this->scripts[$script->getName()] = $script;
        return $this;
    }

    /**
     * @param string $name
     * @param string $description
     * @return Script
     */
    public function createScript($name, $description)
    {
        $script = new Script($this, $name, $description);
        $this->addScript($script);
        return $script;
    }

    /**
     * @param string $name
     * @param string $description
     * @return Script
     */
    public function createEnvironmentScript($name, $description)
    {
        return $this->createScript($name, $description)
            ->requireEnvironment();
    }

    /**
     * @return Script
     * @throws ScriptNotFound
     */
    public function getDeployScript()
    {
        return $this->getScript('deploy');
    }

    public function getEnvironments()
    {
        return $this->environments;
    }

    public function createEnvironment($name)
    {
        $environment = new Environment($this, $name);
        $this->environments[$name] = $environment;
        return $environment;
    }

    public function addEnvironment(Environment $environment)
    {
        $this->environments[$environment->getName()] = $environment;
        return $this;
    }

    public function hasEnvironment($name)
    {
        return array_key_exists($name, $this->environments);
    }

    /**
     * @param $name
     * @return Environment
     * @throws EnvironmentNotFound
     */
    public function getEnvironment($name)
    {
        if (!$this->hasEnvironment($name)) {
            $exception = new EnvironmentNotFound();
            $exception->setName($name);
            throw $exception;
        }

        return $this->environments[$name];
    }

    /**
     * Get the environment selected for use during the current script or command.  This method will automatically
     * configure tunneling as well, using the command-line arguments to find the tunneling environment.
     *
     * @return Environment
     */
    public function getSelectedEnvironment()
    {
        return $this->getTunneledEnvironment($this->input->getArgument('environment'));
    }

    /**
     * Get an environment and configure tunneling for it.
     *
     * @param string $name
     * @return Environment
     */
    public function getTunneledEnvironment($name)
    {
        $environment = $this->getEnvironment($name);

        if ($this->input->getOption('tunnel-via')) {
            $environment->tunnelSshVia($this->input->getOption('tunnel-via'));
        }

        return $environment;
    }

    public function getFileWatcher()
    {
        if (!$this->fileWatcher) {
            $this->fileWatcher = FileWatcherFactory::factory($this->input, $this->output);
        }

        return $this->fileWatcher;
    }

    public function allowWritesToRemoteFolder($remoteFolder)
    {
        return new AllowWritesToRemoteFolderStep($remoteFolder);
    }

    public function findLogs()
    {
        return new FindLogsStep(new LogDetectorSet());
    }

    public function fixSshKeyPermissions()
    {
        return new FixSshKeyPermissionsStep();
    }

    public function gitStatusIsClean()
    {
        return new GitStatusIsCleanStep();
    }

    public function gitBranchMatchesEnvironment()
    {
        return new GitBranchMatchesEnvironmentStep();
    }

    public function isDevEnvironment()
    {
        return new IsDevEnvironmentStep();
    }

    public function logAndSendNotifications()
    {
        return new LogAndSendNotificationsStep($this);
    }

    public function phpCallableSupportingDryRun(callable $callable, callable $dryRunCallable)
    {
        return new PhpCallableSupportingDryRunStep($callable, $dryRunCallable);
    }

    public function phpCallbackSupportingDryRun(callable $callback, callable $dryRunCallback)
    {
        return $this->phpCallableSupportingDryRun($callback, $dryRunCallback);
    }

    public function rsync($localPath, $remotePath)
    {
        return new RsyncStep($localPath, $remotePath);
    }

    public function shellCommandSupportingDryRun($command, $dryRunCommand)
    {
        return new ShellCommandSupportingDryRunStep($command, $dryRunCommand);
    }

    public function scp($localFile, $remoteFile)
    {
        return new ScpStep($localFile, $remoteFile);
    }

    public function ssh($command)
    {
        return new SshStep($command);
    }

    public function watch($script)
    {
        if (is_string($script)) {
            $script = $this->getScript($script);
        }

        return new WatchStep($script, $this->getFileWatcher());
    }
}
