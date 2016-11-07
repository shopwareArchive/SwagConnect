<?php

namespace Tests\ShopwarePlugins\Connect;

if (file_exists('./../../../../../../tests/Shopware/TestHelper.php')) {
    include('./../../../../../../tests/Shopware/TestHelper.php');
} else {
    include(__DIR__ . '/../../../../../../../../../tests/Functional/bootstrap.php');
}
Shopware()->Loader()->registerNamespace('Tests\ShopwarePlugins\Connect', __DIR__  . '/');

Shopware()->Container()->get('ConnectSDK');
