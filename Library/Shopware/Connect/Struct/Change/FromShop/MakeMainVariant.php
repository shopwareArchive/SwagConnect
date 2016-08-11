<?php
/**
 * This file is part of the Shopware Connect SDK Component.
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 */
namespace Shopware\Connect\Struct\Change\FromShop;

use Shopware\Connect\Struct\Change;

/**
 * Make main variant change struct
 *
 * The SDK is licensed under MIT license. (c) Shopware AG and Qafoo GmbH
 * @api
 */
class MakeMainVariant extends Change
{
    /**
     * Group id of the product shows if it has variants
     *
     * @var string
     */
    public $groupId;
}