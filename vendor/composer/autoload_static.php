<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit377deeff98938dc65945738341a43755
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit377deeff98938dc65945738341a43755::$classMap;

        }, null, ClassLoader::class);
    }
}
