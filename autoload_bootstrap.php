<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../../../../../../autoload.php';
require __DIR__ . '/Bootstrap.php';

$loader = new Enlight_Loader();
$loader->registerNamespace(
    'Shopware\\Connect',
    __DIR__ . '/Library/Shopware/Connect/'
);

$loader->registerNamespace(
    'Firebase\\JWT',
    __DIR__ . '/Library/Firebase/JWT/'
);

$loader->registerNamespace(
    'ShopwarePlugins\\Connect',
    __DIR__ . '/'
);

$loader->registerNamespace(
    'Shopware\CustomModels',
    __DIR__ . '/Models/'
);

loadControllers();

function loadControllers()
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
            require_once __DIR__ . '/Controllers/' . $controller;
        } catch (\Exception $e) {
            continue;
        }
    }
}
