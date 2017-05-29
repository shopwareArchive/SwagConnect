<?php

namespace Tests\ShopwarePlugins\Connect;

require_once __DIR__ . '/../../../../../../../../../autoload.php';

use Shopware\Kernel;

class SwagConnectTestKernel
{
    public static function start()
    {
        $kernel = new Kernel(getenv('SHOPWARE_ENV') ?: 'testing', false);
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(E_ALL | E_STRICT);

        /** @var $repository \Shopware\Models\Shop\Repository */
        $repository = $container->get('models')->getRepository('Shopware\Models\Shop\Shop');

        $shop = $repository->getActiveDefault();
        $shop->registerResources();

        $_SERVER['HTTP_HOST'] = $shop->getHost();

        Shopware()->Loader()->registerNamespace('Tests\ShopwarePlugins\Connect', __DIR__  . '/');
        Shopware()->Container()->get('ConnectSDK');
    }
}

SwagConnectTestKernel::start();