<?php

if (file_exists('httpdocs') && is_dir('httpdocs')) {
    chdir('httpdocs');
}

if (wp_cli_is_installed()) {
    echo realpath('wp-cli.phar');
    exit;
}

exec('curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar');

chmod('wp-cli.phar', 0744);

echo realpath('wp-cli.phar');

function wp_cli_is_installed()
{
    if (!file_exists('wp-cli.phar')) {
        return false;
    }

    if (time() - (86400 * 7) > filemtime('wp-cli.phar')) {
        return false;
    }

    return true;
}
