<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5863bc4f536d5f97b48f6bab9cc23500
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'n' => 
        array (
            'nl\\rabobank\\gict\\payments_savings\\omnikassa_sdk\\test\\' => 53,
            'nl\\rabobank\\gict\\payments_savings\\omnikassa_sdk\\' => 48,
        ),
        'S' => 
        array (
            'Stripe\\' => 7,
            'Sisow\\' => 6,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
        ),
        'M' => 
        array (
            'Mollie\\Api\\' => 11,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
            'Ginger\\' => 7,
        ),
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'nl\\rabobank\\gict\\payments_savings\\omnikassa_sdk\\test\\' => 
        array (
            0 => __DIR__ . '/..' . '/opensdks/omnikassa2-sdk/test',
        ),
        'nl\\rabobank\\gict\\payments_savings\\omnikassa_sdk\\' => 
        array (
            0 => __DIR__ . '/..' . '/opensdks/omnikassa2-sdk/src',
        ),
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
        'Sisow\\' => 
        array (
            0 => __DIR__ . '/..' . '/sisow/php-client/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
            1 => __DIR__ . '/..' . '/psr/http-factory/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'Mollie\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/mollie/mollie-api-php/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Ginger\\' => 
        array (
            0 => __DIR__ . '/..' . '/gingerpayments/ginger-php/src',
        ),
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/ca-bundle/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'J' => 
        array (
            'JsonMapper' => 
            array (
                0 => __DIR__ . '/..' . '/netresearch/jsonmapper/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5863bc4f536d5f97b48f6bab9cc23500::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5863bc4f536d5f97b48f6bab9cc23500::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit5863bc4f536d5f97b48f6bab9cc23500::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit5863bc4f536d5f97b48f6bab9cc23500::$classMap;

        }, null, ClassLoader::class);
    }
}