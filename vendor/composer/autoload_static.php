<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit30b6e04c2d838b989b5850f18beaf3b8
{
    public static $files = array (
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'v' => 
        array (
            'vjoon\\Adapter\\' => 14,
        ),
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'P' => 
        array (
            'ParagonIE\\ConstantTime\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'vjoon\\Adapter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Parsedown' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit30b6e04c2d838b989b5850f18beaf3b8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit30b6e04c2d838b989b5850f18beaf3b8::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit30b6e04c2d838b989b5850f18beaf3b8::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit30b6e04c2d838b989b5850f18beaf3b8::$classMap;

        }, null, ClassLoader::class);
    }
}