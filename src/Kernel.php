<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        if ($this->isRunningOnWsl()) {
            return sys_get_temp_dir().'/symfoconnect/cache/'.$this->environment;
        }

        return parent::getCacheDir();
    }

    public function getBuildDir(): string
    {
        if ($this->isRunningOnWsl()) {
            return sys_get_temp_dir().'/symfoconnect/build/'.$this->environment;
        }

        return parent::getBuildDir();
    }

    public function getLogDir(): string
    {
        if ($this->isRunningOnWsl()) {
            return sys_get_temp_dir().'/symfoconnect/log';
        }

        return parent::getLogDir();
    }

    private function isRunningOnWsl(): bool
    {
        if (\DIRECTORY_SEPARATOR !== '/') {
            return false;
        }

        $uname = php_uname();

        return str_contains($uname, 'Microsoft') || str_contains($uname, 'microsoft');
    }
}
