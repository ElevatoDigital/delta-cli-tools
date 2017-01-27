<?php

namespace DeltaCli;

use DeltaCli\Config\ConfigFactory;
use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Exception\ScriptNotFound;
use DeltaCli\Extension\DefaultScripts as DefaultScriptsExtension;
use DeltaCli\Extension\Vagrant as VagrantExtension;
use DeltaCli\FileWatcher\FileWatcherInterface;
use DeltaCli\FileWatcher\FileWatcherFactory;
use DeltaCli\Log\Detector\DetectorSet as LogDetectorSet;
use DeltaCli\Script\Step\AllowWritesToRemoteFolder as AllowWritesToRemoteFolderStep;
use DeltaCli\Script\Step\DumpDatabase as DumpDatabaseStep;
use DeltaCli\Script\Step\EmptyDatabase as EmptyDatabaseStep;
use DeltaCli\Script\Step\FindDatabases as FindDatabasesStep;
use DeltaCli\Script\Step\FindLogs as FindLogsStep;
use DeltaCli\Script\Step\FixSshKeyPermissions as FixSshKeyPermissionsStep;
use DeltaCli\Script\Step\GenerateDatabaseDiagram as GenerateDatabaseDiagramStep;
use DeltaCli\Script\Step\GenerateSearchAndReplaceSql as GenerateSearchAndReplaceSqlStep;
use DeltaCli\Script\Step\GitBranchMatchesEnvironment as GitBranchMatchesEnvironmentStep;
use DeltaCli\Script\Step\GitStatusIsClean as GitStatusIsCleanStep;
use DeltaCli\Script\Step\IsDevEnvironment as IsDevEnvironmentStep;
use DeltaCli\Script\Step\KillProcessMatchingName as KillProcessMatchingNameStep;
use DeltaCli\Script\Step\LogAndSendNotifications as LogAndSendNotificationsStep;
use DeltaCli\Script\Step\PhpCallableSupportingDryRun as PhpCallableSupportingDryRunStep;
use DeltaCli\Script\Step\RestoreDatabase as RestoreDatabaseStep;
use DeltaCli\Script\Step\Rsync as RsyncStep;
use DeltaCli\Script\Step\SanityCheckPotentiallyDangerousOperation as SanityCheckPotentiallyDangerousOperationStep;
use DeltaCli\Script\Step\Scp as ScpStep;
use DeltaCli\Script\Step\ShellCommandSupportingDryRun as ShellCommandSupportingDryRunStep;
use DeltaCli\Script\Step\Ssh as SshStep;
use DeltaCli\Script\Step\StartBackgroundProcess as StartBackgroundProcessStep;
use DeltaCli\Script\Step\Watch as WatchStep;
use DeltaCli\Template\TemplateInterface;
use DeltaCli\Template\WordPress as WordpressTemplate;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
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
     * @var Application
     */
    private $application;

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
     * @var Cache
     */
    private $globalCache;

    /**
     * @var Cache
     */
    private $projectCache;

    /**
     * Project constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(Application $application, InputInterface $input, OutputInterface $output)
    {
        $this->application = $application;
        $this->input       = $input;
        $this->output      = $output;

        $this->globalCache  = new Cache();
        $this->projectCache = null;

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

            $this->createDefaultEnvironments($cwd);

            if (file_exists($cwd . '/delta-cli.php')) {
                $this->projectCache = new Cache($cwd . '/.delta-cli-cache.json');

                $project = $this;
                require_once $cwd . '/delta-cli.php';
            }

            $this->setDefaultSshPrivateKeyOnEnvironments($cwd);

            $this->configFileLoaded = true;
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

    /**
     * @return Environment[]
     */
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
            $exception
                ->setName($name)
                ->setEnvironments($this->environments)
                ->setOutput($this->output);
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
        $environment       = $this->getEnvironment($name);
        $tunnelEnvironment = $this->input->getOption('tunnel-via');

        if ($this->input->getOption('vpn') && 'vagrant' !== $environment->getName()) {
            $tunnelEnvironment = 'vpn';
        }

        if ($tunnelEnvironment) {
            $environment->tunnelSshVia($tunnelEnvironment);
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

    public function dumpDatabase(DatabaseInterface $database)
    {
        return new DumpDatabaseStep($database);
    }

    public function emptyDatabase(DatabaseInterface $database)
    {
        return new EmptyDatabaseStep($database);
    }

    public function findDatabases()
    {
        return new FindDatabasesStep(new ConfigFactory($this->projectCache));
    }

    public function findLogs()
    {
        return new FindLogsStep(new LogDetectorSet());
    }

    public function fixSshKeyPermissions()
    {
        return new FixSshKeyPermissionsStep();
    }

    public function generateDatabaseDiagram(DatabaseInterface $database)
    {
        return new GenerateDatabaseDiagramStep($database, $this->output);
    }

    public function generateSearchAndReplaceSql(DatabaseInterface $database, $searchString, $replacementString)
    {
        return new GenerateSearchAndReplaceSqlStep($database, $this->output, $searchString, $replacementString);
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

    public function restoreDatabase(DatabaseInterface $database, $dumpFile)
    {
        return new RestoreDatabaseStep($database, $dumpFile);
    }

    public function rsync($localPath, $remotePath)
    {
        return new RsyncStep($localPath, $remotePath);
    }

    public function sanityCheckPotentiallyDangerousOperation($operationDescription)
    {
        return new SanityCheckPotentiallyDangerousOperationStep(
            $this->input,
            $this->projectCache,
            $operationDescription
        );
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

    public function sshSupportingDryRun($command, $dryRunCommand)
    {
        return new SshStep($command, $dryRunCommand);
    }

    public function killProcessMatchingName($searchString)
    {
        return new KillProcessMatchingNameStep($searchString);
    }

    public function startBackgroundProcess($command)
    {
        return new StartBackgroundProcessStep($command);
    }

    public function watch($script)
    {
        if (is_string($script)) {
            $script = $this->getScript($script);
        }

        return new WatchStep($script, $this->getFileWatcher());
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->projectCache;
    }

    private function createDefaultEnvironments($cwd)
    {
        if (!$this->hasEnvironment('vpn')) {
            $this->createEnvironment('vpn')
                ->setUsername('delta')
                ->addHost('vpn.deltasys.com');

            $privateKeyPath = $cwd . '/ssh-keys/id_rsa';

            if (file_exists($privateKeyPath)) {
                $this->getEnvironment('vpn')->setSshPrivateKey($privateKeyPath);
            }
        }

        $vagrantPath = $this->findVagrantPath();

        $vagrantPrivateKeyPath = null;

        if ($vagrantPath) {
            $vagrantPrivateKeyPath = $this->findVagrantPath() . '/.vagrant/machines/default/virtualbox/private_key';
        }

        if (!$this->hasEnvironment('vagrant') && file_exists($vagrantPrivateKeyPath)) {
            $this->createEnvironment('vagrant')
                ->setUsername('vagrant')
                ->setSshPrivateKey($vagrantPrivateKeyPath)
                ->setApplicationEnv('development')
                ->setIsDevEnvironment(true)
                ->addHost('127.0.0.1');

            if ('/delta' === $cwd || 0 === strpos($cwd, '/delta/')) {
                $homeFolder = $cwd;
            } else {
                $homeFolder = '/delta';
            }

            $this->getEnvironment('vagrant')->getHost('127.0.0.1')
                ->setSshPort(2222)
                ->setSshHomeFolder($homeFolder)
                ->setAdditionalSshOptions(
                    [
                        'Compression'           => 'yes',
                        'DSAAuthentication'     => 'yes',
                        'LogLevel'              => 'FATAL',
                        'StrictHostKeyChecking' => 'no',
                        'UserKnownHostsFile'    => '/dev/null',
                        'IdentitiesOnly'        => 'yes'
                    ]
                );
        }
    }

    private function findVagrantPath()
    {
        if ((!$vagrantPath = $this->globalCache->fetch('vagrant-path'))) {
            /* @var $helper QuestionHelper */
            $helper = $this->application->getHelperSet()->get('question');
            $finder = new VagrantFinder($helper, $this->input, $this->output);

            $vagrantPath = $finder->locateVagrantPath();

            if ($vagrantPath) {
                $this->globalCache->store('vagrant-path', $vagrantPath);
            }
        }

        return $vagrantPath;
    }

    /**
     * If a private key exists in the default location used by Delta CLI, automatically assign it to any
     * environments that did not already a key specified in the delta-cli.php file.
     *
     * @param string $workingDirectory
     * @return $this
     */
    private function setDefaultSshPrivateKeyOnEnvironments($workingDirectory)
    {
        $defaultSshPrivateKey = $workingDirectory . '/ssh-keys/id_rsa';

        foreach ($this->getEnvironments() as $environment) {
            if (!$environment->getSshPrivateKey() && file_exists($defaultSshPrivateKey)) {
                $environment->setSshPrivateKey($defaultSshPrivateKey);
            }
        }

        return $this;
    }
}
