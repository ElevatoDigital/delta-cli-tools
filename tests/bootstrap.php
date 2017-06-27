<?php

define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

define('SSHD_TEST_USERNAME', getenv('SSHD_TEST_USERNAME') || shell_exec('whoami'));

require_once VENDOR_PATH . '/autoload.php';

/**
 * Set server defaults
 */
if(!isset($_SERVER['HOME'])){
    $_SERVER['HOME'] = '~';
}

if(!isset($_SERVER['USER'])){
    $_SERVER['USER'] = null;
}

/**
 * Windows compatibility
 */
if(strstr(PHP_OS,'WIN')) {
    if(PHP_WINDOWS_VERSION_MAJOR===10){
        //assuming that bash is available through Windows Substem for Linux
        //https://msdn.microsoft.com/en-us/commandline/wsl/install_guide
        define('SHELL_WRAPPER','%%windir%%\Sysnative\bash.exe -c %s');
    }
}

/**
 * This simple command checks if the SHELL_WRAPPER constant is defined and then renders the command using it.
 *
 * @param $command
 * @return string
 */
function deltacli_wrap_command(&$command){

    if(defined('SHELL_WRAPPER')){
        $command = sprintf(SHELL_WRAPPER,escapeshellarg($command));
    }

    return $command;
}