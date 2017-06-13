<?php

namespace SwagConnect;

require_once __DIR__ . '/Bootstrap.php';

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;

class SwagConnect extends Plugin
{
    public function install(InstallContext $context)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        $config = new \Enlight_Config([ 'path' => __DIR__ ]);

        $bootstrap = new \Shopware_Plugins_Backend_SwagConnect_Bootstrap('SwagConnect', $config, $context);
        $bootstrap->doSetup();
    }
}