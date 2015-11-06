<?php

namespace Tests\ShopwarePlugins\Connect;

include('./../../../../../../tests/Shopware/TestHelper.php');

Shopware()->Loader()->registerNamespace('Tests\ShopwarePlugins\Connect', __DIR__  . '/');

Shopware()->Bootstrap()->getResource('ConnectSDK');
