<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exec;

class FixSshKeyPermissions extends StepAbstract implements DryRunInterface
{
    public function getName()
    {
        return 'fix-ssh-key-permissions';
    }

    public function run()
    {
        $folder     = getcwd() . '/ssh-keys';
        $privateKey = $folder . '/id_rsa';

        if (!file_exists($folder) || !is_dir($folder)) {
            return new Result($this, Result::FAILURE, 'ssh-keys folder not found.  Run ssh:generate-key to create it.');
        } elseif (!file_exists($privateKey)) {
            return new Result($this, Result::FAILURE, 'SSH private key not found.  Run ssh:generate-key to create it.');
        } else {
            Exec::run(
                sprintf('chmod 0600 %s', escapeshellarg($privateKey)),
                $output,
                $keyExitStatus
            );

            Exec::run(
                sprintf('chmod 0700 %s', escapeshellarg($folder)),
                $output,
                $folderExitStatus
            );

            if ($keyExitStatus || $folderExitStatus) {
                return new Result($this, Result::FAILURE, 'Failed to set permissions for SSH key.');
            } else {
                return new Result($this, Result::SUCCESS);
            }
        }
    }

    public function dryRun()
    {
        $folder     = getcwd() . '/ssh-keys';
        $privateKey = $folder . '/id_rsa';

        if (!file_exists($folder) || !is_dir($folder)) {
            return new Result($this, Result::FAILURE, 'ssh-keys folder not found.  Run ssh:generate-key to create it.');
        } elseif (!file_exists($privateKey)) {
            return new Result($this, Result::FAILURE, 'SSH private key not found.  Run ssh:generate-key to create it.');
        } elseif ('0700' === $this->getOctalPermissions($folder) && '0600' === $this->getOctalPermissions($privateKey)) {
            return new Result($this, Result::SUCCESS, 'SSH key permissions are correct.');
        } else {
            return new Result(
                $this,
                Result::FAILURE,
                'SSH key permissions are not correct.  Run ssh:fix-key-permissions to correct them.'
            );
        }
    }

    private function getOctalPermissions($file)
    {
        return substr(sprintf('%o', fileperms($file)), -4);
    }
}
