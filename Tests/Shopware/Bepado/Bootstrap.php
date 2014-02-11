<?php

include('./../../../../../../tests/Shopware/TestHelper.php');

Shopware()->Loader()->registerNamespace('Tests\Shopware\Bepado', __DIR__  . '/');

Shopware()->Bootstrap()->getResource('BepadoSDK');

