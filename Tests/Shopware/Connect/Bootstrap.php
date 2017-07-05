<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\ShopwarePlugins\Connect;

require_once __DIR__ . '/../../../../../../../../../autoload.php';

use Shopware\Bundle\MediaBundle\OptimizerServiceInterface;
use Shopware\Components\Thumbnail\Manager;
use Shopware\Kernel;
use Shopware\Models\Media\Media;

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

        self::loadControllers();

        Shopware()->Loader()->registerNamespace('Tests\ShopwarePlugins\Connect', __DIR__ . '/');
        Shopware()->Container()->get('ConnectSDK');

        Shopware()->Container()->set('thumbnail_manager', new ThumbnailManagerDummy());
        Shopware()->Container()->set('shopware_media.cache_optimizer_service', new OptimizerServiceDummy());
        Shopware()->Container()->set('shopware_media.optimizer_service', new OptimizerServiceDummy());
    }

    private static function loadControllers()
    {
        if (getenv('SHOPWARE_ENV') === 'swagconnectest') {
            return;
        }
        $controllers = [
            'Frontend/Connect.php',
            'Frontend/ConnectProductGateway.php',

            'Widgets/Connect.php',

            'Backend/ConnectBaseController.php',
            'Backend/Connect52.php',
            'Backend/ConnectConfig.php',
            'Backend/ConnectGatewayBaseController.php',
            'Frontend/ConnectProductGateway.php',
            'Backend/ConnectGateway.php',
            'Backend/LastChanges.php',
            'Backend/Import.php',
        ];

        foreach ($controllers as $controller) {
            try {
                require_once __DIR__ . '/../../../Controllers/' . $controller;
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}

class ThumbnailManagerDummy extends Manager
{
    public function __construct()
    {
    }

    public function createMediaThumbnail(Media $media, $thumbnailSizes = [], $keepProportions = false)
    {
        return true;
    }
}

class OptimizerServiceDummy implements OptimizerServiceInterface
{
    public function optimize($filepath)
    {
    }

    public function getOptimizers()
    {
    }

    public function getOptimizerByMimeType($mime)
    {
    }
}

SwagConnectTestKernel::start();
