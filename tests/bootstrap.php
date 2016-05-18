<?php

define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

define('SSHD_TEST_USERNAME', getenv('SSHD_TEST_USERNAME') || shell_exec('whoami'));

require_once VENDOR_PATH . '/autoload.php';

