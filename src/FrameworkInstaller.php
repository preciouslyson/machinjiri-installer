<?php

namespace Mlangeni\Machinjiri\Core\Artisans\Helpers;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class FrameworkInstaller extends LibraryInstaller
{
    public function getInstallPath(PackageInterface $package)
    {
        // Determine the installation path based on package type
        if ('machinjiri-framework' === $package->getType()) {
            return 'vendor/mlangenigroup/machinjiri';
        }
        
        return parent::getInstallPath($package);
    }

    public function supports($packageType)
    {
        return 'machinjiri-framework' === $packageType;
    }
}