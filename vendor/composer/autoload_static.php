<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit38e5e194b616d8ef02974d720945f770
{
    public static $files = array (
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        '841780ea2e1d6545ea3a253239d59c05' => __DIR__ . '/..' . '/qiniu/php-sdk/src/Qiniu/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'l' => 
        array (
            'lib\\' => 4,
        ),
        'c' => 
        array (
            'cmd\\' => 4,
        ),
        'a' => 
        array (
            'admin\\' => 6,
        ),
        'T' => 
        array (
            'Twig\\' => 5,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Component\\Debug\\' => 24,
            'Symfony\\Component\\Console\\' => 26,
            'Sirius\\Validation\\' => 18,
            'Sirius\\Upload\\' => 14,
        ),
        'Q' => 
        array (
            'Qiniu\\' => 6,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Psr\\Http\\Message\\' => 17,
            'PhpAmqpLib\\' => 11,
        ),
        'M' => 
        array (
            'Medoo\\' => 6,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'lib\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
        'cmd\\' => 
        array (
            0 => __DIR__ . '/../..' . '/cmd',
        ),
        'admin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/admin',
        ),
        'Twig\\' => 
        array (
            0 => __DIR__ . '/..' . '/twig/twig/src',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Component\\Debug\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/debug',
        ),
        'Symfony\\Component\\Console\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/console',
        ),
        'Sirius\\Validation\\' => 
        array (
            0 => __DIR__ . '/..' . '/siriusphp/validation/src',
        ),
        'Sirius\\Upload\\' => 
        array (
            0 => __DIR__ . '/..' . '/siriusphp/upload/src',
        ),
        'Qiniu\\' => 
        array (
            0 => __DIR__ . '/..' . '/qiniu/php-sdk/src/Qiniu',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'PhpAmqpLib\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-amqplib/php-amqplib/PhpAmqpLib',
        ),
        'Medoo\\' => 
        array (
            0 => __DIR__ . '/..' . '/catfan/medoo/src',
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
    );

    public static $prefixesPsr0 = array (
        'T' => 
        array (
            'Twig_' => 
            array (
                0 => __DIR__ . '/..' . '/twig/twig/lib',
            ),
        ),
        'P' => 
        array (
            'PHPExcel' => 
            array (
                0 => __DIR__ . '/..' . '/phpoffice/phpexcel/Classes',
            ),
        ),
    );

    public static $classMap = array (
        'Uploader' => __DIR__ . '/../..' . '/admin/build/plugins/ue/php/Uploader.class.php',
        'admin\\api\\ErpApi' => __DIR__ . '/../..' . '/admin/api/ErpApi.php',
        'admin\\api\\api' => __DIR__ . '/../..' . '/admin/api/api.php',
        'admin\\api\\apiProduct' => __DIR__ . '/../..' . '/admin/api/apiProduct.php',
        'admin\\helper\\ImageHander' => __DIR__ . '/../..' . '/admin/helper/ImageHander.php',
        'admin\\helper\\api' => __DIR__ . '/../..' . '/admin/helper/api.php',
        'admin\\helper\\api\\domain' => __DIR__ . '/../..' . '/admin/helper/api/domain.php',
        'admin\\helper\\api\\erpbase' => __DIR__ . '/../..' . '/admin/helper/api/erpbase.php',
        'admin\\helper\\api\\erpdomain' => __DIR__ . '/../..' . '/admin/helper/api/erpdomain.php',
        'admin\\helper\\api\\erporder' => __DIR__ . '/../..' . '/admin/helper/api/erporder.php',
        'admin\\helper\\api\\erpproduct' => __DIR__ . '/../..' . '/admin/helper/api/erpproduct.php',
        'admin\\helper\\api\\erpseo' => __DIR__ . '/../..' . '/admin/helper/api/erpseo.php',
        'admin\\helper\\api\\erpzone' => __DIR__ . '/../..' . '/admin/helper/api/erpzone.php',
        'admin\\helper\\api\\olderpproduct' => __DIR__ . '/../..' . '/admin/helper/api/olderpproduct.php',
        'admin\\helper\\api\\olderpseo' => __DIR__ . '/../..' . '/admin/helper/api/olderpseo.php',
        'admin\\helper\\article' => __DIR__ . '/../..' . '/admin/helper/article.php',
        'admin\\helper\\category' => __DIR__ . '/../..' . '/admin/helper/category.php',
        'admin\\helper\\combo' => __DIR__ . '/../..' . '/admin/helper/combo.php',
        'admin\\helper\\comment' => __DIR__ . '/../..' . '/admin/helper/comment.php',
        'admin\\helper\\common' => __DIR__ . '/../..' . '/admin/helper/common.php',
        'admin\\helper\\company' => __DIR__ . '/../..' . '/admin/helper/company.php',
        'admin\\helper\\country' => __DIR__ . '/../..' . '/admin/helper/country.php',
        'admin\\helper\\currency' => __DIR__ . '/../..' . '/admin/helper/currency.php',
        'admin\\helper\\imageCut' => __DIR__ . '/../..' . '/admin/helper/imageCut.php',
        'admin\\helper\\index_focus' => __DIR__ . '/../..' . '/admin/helper/index_focus.php',
        'admin\\helper\\oa_users' => __DIR__ . '/../..' . '/admin/helper/oa_users.php',
        'admin\\helper\\order' => __DIR__ . '/../..' . '/admin/helper/order.php',
        'admin\\helper\\payment' => __DIR__ . '/../..' . '/admin/helper/payment.php',
        'admin\\helper\\product' => __DIR__ . '/../..' . '/admin/helper/product.php',
        'admin\\helper\\qiniu' => __DIR__ . '/../..' . '/admin/helper/qiniu.php',
        'admin\\helper\\site' => __DIR__ . '/../..' . '/admin/helper/site.php',
        'admin\\helper\\site_product' => __DIR__ . '/../..' . '/admin/helper/site_product.php',
        'admin\\helper\\sms' => __DIR__ . '/../..' . '/admin/helper/sms.php',
        'admin\\helper\\theme' => __DIR__ . '/../..' . '/admin/helper/theme.php',
        'admin\\helper\\themeDiy' => __DIR__ . '/../..' . '/admin/helper/themeDiy.php',
        'admin\\helper\\tongji' => __DIR__ . '/../..' . '/admin/helper/tongji.php',
        'admin\\helper\\tongji_calculate' => __DIR__ . '/../..' . '/admin/helper/tongji_calculate.php',
        'admin\\helper\\tongji_map' => __DIR__ . '/../..' . '/admin/helper/tongji_map.php',
        'admin\\helper\\upload' => __DIR__ . '/../..' . '/admin/helper/upload.php',
        'admin\\helper\\user' => __DIR__ . '/../..' . '/admin/helper/user.php',
        'admin\\helper\\userGroup' => __DIR__ . '/../..' . '/admin/helper/userGroup.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit38e5e194b616d8ef02974d720945f770::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit38e5e194b616d8ef02974d720945f770::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit38e5e194b616d8ef02974d720945f770::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit38e5e194b616d8ef02974d720945f770::$classMap;

        }, null, ClassLoader::class);
    }
}
