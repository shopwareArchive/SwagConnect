<?php

namespace Tests\Shopware\Connect;

include('./../../../../../../tests/Shopware/TestHelper.php');

Shopware()->Loader()->registerNamespace('Tests\Shopware\Connect', __DIR__  . '/');

Shopware()->Bootstrap()->getResource('ConnectSDK');
