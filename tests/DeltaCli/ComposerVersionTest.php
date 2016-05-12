<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;

class ComposerVersionTest extends PHPUnit_Framework_TestCase
{
    public function testWillReadVersionFromInstalledDotJsonFile()
    {
        $composerVersion = new ComposerVersion(__DIR__ . '/composer-version/installed');
        $this->assertEquals('1.22.0', $composerVersion->getCurrentVersion());
    }

    public function testWhenRunningFromGitReturnsGit()
    {
        $composerVersion = new ComposerVersion(__DIR__ . '/composer-version/git');
        $this->assertEquals('git', $composerVersion->getCurrentVersion());
    }

    public function testMissingInstalledDotJsonFileReturnsZeroes()
    {
        $composerVersion = new ComposerVersion(__DIR__);
        $this->assertEquals('0.0.0', $composerVersion->getCurrentVersion());
    }
}
