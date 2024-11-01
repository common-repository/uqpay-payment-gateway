<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0982bba0768e0178b1fd9e12caf59380
{
    public static $prefixLengthsPsr4 = array (
        'u' => 
        array (
            'uqpay\\payment\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'uqpay\\payment\\' => 
        array (
            0 => __DIR__ . '/..' . '/uqpay/uqpay_php/lib/UQPay',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0982bba0768e0178b1fd9e12caf59380::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0982bba0768e0178b1fd9e12caf59380::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
